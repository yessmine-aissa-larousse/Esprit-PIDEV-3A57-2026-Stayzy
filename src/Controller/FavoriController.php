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
    // ⚠️ TEMPORAIRE : ID utilisateur de test
    private const TEST_USER_ID = 3;

    private function getTestUser(EntityManagerInterface $em): ?User
    {
        return $em->getRepository(User::class)->find(self::TEST_USER_ID);
    }

    // =========================================================================
    // FRONTEND : PAGE MES FAVORIS (Pour les clients)
    // =========================================================================
    #[Route('/mes-favoris', name: 'mes_favoris')]
    public function mesFavoris(EntityManagerInterface $em): Response
    {
        $user = $this->getTestUser($em);

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('app_home');
        }

        $favoris = $user->getFavoris();

        return $this->render('frontOffice/favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    // =========================================================================
    // BACKOFFICE : MES FAVORIS (Pour les clients dans le backoffice)
    // =========================================================================
    #[Route('/admin/mes-favoris', name: 'admin_mes_favoris')]
    public function adminMesFavoris(EntityManagerInterface $em): Response
    {
        $user = $this->getTestUser($em);

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('admin_dashboard');
        }

        $favoris = $user->getFavoris();

        return $this->render('backOffice/favoris/mes_favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    // =========================================================================
    // BACKOFFICE : LOGEMENTS MIS EN FAVORIS PAR MES CLIENTS (Pour propriétaires)
    // =========================================================================
    #[Route('/admin/favoris-clients', name: 'admin_favoris_clients')]
    public function favorisClients(EntityManagerInterface $em): Response
    {
        $user = $this->getTestUser($em);

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Récupérer les logements du propriétaire
        $mesLogements = $user->getLogements();

        // Pour chaque logement, compter les favoris
        $statistiques = [];
        foreach ($mesLogements as $logement) {
            $statistiques[] = [
                'logement' => $logement,
                'nombre_favoris' => $logement->getNombreFavoris(),
                'utilisateurs' => $logement->getUtilisateursFavoris(),
            ];
        }

        // Trier par nombre de favoris (les plus populaires en premier)
        usort($statistiques, function($a, $b) {
            return $b['nombre_favoris'] <=> $a['nombre_favoris'];
        });

        return $this->render('backOffice/favoris/favoris_clients.html.twig', [
            'statistiques' => $statistiques,
        ]);
    }

    // =========================================================================
    // AJAX : TOGGLE FAVORI (Ajouter/Retirer)
    // =========================================================================
    #[Route('/favori/toggle/{id}', name: 'favori_toggle', methods: ['POST'])]
    public function toggleFavori(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getTestUser($em);
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non connecté'], 401);
        }

        $logement = $em->getRepository(Logement::class)->find($id);

        if (!$logement) {
            return new JsonResponse(['success' => false, 'message' => 'Logement introuvable'], 404);
        }

        if ($user->isFavori($logement)) {
            // Retirer des favoris
            $user->removeFavori($logement);
            $action = 'removed';
            $message = 'Retiré des favoris';
        } else {
            // Ajouter aux favoris
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
    // AJAX : VÉRIFIER SI EN FAVORI
    // =========================================================================
    #[Route('/favori/check/{id}', name: 'favori_check', methods: ['GET'])]
    public function checkFavori(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getTestUser($em);

        if (!$user) {
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
    // AJAX : COMPTEUR FAVORIS (Badge menu)
    // =========================================================================
    #[Route('/favori/count', name: 'favori_count', methods: ['GET'])]
    public function countFavoris(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getTestUser($em);

        if (!$user) {
            return new JsonResponse(['count' => 0]);
        }

        return new JsonResponse([
            'count' => $user->getNombreFavoris()
        ]);
    }
}