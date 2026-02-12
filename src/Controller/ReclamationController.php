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
  #[Route('/', name: 'reclamation_index')]
public function index(Request $request, ReclamationRepository $repo): Response
{
    $search = $request->query->get('search');
    $statut = $request->query->get('statut');

    $reclamations = $repo->createQueryBuilder('r');

    if ($search) {
        $reclamations->andWhere('r.sujet LIKE :search')
                     ->setParameter('search', '%' . $search . '%');
    }

    if ($statut) {
        $reclamations->andWhere('r.statut = :statut')
                     ->setParameter('statut', $statut);
    }

    $reclamations = $reclamations
        ->orderBy('r.id', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('backOffice/reclamation/index.html.twig', [
        'reclamations' => $reclamations,
    ]);
}



    #[Route('/new', name: 'reclamation_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //  USER TEST ID = 1
            $user = $em->getRepository(User::class)->find(1);
            $reclamation->setUser($user);

            $em->persist($reclamation);
            $em->flush();

            return $this->redirectToRoute('reclamation_index');
        }

        return $this->render('frontOffice/reclamation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

 #[Route('/show/{id}', name: 'reclamation_show', methods: ['GET'])]
public function show(Reclamation $reclamation): Response
{
    return $this->render('backOffice/reclamation/show.html.twig', [
        'reclamation' => $reclamation,
    ]);
}




    #[Route('/edit/{id}', name: 'reclamation_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            return $this->redirectToRoute('reclamation_index');
        }

        return $this->render('backOffice/reclamation/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {

            $em->remove($reclamation);
            $em->flush();
        }

        return $this->redirectToRoute('reclamation_index');
    }

   #[Route('/mes-reclamations', name: 'mes_reclamations')]
public function mesReclamations(ReclamationRepository $repo): Response
{
    $reclamations = $repo->createQueryBuilder('r')
        ->leftJoin('r.reponses', 'rep')
        ->addSelect('rep')
        ->orderBy('r.id', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('backOffice/reclamation/mes_reclamations.html.twig', [
        'reclamations' => $reclamations,
    ]);
}


}

