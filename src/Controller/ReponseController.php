<?php

namespace App\Controller;

use App\Entity\Reponse;
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
    // 🔹 LISTE
    #[Route('/', name: 'reponse_index', methods: ['GET'])]
public function index(Request $request, ReponseRepository $repository): Response
{
    $reclamationId = $request->query->get('reclamation_id');

    $qb = $repository->createQueryBuilder('r')
                     ->leftJoin('r.reclamation', 'rec')
                     ->addSelect('rec');

    if ($reclamationId) {
        $qb->andWhere('rec.id = :rid')
           ->setParameter('rid', $reclamationId);
    }

    $reponses = $qb->orderBy('r.id', 'DESC')
                   ->getQuery()
                   ->getResult();

    return $this->render('backOffice/reponse/index.html.twig', [
        'reponses' => $reponses,
    ]);
}

    // 🔹 AJOUT
 #[Route('/new/{id}', name: 'reponse_new')]
public function new(
    Reclamation $reclamation,
    Request $request,
    EntityManagerInterface $em
): Response {

    // 🚫 منع double réponse
    if (!$reclamation->getReponses()->isEmpty()) {
        $this->addFlash('warning', 'Cette réclamation est déjà traitée.');

        return $this->redirectToRoute('reclamation_show', [
            'id' => $reclamation->getId()
        ]);
    }

    $reponse = new Reponse();
    $reponse->setDateReponse(new \DateTime());
    $reponse->setReclamation($reclamation); // 🔥 مهم

    $form = $this->createForm(ReponseType::class, $reponse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 تغيير statut
        $reclamation->setStatut('TRAITEE');

        // 🔥 persist صحيح
        $em->persist($reponse);
        $em->flush();

        $this->addFlash('success', 'Réponse ajoutée avec succès.');

        return $this->redirectToRoute('reclamation_show', [
            'id' => $reclamation->getId()
        ]);
    }

    return $this->render('backOffice/reponse/new.html.twig', [
        'form' => $form->createView(),
        'reclamation' => $reclamation
    ]);
}


    // 🔹 EDIT
    #[Route('/{id}/edit', name: 'reponse_edit', methods: ['GET','POST'])]
    public function edit(
        Request $request,
        Reponse $reponse,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            return $this->redirectToRoute('reponse_index');
        }

        return $this->render('backOffice/reponse/edit.html.twig', [
            'form' => $form->createView(),
            'reponse' => $reponse
        ]);
    }

    // 🔹 DELETE
    #[Route('/{id}', name: 'reponse_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Reponse $reponse,
        EntityManagerInterface $em
    ): Response {

        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $em->remove($reponse);
            $em->flush();
        }

        return $this->redirectToRoute('reponse_index');
    }
}
