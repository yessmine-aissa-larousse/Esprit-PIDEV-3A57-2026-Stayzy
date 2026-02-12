<?php

namespace App\Controller;

use App\Entity\Visite;
use App\Form\VisiteType;
use App\Repository\VisiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/visite')]
class VisiteController extends AbstractController
{
    // 🔹 READ ALL
    #[Route('/', name: 'app_visite_index', methods: ['GET'])]
    public function index(VisiteRepository $visiteRepository): Response
    {
        return $this->render('visite/index.html.twig', [
            'visites' => $visiteRepository->findAll(),
        ]);
    }

    // 🔹 CREATE
    #[Route('/new', name: 'app_visite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $visite = new Visite();

        // auto affectation user connecté
        $visite->setUser($this->getUser());

        $form = $this->createForm(VisiteType::class, $visite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // valeur par défaut
            $visite->setStatut('EN_ATTENTE');
            $visite->setDisponibilite(true);

            $em->persist($visite);
            $em->flush();

            return $this->redirectToRoute('app_visite_index');
        }

        return $this->render('visite/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // 🔹 READ ONE
    #[Route('/{id}', name: 'app_visite_show', methods: ['GET'])]
    public function show(Visite $visite): Response
    {
        return $this->render('visite/show.html.twig', [
            'visite' => $visite,
        ]);
    }

    // 🔹 UPDATE
    #[Route('/{id}/edit', name: 'app_visite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Visite $visite, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VisiteType::class, $visite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_visite_index');
        }

        return $this->render('visite/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // 🔹 DELETE
    #[Route('/{id}', name: 'app_visite_delete', methods: ['POST'])]
    public function delete(Request $request, Visite $visite, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$visite->getId(), $request->request->get('_token'))) {
            $em->remove($visite);
            $em->flush();
        }

        return $this->redirectToRoute('app_visite_index');
    }
}
