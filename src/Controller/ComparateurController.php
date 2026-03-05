<?php

namespace App\Controller;

use App\Entity\Logement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ComparateurController extends AbstractController
{
    private const SESSION_KEY = 'comparateur_logements';
    private const MAX_LOGEMENTS = 4;

    // =========================================================================
    // AJOUTER UN LOGEMENT AU COMPARATEUR (AJAX)
    // =========================================================================
    #[Route('/comparateur/add/{id}', name: 'comparateur_add', methods: ['POST'])]
    public function add(int $id, SessionInterface $session, EntityManagerInterface $em): JsonResponse
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        
        if (!$logement) {
            return new JsonResponse(['success' => false, 'message' => 'Logement introuvable'], 404);
        }

        // Récupérer la liste des IDs en session
        $comparateur = $session->get(self::SESSION_KEY, []);

        // Vérifier si déjà dans le comparateur
        if (in_array($id, $comparateur)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Logement déjà dans le comparateur'
            ]);
        }

        // Vérifier la limite
        if (count($comparateur) >= self::MAX_LOGEMENTS) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Maximum ' . self::MAX_LOGEMENTS . ' logements dans le comparateur'
            ]);
        }

        // Ajouter
        $comparateur[] = $id;
        $session->set(self::SESSION_KEY, $comparateur);

        return new JsonResponse([
            'success' => true,
            'message' => 'Ajouté au comparateur',
            'count' => count($comparateur)
        ]);
    }

    // =========================================================================
    // RETIRER UN LOGEMENT DU COMPARATEUR (AJAX)
    // =========================================================================
    #[Route('/comparateur/remove/{id}', name: 'comparateur_remove', methods: ['POST'])]
    public function remove(int $id, SessionInterface $session): JsonResponse
    {
        $comparateur = $session->get(self::SESSION_KEY, []);
        
        $key = array_search($id, $comparateur);
        if ($key !== false) {
            unset($comparateur[$key]);
            $comparateur = array_values($comparateur); // Réindexer
            $session->set(self::SESSION_KEY, $comparateur);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Retiré du comparateur',
                'count' => count($comparateur)
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Logement non trouvé dans le comparateur'
        ]);
    }

    // =========================================================================
    // VIDER LE COMPARATEUR
    // =========================================================================
    #[Route('/comparateur/clear', name: 'comparateur_clear', methods: ['POST'])]
    public function clear(SessionInterface $session): JsonResponse
    {
        $session->remove(self::SESSION_KEY);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Comparateur vidé',
            'count' => 0
        ]);
    }

    // =========================================================================
    // COMPTER LES LOGEMENTS DANS LE COMPARATEUR
    // =========================================================================
    #[Route('/comparateur/count', name: 'comparateur_count', methods: ['GET'])]
    public function count(SessionInterface $session): JsonResponse
    {
        $comparateur = $session->get(self::SESSION_KEY, []);
        
        return new JsonResponse([
            'count' => count($comparateur)
        ]);
    }

    // =========================================================================
    // PAGE DE COMPARAISON
    // =========================================================================
    #[Route('/comparateur', name: 'comparateur_view')]
    public function view(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $ids = $session->get(self::SESSION_KEY, []);
        
        if (empty($ids)) {
            $this->addFlash('info', 'Aucun logement dans le comparateur');
            return $this->redirectToRoute('app_properties');
        }

        // Récupérer les logements
        $logements = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->leftJoin('l.categorie', 'c')
            ->leftJoin('l.proprietaire', 'p')
            ->addSelect('c', 'p')
            ->where('l.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $this->render('frontOffice/comparateur.html.twig', [
            'logements' => $logements,
        ]);
    }
}