<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProprietairePortalController extends AbstractController
{
    #[Route('/proprietaire', name: 'app_proprietaire_portal')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->render('security/proprietaire.html.twig');
    }
}