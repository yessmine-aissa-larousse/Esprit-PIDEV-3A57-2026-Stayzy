<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PROPRIETAIRE')]
final class PromotionController extends AbstractController
{
    // =========================================================================
    // ██ PAR LOGEMENT — prefix /proprietaire/logement/{logementId}/promotions
    // =========================================================================

    #[Route('/proprietaire/logement/{logementId}/promotions', name: 'promotion_list')]
    public function list(int $logementId, EntityManagerInterface $em): Response
    {
        $logement = $this->getLogementSecurise($logementId, $em);

        $promotions = $em->getRepository(Promotion::class)
            ->createQueryBuilder('p')
            ->where('p.logement = :logement')
            ->setParameter('logement', $logement)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $promoEnCoursExiste = false;
        foreach ($promotions as $promo) {
            if ($promo->isEnCours()) { $promoEnCoursExiste = true; break; }
        }

        return $this->render('frontOffice/promotion/list.html.twig', [
            'logement'           => $logement,
            'promotions'         => $promotions,
            'promoEnCoursExiste' => $promoEnCoursExiste,
        ]);
    }

    // -------------------------------------------------------------------------
    #[Route('/proprietaire/logement/{logementId}/promotions/new', name: 'promotion_new')]
public function new(int $logementId, Request $request, EntityManagerInterface $em, SmsService $smsService): Response
{
    $logement = $this->getLogementSecurise($logementId, $em);

    $promoEnCours = $em->getRepository(Promotion::class)
        ->findPromoActiveByLogement($logement->getId());

    if ($promoEnCours) {
        $this->addFlash('warning', '⚠️ Une promotion est déjà en cours sur ce logement.');
        return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
    }

    $promotion = new Promotion();
    $promotion->setLogement($logement);

    $form = $this->createForm(PromotionType::class, $promotion);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($promotion);
        $em->flush();

        // ✅ Envoi SMS avec debug visible
        $this->envoyerSmsPromotion($logement, $promotion, $smsService);

        $this->addFlash('success', '🎉 Promotion "' . $promotion->getTitre() . '" créée avec succès !');
        return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
    }

