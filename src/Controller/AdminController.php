<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ReclamationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
   #[Route('/admin', name: 'admin_dashboard')]
public function dashboard(ReclamationRepository $repo): Response
{
    $total = $repo->count([]);
    $enAttente = $repo->count(['statut' => 'EN_ATTENTE']);
    $traitee = $repo->count(['statut' => 'TRAITEE']);

    return $this->render('backOffice/dashboard.html.twig', [
        'total' => $total,
        'enAttente' => $enAttente,
        'traitee' => $traitee,
    ]);
}}

