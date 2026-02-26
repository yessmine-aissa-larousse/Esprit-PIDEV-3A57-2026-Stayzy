<?php

namespace App\Controller;

use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('frontOffice/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/properties', name: 'app_properties')]
    public function properties(LogementRepository $logementRepository): Response
    {
        $logements = $logementRepository->findAll();

        return $this->render('frontOffice/properties.html.twig', [
            'logements' => $logements
        ]);
    }
}
