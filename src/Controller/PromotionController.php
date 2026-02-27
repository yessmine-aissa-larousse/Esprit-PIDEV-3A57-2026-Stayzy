<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Promotion;
use App\Form\PromotionType;
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
    public function new(int $logementId, Request $request, EntityManagerInterface $em): Response
    {
        $logement = $this->getLogementSecurise($logementId, $em);

        // Bloquer si promo en cours
        $promoEnCours = $em->getRepository(Promotion::class)
            ->findPromoActiveByLogement($logement->getId());

        if ($promoEnCours) {
            $this->addFlash('warning', '⚠️ Une promotion est déjà en cours sur ce logement. Modifiez-la ou désactivez-la d\'abord.');
            return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
        }

        $promotion = new Promotion();
        $promotion->setLogement($logement);

        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($promotion);
            $em->flush();
            $this->addFlash('success', '🎉 Promotion "' . $promotion->getTitre() . '" créée avec succès !');
            return $this->redirectToRoute('promotion_list', ['logementId' => $logementId]);
        }

        return $this->render('frontOffice/promotion/form.html.twig', [
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

        return $this->render('frontOffice/promotion/form.html.twig', [
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
        /** @var \App\Entity\Utilisateur $user */
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
        /** @var \App\Entity\Utilisateur $user */
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
                // Ignorer les logements avec promo en cours
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

        // Calcul prix moyen pour le JS (prixMoyen doit être une var PHP, pas Twig)
        $prixMoyen = 0;
        if (!empty($logements)) {
            foreach ($logements as $l) {
                $prixMoyen += $l->getPrix();
            }
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
}