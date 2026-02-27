<?php

namespace App\Controller;

use App\Repository\LogementRepository;
use App\Entity\Logement;
use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer les 6 logements les plus récents pour l'affichage
        $logementsFeatured = $em->getRepository(Logement::class)->findBy(
            ['disponible' => true],
            ['createdAt' => 'DESC'],
            6  // Limiter à 6 logements
        );

        // Récupérer toutes les catégories pour la barre de recherche
        $categories = $em->getRepository(Categorie::class)->findAll();

        return $this->render('frontOffice/index.html.twig', [
            'logementsFeatured' => $logementsFeatured,
            'categories' => $categories,
        ]);
    }

    #[Route('/properties', name: 'app_properties')]
    public function properties(Request $request, EntityManagerInterface $em): Response
    {
        // ═══════════════════════════════════════════════════════════════
        // RÉCUPÉRATION DES FILTRES DEPUIS L'URL (GET)
        // ═══════════════════════════════════════════════════════════════
        $ville = $request->query->get('ville');
        $categorieId = $request->query->get('categorie');
        $prixMin = $request->query->get('prix_min');
        $prixMax = $request->query->get('prix_max');
        $chambres = $request->query->get('chambres');
        $tri = $request->query->get('tri', 'recent'); // Par défaut : plus récent

        // ═══════════════════════════════════════════════════════════════
        // CONSTRUCTION DE LA REQUÊTE AVEC QUERYBUILDER
        // ═══════════════════════════════════════════════════════════════
        $qb = $em->getRepository(Logement::class)->createQueryBuilder('l')
            ->where('l.disponible = :disponible')
            ->setParameter('disponible', true);

        // ── Filtre par catégorie ──
        if ($categorieId) {
            $qb->andWhere('l.categorie = :categorie')
               ->setParameter('categorie', $categorieId);
        }

        // ── Filtre par prix minimum ──
        if ($prixMin) {
            $qb->andWhere('l.prix >= :prixMin')
               ->setParameter('prixMin', (float) $prixMin);
        }

        // ── Filtre par prix maximum ──
        if ($prixMax) {
            $qb->andWhere('l.prix <= :prixMax')
               ->setParameter('prixMax', (float) $prixMax);
        }

        // ── Filtre par nombre de chambres minimum ──
        if ($chambres && $chambres !== 'any') {
            $qb->andWhere('l.nombreChambres >= :chambres')
               ->setParameter('chambres', (int) $chambres);
        }

        // ═══════════════════════════════════════════════════════════════
        // TRI DES RÉSULTATS
        // ═══════════════════════════════════════════════════════════════
        switch ($tri) {
            case 'prix_asc':
                $qb->orderBy('l.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('l.prix', 'DESC');
                break;
            case 'superficie_desc':
                $qb->orderBy('l.superficie', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('l.createdAt', 'DESC');
                break;
        }

        // ── Exécution de la requête ──
        $logements = $qb->getQuery()->getResult();

        // ═══════════════════════════════════════════════════════════════
        // FILTRAGE PAR VILLE (en PHP car champ JSON)
        // ═══════════════════════════════════════════════════════════════
        if ($ville) {
            $logements = array_filter($logements, function($logement) use ($ville) {
                $adresse = $logement->getAdresse();
                if ($adresse && isset($adresse['ville'])) {
                    return stripos($adresse['ville'], $ville) !== false;
                }
                return false;
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // RÉCUPÉRER TOUTES LES CATÉGORIES POUR LE FORMULAIRE
        // ═══════════════════════════════════════════════════════════════
        $categories = $em->getRepository(Categorie::class)->findAll();

        // ═══════════════════════════════════════════════════════════════
        // PASSER LES DONNÉES AU TEMPLATE
        // ═══════════════════════════════════════════════════════════════
        return $this->render('frontOffice/properties.html.twig', [
            'logements' => $logements,
            'categories' => $categories,
            // Filtres actuels (pour pré-remplir le formulaire)
            'filtres' => [
                'ville' => $ville,
                'categorie' => $categorieId,
                'prix_min' => $prixMin,
                'prix_max' => $prixMax,
                'chambres' => $chambres,
                'tri' => $tri,
            ],
        ]);
    }

    #[Route('/property/{id}', name: 'app_property_details')]
    public function propertyDetails(int $id, EntityManagerInterface $em): Response
    {
        // Récupérer le logement par son ID
        $logement = $em->getRepository(Logement::class)->find($id);

        // Si le logement n'existe pas, rediriger vers la liste
        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('app_properties');
        }

        // Récupérer des logements similaires (même catégorie, prix proche)
        $logementsSimilaires = $em->getRepository(Logement::class)->createQueryBuilder('l')
            ->where('l.disponible = :disponible')
            ->andWhere('l.id != :currentId')
            ->andWhere('l.categorie = :categorie')
            ->setParameter('disponible', true)
            ->setParameter('currentId', $id)
            ->setParameter('categorie', $logement->getCategorie())
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('frontOffice/property-details.html.twig', [
            'logement' => $logement,
            'logementsSimilaires' => $logementsSimilaires,]);
    }
            
    #[Route('/portal/{type}', name: 'app_portal', requirements: ['type' => 'login|register'])]
    public function portal(string $type): Response  
    {
        return $this->render('frontOffice/portal.html.twig', [
            'type' => $type,
        ]);
    }
}