    return $this->render('frontOffice/promotion/add.html.twig', [
        'form'      => $form->createView(),
        'logement'  => $logement,
        'promotion' => null,
        'titre'     => 'Créer une promotion',
    ]);
}

    // -------------------------------------------------------------------------
    #[Route('/proprietaire/logement/{logementId}/promotions/{id}/edit', name: 'promotion_edit')]
    public function edit(int $logementId, int $id, Request $request, EntityManagerInterface $em): Response
    {
        $logement  = $this->getLogementSecurise($logementId, $em);
        $promotion = $em->getRepository(Promotion::class)->find($id);

        if (!$promotion || $promotion->getLogement() !== $logement) {
            $this->addFlash('error', 'Promotion introuvable.');
            return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
        }

        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '✅ Promotion modifiée avec succès !');
            return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
        }

        return $this->render('frontOffice/promotion/add.html.twig', [
            'form'      => $form->createView(),
            'logement'  => $logement,
            'promotion' => $promotion,
            'titre'     => 'Modifier la promotion',
        ]);
    }

    // -------------------------------------------------------------------------
    #[Route('/proprietaire/logement/{logementId}/promotions/{id}/toggle', name: 'promotion_toggle', methods: ['POST'])]
    public function toggle(int $logementId, int $id, EntityManagerInterface $em): Response
    {
        $logement  = $this->getLogementSecurise($logementId, $em);
        $promotion = $em->getRepository(Promotion::class)->find($id);

        if (!$promotion || $promotion->getLogement() !== $logement) {
            $this->addFlash('error', 'Promotion introuvable.');
            return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
        }

        $promotion->setActive(!$promotion->isActive());
        $em->flush();

        $etat = $promotion->isActive() ? 'activée ✅' : 'désactivée ⏸️';
        $this->addFlash('success', 'Promotion ' . $etat);
        return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
    }

    // -------------------------------------------------------------------------
    #[Route('/proprietaire/logement/{logementId}/promotions/{id}/delete', name: 'promotion_delete', methods: ['POST'])]
    public function delete(int $logementId, int $id, EntityManagerInterface $em): Response
    {
        $logement  = $this->getLogementSecurise($logementId, $em);
        $promotion = $em->getRepository(Promotion::class)->find($id);

        if (!$promotion || $promotion->getLogement() !== $logement) {
            $this->addFlash('error', 'Promotion introuvable.');
            return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
        }

        $em->remove($promotion);
        $em->flush();
        $this->addFlash('success', '🗑️ Promotion supprimée.');
        return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
    }

    // =========================================================================
    // ██ GLOBALES — prefix /proprietaire/promotions
    // =========================================================================

    #[Route('/proprietaire/promotions', name: 'promotion_globale_list')]
    public function globaleList(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $logements = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->where('l.proprietaire = :user')
            ->setParameter('user', $user)
            ->orderBy('l.titre', 'ASC')
            ->getQuery()
            ->getResult();

        $promotions = [];
        if (!empty($logements)) {
            $promotions = $em->getRepository(Promotion::class)
                ->createQueryBuilder('p')
                ->join('p.logement', 'l')
                ->where('l.proprietaire = :user')
                ->setParameter('user', $user)
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        $nbActives  = count(array_filter($promotions, fn($p) => $p->isEnCours()));
        $nbFutures  = count(array_filter($promotions, fn($p) => $p->isFuture()));
        $nbExpirees = count(array_filter($promotions, fn($p) => $p->isExpiree()));

        return $this->render('frontOffice/promotion/globale_list.html.twig', [
            'logements'  => $logements,
            'promotions' => $promotions,
            'nbActives'  => $nbActives,
            'nbFutures'  => $nbFutures,
            'nbExpirees' => $nbExpirees,
        ]);
    }

    // -------------------------------------------------------------------------
    #[Route('/proprietaire/promotions/appliquer-tous', name: 'promotion_globale_appliquer_tous')]
    public function appliquerTous(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $logements = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->where('l.proprietaire = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        if (empty($logements)) {
            $this->addFlash('warning', 'Vous n\'avez aucun logement publié.');
            return $this->redirectToRoute('promotion_globale_list');
        }

        $promoTemplate = new Promotion();
        $form = $this->createForm(PromotionType::class, $promoTemplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nbAppliques = 0;
            $nbIgnores   = 0;

            foreach ($logements as $logement) {
                $promoEnCours = $em->getRepository(Promotion::class)
                    ->findPromoActiveByLogement($logement->getId());

                if ($promoEnCours) { $nbIgnores++; continue; }

                $nouvellePromo = new Promotion();
                $nouvellePromo->setLogement($logement);
                $nouvellePromo->setTitre($promoTemplate->getTitre());
                $nouvellePromo->setPourcentage($promoTemplate->getPourcentage());
                $nouvellePromo->setDateDebut($promoTemplate->getDateDebut());
                $nouvellePromo->setDateFin($promoTemplate->getDateFin());
                $nouvellePromo->setCodePromo($promoTemplate->getCodePromo());
                $nouvellePromo->setActive($promoTemplate->isActive());

                $em->persist($nouvellePromo);
                $nbAppliques++;
            }

            $em->flush();

            $msg = '🎉 Promotion appliquée sur ' . $nbAppliques . ' logement(s) !';
            if ($nbIgnores > 0) {
                $msg .= ' (' . $nbIgnores . ' ignoré(s) car déjà en promotion.)';
            }
            $this->addFlash('success', $msg);
            return $this->redirectToRoute('promotion_globale_list');
        }

        $prixMoyen = 0;
        if (!empty($logements)) {
            foreach ($logements as $l) { $prixMoyen += $l->getPrix(); }
            $prixMoyen = round($prixMoyen / count($logements), 2);
        }

        return $this->render('frontOffice/promotion/globale_form.html.twig', [
            'form'      => $form->createView(),
            'logements' => $logements,
            'prixMoyen' => $prixMoyen,
        ]);
    }

    // -------------------------------------------------------------------------
    #[Route('/proprietaire/promotions/{id}/toggle', name: 'promotion_globale_toggle', methods: ['POST'])]
    public function globaleToggle(int $id, EntityManagerInterface $em): Response
    {
        $promotion = $this->getPromoSecurise($id, $em);
        $promotion->setActive(!$promotion->isActive());
        $em->flush();

        $etat = $promotion->isActive() ? 'activée ✅' : 'désactivée ⏸️';
        $this->addFlash('success', 'Promotion ' . $etat);
        return $this->redirectToRoute('promotion_globale_list');
    }

    // -------------------------------------------------------------------------
    #[Route('/proprietaire/promotions/{id}/delete', name: 'promotion_globale_delete', methods: ['POST'])]
    public function globaleDelete(int $id, EntityManagerInterface $em): Response
    {
        $promotion = $this->getPromoSecurise($id, $em);
        $em->remove($promotion);
        $em->flush();
        $this->addFlash('success', '🗑️ Promotion supprimée.');
        return $this->redirectToRoute('promotion_globale_list');
    }

    // =========================================================================
    // ██ HELPERS PRIVÉS
    // =========================================================================

    private function getLogementSecurise(int $logementId, EntityManagerInterface $em): Logement
    {
        $logement = $em->getRepository(Logement::class)->find($logementId);
        if (!$logement) throw $this->createNotFoundException('Logement introuvable.');
        if ($logement->getProprietaire() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès non autorisé.');
        }
        return $logement;
    }

    private function getPromoSecurise(int $id, EntityManagerInterface $em): Promotion
    {
        $promotion = $em->getRepository(Promotion::class)->find($id);
        if (!$promotion) throw $this->createNotFoundException('Promotion introuvable.');
        if ($promotion->getLogement()->getProprietaire() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès non autorisé.');
        }
        return $promotion;
    }

    // -------------------------------------------------------------------------
    // ✅ NOUVEAU : Envoi SMS aux clients qui ont le logement en favori
    // -------------------------------------------------------------------------
    private function envoyerSmsPromotion(
    Logement $logement,
    Promotion $promotion,
    SmsService $smsService
): void {
    dump('=== DEBUT envoyerSmsPromotion ===');
    dump('Promotion active ?', $promotion->isActive());

    if (!$promotion->isActive()) {
        dump('❌ Promotion inactive → SMS non envoyé');
        return;
    }

    $clients = $logement->getUtilisateursFavoris();
    dump('Nombre de clients en favori:', count($clients));

    if (count($clients) === 0) {
        dump('❌ Aucun client n\'a ce logement en favori');
        return;
    }

    foreach ($clients as $client) {
        dump('Client trouvé:', $client->getEmail(), '| Tel:', $client->getTel());

        $tel = $client->getTel();
        if (!$tel) {
            dump('❌ Client sans numéro de téléphone → ignoré');
            continue;
        }

        $telFormate = $this->formaterTelephone($tel);
        dump('Téléphone formaté:', $telFormate);

        if (!$telFormate) {
            dump('❌ Téléphone non formaté correctement → ignoré');
            continue;
        }

        $message = sprintf(
            'Bonne nouvelle ! Le logement "%s" que vous avez en favori beneficie d\'une promotion de %d%% ! Profitez-en sur Stayzy.',
            $logement->getTitre(),
            $promotion->getPourcentage()
        );

        dump('📤 Envoi SMS vers:', $telFormate);

        // ⚠️ On laisse l'exception remonter pour voir l'erreur
        $smsService->sendSms($telFormate, $message);

        dump('✅ SMS envoyé avec succès à:', $telFormate);
    }

    dump('=== FIN envoyerSmsPromotion ===');
}
    private function formaterTelephone(string $tel): ?string
    {
        // Supprime espaces, tirets, parenthèses
        $tel = preg_replace('/[\s\-\(\)]/', '', $tel);

        // Déjà au format international
        if (str_starts_with($tel, '+')) return $tel;

        // Numéro tunisien sans indicatif (8 chiffres)
        if (strlen($tel) === 8) return '+216' . $tel;

        return null;
    }


#[Route('/test-sms', name: 'test_sms')]
public function testSms(SmsService $smsService): Response
{
    try {
        $smsService->sendSms('+21656150403', 'Test SMS Stayzy fonctionne !');
        return new Response('✅ SMS envoyé avec succès !');
    } catch (\Exception $e) {
        return new Response('❌ Erreur : ' . $e->getMessage());
    }
}
#[Route('/test-sms-debug', name: 'test_sms_debug')]
public function testSmsDebug(): Response
{
    $accountSid = 'AC6aeb096f449eec5f93cb3807691dca60';
    $authToken  = '9d63d0e3cc40d2f3398d738f065b9bef';
    $from       = '+18654010600';
    $to         = '+21656150403';
    $message    = 'Test Stayzy debug';

    $url  = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
    $data = ['From' => $from, 'To' => $to, 'Body' => $message];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // ✅ Désactive SSL temporairement pour tester
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    $json = json_decode($response, true);

    return new Response('<pre>' . 
        'Error curl: ' . $error . "\n" .
        json_encode($json, JSON_PRETTY_PRINT) 
    . '</pre>');
}
}