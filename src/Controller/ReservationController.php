<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Form\ReservationBackType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation', name: 'front_reservation_')]
class ReservationController extends AbstractController
{
    //CLIENT
   

    //Créer une nouvelle réservation
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

            $nbNuits = $reservation->getDateDebut()->diff($reservation->getDateFin())->days;
            $prixTotal = $nbNuits * $logement->getPrix() * $reservation->getNombrePersonnes();
            $reservation->setPrixTotal($prixTotal);
            $reservation->setStatus('EN_ATTENTE');

            // utilisateur test (à remplacer par $this->getUser())
            $user = $em->getRepository(User::class)->find(1);
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

    // Liste des réservations du client
    #[Route('/list', name: 'list')]
    public function list(EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find(1); // utilisateur test
        $reservations = $em->getRepository(Reservation::class)->findBy(['user' => $user]);

        return $this->render('frontOffice/reservation/client/list.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    // Modifier une réservation (client)
    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find(1); // utilisateur test
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ReservationBackType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nbNuits = $reservation->getDateDebut()->diff($reservation->getDateFin())->days;
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

    // Annuler une réservation (client)
    #[Route('/cancel/{id}', name: 'cancel')]
    public function cancel(Reservation $reservation, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find(1); // utilisateur test
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $reservation->setStatus('ANNULÉE');
        $em->flush();

        $this->addFlash('success', 'Réservation annulée avec succès !');
        return $this->redirectToRoute('front_reservation_list');
    }


  //PROPRIÉTAIRE (Back Office)
   

    // Liste réservations propriétaire
    #[Route('/proprietaire', name: 'proprietaire_list')]
    public function proprietaireList(Request $request, EntityManagerInterface $em): Response
    {
        $logementId = $request->query->get('logement');
        $logements = $em->getRepository(Logement::class)->findAll();

        if ($logementId) {
            $reservations = $em->getRepository(Reservation::class)->findBy(['logement' => $logementId]);
        } else {
            $reservations = $em->getRepository(Reservation::class)->findAll();
        }

        return $this->render('frontOffice/reservation/proprietaire/list.html.twig', [
            'logements' => $logements,
            'reservations' => $reservations,
            'selectedLogement' => $logementId
        ]);
    }

    //Confirmer ou Annuler une réservation (directement depuis la liste)
    #[Route('/proprietaire/action/{id}/{status}', name: 'proprietaire_action')]
    public function proprietaireAction(Reservation $reservation, string $status, EntityManagerInterface $em): Response
    {
        // Mettre à jour la réservation choisie
        $reservation->setStatus($status);

        // Si CONFIRMÉE → annuler les autres réservations en attente qui se chevauchent
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
        }
        // Après $em->flush(); juste avant le flash message :
if ($status === 'CONFIRMÉE') {
    $commandeController = new \App\Controller\CommandeController();
    $commandeController->createFromReservation($reservation, $em);
}


        $em->flush();
        $this->addFlash('success', "Statut de la réservation mis à jour !");
        return $this->redirectToRoute('front_reservation_proprietaire_list');
    }


   #[Route('/proprietaire/dashboard', name: 'proprietaire_dashboard')]
public function dashboard(EntityManagerInterface $em): Response
{
    // Récupérer tous les logements (test)
    $logements = $em->getRepository(Logement::class)->findAll();

    $stats = [];

    foreach ($logements as $logement) {
        // Récupérer toutes les réservations pour ce logement
        $reservations = $em->getRepository(Reservation::class)->findBy(['logement' => $logement]);

        $nbEnAttente = 0;
        $nbConfirme = 0;
        $nbAnnule = 0;

        foreach ($reservations as $r) {
            switch ($r->getStatus()) {
                case 'EN_ATTENTE':
                    $nbEnAttente++;
                    break;
                case 'CONFIRMÉE':
                    $nbConfirme++;
                    break;
                case 'ANNULÉE':
                    $nbAnnule++;
                    break;
            }
        }

        $stats[] = [
            'logement' => $logement->getTitre(),
            'en_attente' => $nbEnAttente,
            'confirme' => $nbConfirme,
            'annule' => $nbAnnule,
        ];
    }

    return $this->render('frontOffice/reservation/proprietaire/dashboard.html.twig', [
        'stats' => $stats,
    ]);
}


}
