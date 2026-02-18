<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('frontOffice/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/properties', name: 'app_properties')]
    public function properties(): Response
    {
        return $this->render('frontOffice/properties.html.twig');
    }

    #[Route('/portal/{type}', name: 'app_portal', requirements: ['type' => 'login|register'])]
    public function portal(string $type): Response  // ← AJOUTER
    {
        return $this->render('frontOffice/portal.html.twig', [
            'type' => $type,
        ]);
    }
}