<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Entity\User;

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
     * ===== PROPRIETAIRE + ADMIN AJOUTER REPONSE (MAX 1) ======
     * ========================================================= */

    #[Route('/new/{id}', name: 'reponse_new')]
    public function new(Reclamation $reclamation, Request $request, EntityManagerInterface $em): Response
    {
        // max 1 réponse
        if (!$reclamation->getReponses()->isEmpty()) {
            return $this->redirectToRoute('prop_reclamations');
        }

        $reponse = new Reponse();
        $reponse->setReclamation($reclamation);

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // update statut
            $reclamation->setStatut('TRAITEE');

            $em->persist($reponse);
            $em->flush();

            return $this->redirectToRoute('prop_reclamations');
        }

        // ⚠️ template path corrigé
        return $this->render('backOffice/reponse/proprietaire/new.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation
        ]);
    }


    /* =========================================================
     * ================== EDIT REPONSE =========================
     * ========================================================= */

    #[Route('/edit/{id}', name: 'reponse_edit')]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            return $this->redirectToRoute('prop_reclamations');
        }

        // ⚠️ لازم نبعث reponse للـ twig
        return $this->render('backOffice/reponse/proprietaire/edit.html.twig', [
            'form' => $form->createView(),
            'reponse' => $reponse
        ]);
    }


    /* =========================================================
     * ================= DELETE REPONSE ========================
     * ========================================================= */

    #[Route('/delete/{id}', name: 'reponse_delete')]
    public function delete(Reponse $reponse, EntityManagerInterface $em): Response
    {
        $em->remove($reponse);
        $em->flush();

        return $this->redirectToRoute('prop_reclamations');
    }


    /* =========================================================
     * ================= LISTE REPONSES ========================
     * ========================================================= */

#[Route('/liste', name: 'prop_reponses')]
public function reponsesProprietaire(Request $request, ReponseRepository $repo): Response
{
    $idReclamation = $request->query->get('id_reclamation');

    if ($idReclamation) {
        $reponses = $repo->findBy(['reclamation' => $idReclamation]);
    } else {
        $reponses = $repo->findAll();
    }

    return $this->render('backOffice/reponse/proprietaire/liste.html.twig', [
        'reponses' => $reponses
    ]);
}




}
