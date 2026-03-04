<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FavoriController extends AbstractController
{
    // =========================================================================
    // FRONTEND : PAGE MES FAVORIS (Pour les clients)
    // =========================================================================
    #[Route('/mes-favoris', name: 'mes_favoris')]
    public function mesFavoris(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à vos favoris');
            return $this->redirectToRoute('app_login');
        }

        $favoris = $user->getFavoris();

        return $this->render('backOffice/favoris/favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    // =========================================================================
    // BACKOFFICE : MES FAVORIS (Pour les clients dans le backoffice)
    // =========================================================================
    #[Route('/client/mes-favoris', name: 'client_mes_favoris')]
    public function adminMesFavoris(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('app_login');
        }

        $favoris = $user->getFavoris();

        return $this->render('frontOffice/favoris/favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    // =========================================================================
    // LOGEMENTS MIS EN FAVORIS PAR MES CLIENTS (Pour propriétaires)
    // =========================================================================
    #[Route('/proprieyaire/favoris-clients', name: 'proprieyaire_favoris_clients')]
    public function favorisClients(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('app_login');
        }

        $mesLogements = $user->getLogements();

        $statistiques = [];

        foreach ($mesLogements as $logement) {
            $statistiques[] = [
                'logement' => $logement,
                'nombre_favoris' => $logement->getUtilisateursFavoris()->count(),
                'utilisateurs' => $logement->getUtilisateursFavoris(),
            ];
        }

        usort($statistiques, function ($a, $b) {
            return $b['nombre_favoris'] <=> $a['nombre_favoris'];
        });

        return $this->render('frontOffice/favoris/favoris_clients.html.twig', [
            'statistiques' => $statistiques,
        ]);
    }

    // =========================================================================
    // AJAX : TOGGLE FAVORI (Ajouter / Retirer)
    // =========================================================================
    #[Route('/favori/toggle/{id}', name: 'favori_toggle', methods: ['POST'])]
    public function toggleFavori(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous devez être connecté pour ajouter des favoris',
            ]);
        }

        $logement = $em->getRepository(Logement::class)->find($id);

        if (!$logement) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Logement introuvable'
            ], 404);
        }

        if ($user->isFavori($logement)) {
            $user->removeFavori($logement);
            $action = 'removed';
            $message = 'Retiré des favoris';
        } else {
            $user->addFavori($logement);
            $action = 'added';
            $message = 'Ajouté aux favoris';
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'action' => $action,
            'message' => $message,
            'count' => $user->getNombreFavoris()
        ]);
    }

    // =========================================================================
    // AJAX : Vérifier si en favori
    // =========================================================================
    #[Route('/favori/check/{id}', name: 'favori_check', methods: ['GET'])]
    public function checkFavori(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['isFavorite' => false]);
        }

        $logement = $em->getRepository(Logement::class)->find($id);

        if (!$logement) {
            return new JsonResponse(['isFavorite' => false]);
        }

        return new JsonResponse([
            'isFavorite' => $user->isFavori($logement)
        ]);
    }

    // =========================================================================
    // AJAX : Compteur favoris (Badge menu)
    // =========================================================================
    #[Route('/favori/count', name: 'favori_count', methods: ['GET'])]
    public function countFavoris(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['count' => 0]);
        }

        return new JsonResponse([
            'count' => $user->getNombreFavoris()
        ]);
    }
}