<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservation;
use App\Entity\Commande;
use App\Entity\User;
use App\Form\ReservationType;
use App\Form\ReservationBackType;
use App\Service\MLScoringService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

#[Route('/reservation', name: 'front_reservation_')]
class ReservationController extends AbstractController
{
    // ══════════════════════════════════════
    // CLIENT
    // ══════════════════════════════════════

    #[Route('/new/{id}', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logement = $em->getRepository(Logement::class)->find($id);
            if (!$logement) {
                $this->addFlash('error', "Logement introuvable !");
                return $this->redirectToRoute('app_home');
            }

            $reservation->setLogement($logement);
            $nbNuits   = $reservation->getDateDebut()->diff($reservation->getDateFin())->days;
            $prixTotal = $nbNuits * $logement->getPrix() * $reservation->getNombrePersonnes();
            $reservation->setPrixTotal($prixTotal);
            $reservation->setStatus('EN_ATTENTE');

            $user = $this->getUser();
            if (!$user) {
                throw $this->createAccessDeniedException('Vous devez être connecté.');
            }
            $reservation->setUser($user);

            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', 'Votre réservation a été envoyée avec succès !');
            return $this->redirectToRoute('front_reservation_list');
        }

        return $this->render('frontOffice/reservation/client/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/list', name: 'list')]
    public function list(
        EntityManagerInterface $em,
        HttpClientInterface $httpClient
    ): Response {
        $user         = $this->getUser();
        $reservations = $em->getRepository(Reservation::class)->findBy(['user' => $user]);

        // ── 1. Rappel 24h ──
        $reservData = array_map(fn($r) => [
            'id'         => $r->getId(),
            'status'     => $r->getStatus(),
            'date_debut' => $r->getDateDebut()->format('Y-m-d')
        ], $reservations);

        $alertes24h = [];
        try {
            $resp = $httpClient->request('POST', 'http://127.0.0.1:5000/rappel-24h', [
                'json'    => ['reservations' => $reservData],
                'timeout' => 2
            ]);
            $alertes24h = $resp->toArray()['alertes'] ?? [];
        } catch (\Exception $e) {
            $now = new \DateTime();
            foreach ($reservations as $r) {
                $diff   = $now->diff($r->getDateDebut());
                $heures = ($diff->days * 24) + $diff->h;
                if ($r->getStatus() === 'EN_ATTENTE' && $heures <= 24 && $heures >= 0) {
                    $alertes24h[$r->getId()] = [
                        'niveau'        => $heures <= 2 ? 'danger' : 'warning',
                        'message'       => "Plus que {$heures}h pour modifier !",
                        'peut_modifier' => $heures > 2
                    ];
                } else {
                    $alertes24h[$r->getId()] = ['niveau' => 'ok', 'message' => null, 'peut_modifier' => true];
                }
            }
        }

        // ── 2. Analyse Prix ──
        $tousLesPrix = array_values(array_map(
            fn($l) => (float) $l->getPrix(),
            $em->getRepository(Logement::class)->findAll()
        ));

        $analysesPrix = [];
        foreach ($reservations as $r) {
            try {
                $resp = $httpClient->request('POST', 'http://127.0.0.1:5000/prix-analyse', [
                    'json' => [
                        'prix'      => (float) $r->getLogement()->getPrix(),
                        'tous_prix' => $tousLesPrix
                    ],
                    'timeout' => 2
                ]);
                $analysesPrix[$r->getId()] = $resp->toArray();
            } catch (\Exception $e) {
                $analysesPrix[$r->getId()] = ['badge' => 'info', 'message' => 'N/A', 'diff_pct' => 0];
            }
        }

        // ── 3. Dates Optimales ──
        $datesExistantes = array_values(array_map(
            fn($r) => $r->getDateDebut()->format('Y-m-d'),
            $em->getRepository(Reservation::class)->findAll()
        ));

        $prixMoyen      = !empty($tousLesPrix) ? array_sum($tousLesPrix) / count($tousLesPrix) : 100;
        $datesOptimales = [];

        try {
            $resp = $httpClient->request('POST', 'http://127.0.0.1:5000/dates-optimales', [
                'json' => [
                    'reservations_existantes' => $datesExistantes,
                    'prix_base'               => $prixMoyen
                ],
                'timeout' => 2
            ]);
            $datesOptimales = $resp->toArray()['suggestions'] ?? [];
        } catch (\Exception $e) {
            $datesOptimales = [];
        }

        return $this->render('frontOffice/reservation/client/list.html.twig', [
            'reservations'   => $reservations,
            'alertes24h'     => $alertes24h,
            'analysesPrix'   => $analysesPrix,
            'datesOptimales' => $datesOptimales,
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // ✅ Contrôle 24h — AVANT tout traitement
        $now             = new \DateTime();
        $diff            = $now->diff($reservation->getDateDebut());
        $heuresRestantes = ($diff->days * 24) + $diff->h;

        if ($heuresRestantes < 24) {
            $this->addFlash('error', "Impossible — moins de 24h avant la date d'entrée !");
            return $this->redirectToRoute('front_reservation_list');
        }

        $form = $this->createForm(ReservationBackType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nbNuits   = $reservation->getDateDebut()->diff($reservation->getDateFin())->days;
            $prixTotal = $nbNuits * $reservation->getLogement()->getPrix() * $reservation->getNombrePersonnes();
            $reservation->setPrixTotal($prixTotal);
            $em->flush();

            $this->addFlash('success', 'Réservation modifiée avec succès !');
            return $this->redirectToRoute('front_reservation_list');
        }

        return $this->render('frontOffice/reservation/client/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cancel/{id}', name: 'cancel')]
    public function cancel(Reservation $reservation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // ✅ Contrôle 24h — AVANT d'annuler
        $now             = new \DateTime();
        $diff            = $now->diff($reservation->getDateDebut());
        $heuresRestantes = ($diff->days * 24) + $diff->h;

        if ($heuresRestantes < 24) {
            $this->addFlash('error', "Impossible — moins de 24h avant la date d'entrée !");
            return $this->redirectToRoute('front_reservation_list');
        }

        $reservation->setStatus('ANNULÉE');
        $em->flush();

        $this->addFlash('success', 'Réservation annulée avec succès !');
        return $this->redirectToRoute('front_reservation_list');
    }

    // ══════════════════════════════════════
    // PROPRIÉTAIRE
    // ══════════════════════════════════════

    #[Route('/proprietaire', name: 'proprietaire_list')]
    public function proprietaireList(
        Request $request,
        EntityManagerInterface $em,
        MLScoringService $mlScoring
    ): Response {
        $logementId = $request->query->get('logement');
        $logements  = $em->getRepository(Logement::class)->findAll();

        if ($logementId) {
            $reservations = $em->getRepository(Reservation::class)->findBy(['logement' => $logementId]);
        } else {
            $reservations = $em->getRepository(Reservation::class)->findAll();
        }

        $scores = [];
        foreach ($reservations as $r) {
            if ($r->getStatus() === 'EN_ATTENTE') {
                $scores[$r->getId()] = $mlScoring->getScore($r);
            }
        }

        return $this->render('frontOffice/reservation/proprietaire/list.html.twig', [
            'logements'        => $logements,
            'reservations'     => $reservations,
            'selectedLogement' => $logementId,
            'scores'           => $scores
        ]);
    }

    #[Route('/proprietaire/action/{id}/{status}', name: 'proprietaire_action')]
    public function proprietaireAction(Reservation $reservation, string $status, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $logger->info('proprietaireAction called', ['reservation_id' => $reservation->getId(), 'status' => $status]);
        $reservation->setStatus($status);

        if ($status === 'CONFIRMÉE') {
            $autres = $em->getRepository(Reservation::class)->createQueryBuilder('r')
                ->where('r.logement = :logement')
                ->andWhere('r.id != :id')
                ->andWhere('r.status = :en_attente')
                ->andWhere('r.dateDebut < :fin AND r.dateFin > :debut')
                ->setParameter('logement', $reservation->getLogement())
                ->setParameter('id', $reservation->getId())
                ->setParameter('en_attente', 'EN_ATTENTE')
                ->setParameter('debut', $reservation->getDateDebut())
                ->setParameter('fin', $reservation->getDateFin())
                ->getQuery()
                ->getResult();

            foreach ($autres as $res) {
                $res->setStatus('ANNULÉE');
            }

            $commandeController = new \App\Controller\CommandeController();
            $commandeController->createFromReservation($reservation, $em);

            // Envoi d'un email au client pour l'informer de la confirmation
            try {
                $userEmail = $reservation->getUser()?->getEmail();
                $logger->info('Preparing to send confirmation email', ['reservation_id' => $reservation->getId(), 'user_email' => $userEmail]);
                if ($userEmail) {
                    $fromAddress = $this->getParameter('mailer_from') ?? 'no-reply@stayzy.local';
                    $alwaysTo = $this->getParameter('mailer_always_to');

                    $email = (new TemplatedEmail())
                        ->from(new Address($fromAddress, 'Stayzy'))
                        // If an always-to address is configured, send the email to it
                        // and add the reservation user as BCC so they also receive a copy.
                        ->subject('Nouvelle réservation confirmée')
                        ->htmlTemplate('emails/reservation_confirmed.html.twig')
                        ->context(['reservation' => $reservation]);

                    if ($alwaysTo) {
                        $email->to($alwaysTo);
                        if ($userEmail) {
                            $email->addBcc($userEmail);
                        }
                    } else {
                        // Fallback: send to reservation user only
                        $email->to($userEmail);
                    }

                    $logger->info('Sending confirmation email now', ['reservation_id' => $reservation->getId(), 'to' => $alwaysTo ?? $userEmail]);

                    $mailer->send($email);
                }
            } catch (\Throwable $e) {
                // Ne pas bloquer la confirmation si l'envoi échoue — on logge l'erreur
                $logger->error('Échec envoi email réservation confirmée', ['exception' => $e, 'reservation_id' => $reservation->getId()]);
            }
        }

        $em->flush();
        $this->addFlash('success', "Statut de la réservation mis à jour !");
        return $this->redirectToRoute('front_reservation_proprietaire_list');
    }

    // ══════════════════════════════════════
    // DASHBOARD PROPRIÉTAIRE — avec IA
    // ══════════════════════════════════════
    #[Route('/proprietaire/dashboard', name: 'proprietaire_dashboard')]
    public function dashboard(
        EntityManagerInterface $em,
        HttpClientInterface $httpClient
    ): Response {
        $allReservations     = $em->getRepository(Reservation::class)->findAll();
        $nbEnAttente         = 0;
        $nbConfirmee         = 0;
        $nbAnnulee           = 0;
        $reservationsParMois = array_fill(0, 12, 0);
        $now                 = new \DateTime();

        foreach ($allReservations as $r) {
            match ($r->getStatus()) {
                'EN_ATTENTE' => $nbEnAttente++,
                'CONFIRMÉE'  => $nbConfirmee++,
                'ANNULÉE'    => $nbAnnulee++,
                default      => null
            };
            $moisDiff = (int)(($now->getTimestamp() - $r->getDateDebut()->getTimestamp()) / (30 * 24 * 3600));
            if ($moisDiff >= 0 && $moisDiff < 12) {
                $reservationsParMois[11 - $moisDiff]++;
            }
        }

        $allCommandes     = $em->getRepository(Commande::class)->findAll();
        $nbCommandesTotal = count($allCommandes);
        $totalRevenu      = 0;
        $commandesParMois = array_fill(0, 12, 0);

        foreach ($allCommandes as $c) {
            $totalRevenu += $c->getPrixTotal() ?? 0;
            if ($c->getDateTransaction()) {
                $moisDiff = (int)(($now->getTimestamp() - $c->getDateTransaction()->getTimestamp()) / (30 * 24 * 3600));
                if ($moisDiff >= 0 && $moisDiff < 12) {
                    $commandesParMois[11 - $moisDiff]++;
                }
            }
        }

        $logements           = $em->getRepository(Logement::class)->findAll();
        $classementLogements = [];

        foreach ($logements as $logement) {
            $reservations = $em->getRepository(Reservation::class)->findBy(['logement' => $logement]);
            $confirmed    = count(array_filter($reservations, fn($r) => $r->getStatus() === 'CONFIRMÉE'));
            $total        = count($reservations);

            $classementLogements[] = [
                'titre'     => $logement->getTitre(),
                'total'     => $total,
                'confirme'  => $confirmed,
                'annule'    => count(array_filter($reservations, fn($r) => $r->getStatus() === 'ANNULÉE')),
                'tauxOccup' => $total > 0 ? round(($confirmed / $total) * 100) : 0
            ];
        }

        usort($classementLogements, fn($a, $b) => $b['total'] <=> $a['total']);

        $iaInsights = $this->getIAInsights($httpClient, $classementLogements, $reservationsParMois);

        $labelsMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $labelsMois[] = (new \DateTime("-$i months"))->format('M Y');
        }

        return $this->render('backOffice/reservation/Admin/dashboard.html.twig', [
            'nbEnAttente'         => $nbEnAttente,
            'nbConfirmee'         => $nbConfirmee,
            'nbAnnulee'           => $nbAnnulee,
            'totalReservations'   => count($allReservations),
            'reservationsParMois' => $reservationsParMois,
            'nbCommandesTotal'    => $nbCommandesTotal,
            'totalRevenu'         => $totalRevenu,
            'commandesParMois'    => $commandesParMois,
            'classementLogements' => $classementLogements,
            'labelsMois'          => $labelsMois,
            'iaInsights'          => $iaInsights,
        ]);
    }

    private function getIAInsights(HttpClientInterface $httpClient, array $classementLogements, array $reservationsParMois): array
    {
        try {
            $response = $httpClient->request('POST', 'http://127.0.0.1:5000/insights', [
                'json'    => ['classement' => $classementLogements, 'reservations_mois' => $reservationsParMois],
                'timeout' => 3
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return $this->fallbackInsights($classementLogements, $reservationsParMois);
        }
    }

    private function fallbackInsights(array $classement, array $parMois): array
    {
        $topLogement = !empty($classement) ? $classement[0]['titre'] : 'N/A';
        $sousPerf    = array_values(array_map(fn($l) => $l['titre'], array_filter($classement, fn($l) => $l['tauxOccup'] < 30 && $l['total'] > 0)));
        $minIdx      = array_search(min($parMois), $parMois);
        $moisCreux   = (new \DateTime("-" . (11 - $minIdx) . " months"))->format('F Y');

        return [
            'top_logement'     => $topLogement,
            'sous_performants' => $sousPerf,
            'periode_creuse'   => $moisCreux,
            'tendance'         => '➡️ Stable',
            'tendance_cls'     => 'warning',
            'prediction_msg'   => "Période creuse prévue en $moisCreux — pensez à lancer des promotions."
        ];
    }
}