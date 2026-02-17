<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reclamation')]
class ReclamationController extends AbstractController
{

    /* =========================================================
     * ==================== CLIENT FRONT =======================
     * ========================================================= */

    // ➜ Ajouter réclamation
    #[Route('/client/new', name: 'client_reclamation_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();

        // client id = 1
        $client = $em->getRepository(User::class)->find(1);
        $reclamation->setUser($client);
        $reclamation->setStatut('EN_ATTENTE');

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($reclamation);
            $em->flush();

            return $this->render('frontOffice/reclamation/client/show.html.twig', [
            'reclamation' => $reclamation
        ]);
    }
    

        return $this->render('frontOffice/reclamation/client/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ➜ Mes réclamations + réponses
    #[Route('/client/mes-reclamations', name: 'client_mes_reclamations')]
    public function mesReclamations(ReclamationRepository $repo): Response
    {
        $reclamations = $repo->createQueryBuilder('r')
            ->leftJoin('r.reponses', 'rep')->addSelect('rep')
            ->where('r.user = 1')
            ->orderBy('r.id', 'DESC')
            ->getQuery()->getResult();

        return $this->render('frontOffice/reclamation/client/mes_reclamations.html.twig', [
            'reclamations' => $reclamations
        ]);
    }

    // ➜ Modifier (si EN_ATTENTE)
    #[Route('/client/edit/{id}', name: 'client_reclamation_edit')]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($reclamation->getStatut() !== 'EN_ATTENTE')
            return $this->redirectToRoute('client_mes_reclamations');

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('client_mes_reclamations');
        }

        return $this->render('frontOffice/reclamation/client/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ➜ Delete (si EN_ATTENTE)
    #[Route('/client/delete/{id}', name: 'client_reclamation_delete')]
    public function delete(Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($reclamation->getStatut() === 'EN_ATTENTE') {
            $em->remove($reclamation);
            $em->flush();
        }

        return $this->redirectToRoute('client_mes_reclamations');
    }


    /* =========================================================
     * ================= PROPRIETAIRE BACK =====================
     * ========================================================= */

    // ➜ Réclamations reçues
    #[Route('/proprietaire', name: 'prop_reclamations')]
    public function reclamationsProprietaire(ReclamationRepository $repo): Response
    {
        $reclamations = $repo->createQueryBuilder('r')
            ->leftJoin('r.reponses', 'rep')->addSelect('rep')
            ->orderBy('r.id', 'DESC')
            ->getQuery()->getResult();

        return $this->render('backOffice/reclamation/proprietaire/index.html.twig', [
            'reclamations' => $reclamations
        ]);
    }


    /* =========================================================
     * ====================== ADMIN =============================
     * ========================================================= */

    // ➜ Supervision globale
   #[Route('/admin', name: 'admin_reclamations')]
public function admin(Request $request, ReclamationRepository $repo): Response
{
    $sujet = $request->query->get('sujet');
    $statut = $request->query->get('statut');
    $tri = $request->query->get('tri', 'DESC');

    $qb = $repo->createQueryBuilder('r')
        ->leftJoin('r.reponses', 'rep')->addSelect('rep');

    // 🔎 recherche sujet
    if ($sujet) {
        $qb->andWhere('r.sujet LIKE :sujet')
           ->setParameter('sujet', '%'.$sujet.'%');
    }

    // 🎯 filtre statut
    if ($statut) {
        $qb->andWhere('r.statut = :statut')
           ->setParameter('statut', $statut);
    }

    // 🔼🔽 tri
    $qb->orderBy('r.id', $tri);

    $reclamations = $qb->getQuery()->getResult();

    return $this->render('backOffice/reclamation/admin/index.html.twig', [
        'reclamations' => $reclamations
    ]);
}

}
