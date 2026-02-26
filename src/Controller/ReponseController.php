<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Service\ReponseNotifier;
use App\Repository\ReclamationRepository;
use App\Service\ReclamationIntelligenceService;
use App\Entity\Reclamation;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reponse')]
class ReponseController extends AbstractController
{

    /* =========================================================
     * ================== ADMIN AJOUTER REPONSE =================
     * ========================================================= */

   #[Route('/admin/new/{id}', name: 'admin_reponse_new')]
public function newAdmin(
    Reclamation $reclamation,
    Request $request,
    EntityManagerInterface $em,
    ReclamationIntelligenceService $intelligenceService,
        ReponseNotifier $notifier

): Response {

    // 🔒 Vérification orientation
    if ($intelligenceService->detectTargetRole($reclamation) !== 'ADMIN') {
        throw $this->createAccessDeniedException(
            'Cette réclamation n’est pas destinée à l’administration.'
        );
    }

    // 🔒 Une seule réponse
    if (!$reclamation->getReponses()->isEmpty()) {
        return $this->redirectToRoute('admin_reclamations');
    }

$reponse = new Reponse();
$reponse->setReclamation($reclamation);

// 🔥 Smart suggestion
$suggestion = $intelligenceService->generateSmartReply($reclamation);
$reponse->setContenu($suggestion);    $reponse->setReclamation($reclamation);

    $form = $this->createForm(ReponseType::class, $reponse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        $reclamation->setStatut('TRAITEE');

        $em->persist($reponse);
        $em->flush();

        $notifier->notifyReponse();

        return $this->redirectToRoute('admin_reclamations');
    }

    return $this->render('backOffice/reponse/admin/new.html.twig', [
        'form' => $form->createView(),
        'reclamation' => $reclamation
    ]);
}


    /* =========================================================
     * ============== PROPRIETAIRE AJOUTER REPONSE ==============
     * ========================================================= */

    #[Route('/new/{id}', name: 'prop_reponse_new')]
    public function newProprietaire(
        Reclamation $reclamation,
        Request $request,
        EntityManagerInterface $em,
            ReclamationIntelligenceService $intelligenceService,
                ReponseNotifier $notifier


    ): Response {

        if (!$reclamation->getReponses()->isEmpty()) {
            return $this->redirectToRoute('prop_reclamations');
        }

$reponse = new Reponse();
$reponse->setReclamation($reclamation);

// 🔥 Smart suggestion
$suggestion = $intelligenceService->generateSmartReply($reclamation);
$reponse->setContenu($suggestion);        $reponse->setReclamation($reclamation);

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $reclamation->setStatut('TRAITEE');

            $em->persist($reponse);
            $em->flush();

                    $notifier->notifyReponse();


            return $this->redirectToRoute('prop_reclamations');
        }

        return $this->render('frontOffice/reponse/proprietaire/new.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation
        ]);
    }


    /* =========================================================
     * ================= ADMIN EDIT =============================
     * ========================================================= */

  #[Route('/admin/edit/{id}', name: 'admin_reponse_edit')]
public function editAdmin(
    Request $request,
    Reponse $reponse,
    EntityManagerInterface $em,
    ReclamationIntelligenceService $intelligenceService
): Response {

    $reclamation = $reponse->getReclamation();

    // 🔒 إذا موش ADMIN نرجعو للقائمة بصمت
    if ($intelligenceService->detectTargetRole($reclamation) !== 'ADMIN') {
        return $this->redirectToRoute('admin_reclamations');
    }

    $form = $this->createForm(ReponseType::class, $reponse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        return $this->redirectToRoute('admin_reclamations');
    }

    return $this->render('backOffice/reponse/admin/edit.html.twig', [
        'form' => $form->createView(),
        'reponse' => $reponse
    ]);
}


    /* =========================================================
     * ================= PROPRIETAIRE EDIT ======================
     * ========================================================= */

    #[Route('/edit/{id}', name: 'prop_reponse_edit')]
    public function editProprietaire(
        Request $request,
        Reponse $reponse,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('prop_reclamations');
        }

        return $this->render('frontOffice/reponse/proprietaire/edit.html.twig', [
            'form' => $form->createView(),
            'reponse' => $reponse
        ]);
    }


    /* =========================================================
     * ================= DELETE ================================
     * ========================================================= */

    #[Route('/admin/delete/{id}', name: 'admin_reponse_delete')]
    public function deleteAdmin(
        Reponse $reponse,
        EntityManagerInterface $em
    ): Response {

        $em->remove($reponse);
        $em->flush();

        return $this->redirectToRoute('admin_reclamations');
    }

    #[Route('/delete/{id}', name: 'prop_reponse_delete')]
    public function deleteProprietaire(
        Reponse $reponse,
        EntityManagerInterface $em
    ): Response {

        $em->remove($reponse);
        $em->flush();

        return $this->redirectToRoute('prop_reclamations');
    }


    #[Route('/liste', name: 'prop_reponses')]
public function reponsesProprietaire(
    Request $request,
    ReponseRepository $repo,
    ReclamationRepository $reclamationRepo,
    ReclamationIntelligenceService $intelligenceService
): Response {

    $idReclamation = $request->query->get('id_reclamation');

    // 1️⃣ نجيبوا réclamations اللي target متاعهم PROPRIETAIRE
    $allReclamations = $reclamationRepo->findAll();

    $reclamationsProp = [];

    foreach ($allReclamations as $reclamation) {
        $target = $intelligenceService->detectTargetRole($reclamation);

        if ($target === 'PROPRIETAIRE') {
            $reclamationsProp[] = $reclamation;
        }
    }

    // 2️⃣ نجيبوا réponses اللي مربوطين بهالرéclamations
    $qb = $repo->createQueryBuilder('r')
        ->join('r.reclamation', 'rec')
        ->where('rec IN (:recs)')
        ->setParameter('recs', $reclamationsProp)
        ->orderBy('r.id', 'DESC');

    if ($idReclamation) {
        $qb->andWhere('rec.id = :idRec')
           ->setParameter('idRec', $idReclamation);
    }

    $reponses = $qb->getQuery()->getResult();

    return $this->render('frontOffice/reponse/proprietaire/liste.html.twig', [
        'reponses' => $reponses
    ]);
}
}