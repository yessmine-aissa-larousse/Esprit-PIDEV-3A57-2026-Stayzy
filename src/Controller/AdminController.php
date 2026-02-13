<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('backOffice/dashboard.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/forum', name: 'admin_forum', methods: ['GET'])]
    public function forum(): Response
    {
        return $this->redirectToRoute('admin_post_index');
    }
}
