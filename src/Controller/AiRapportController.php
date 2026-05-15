<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PROPRIETAIRE')]
final class AiRapportController extends AbstractController
{
    // =========================================================================
    // DÉTECTION AUTOMATIQUE DU CHEMIN PYTHON (portable sur toutes les machines)
    // =========================================================================
    private function getPythonPath(): string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $candidates = $isWindows
            ? ['python', 'python3', 'py']
            : [
                '/opt/anaconda3/bin/python',
                '/opt/anaconda3/bin/python3',
                '/opt/miniconda3/bin/python',
                '/usr/local/anaconda3/bin/python',
                '/usr/local/bin/python3',
                '/usr/bin/python3',
                'python3',
                'python',
            ];

        // Pick the first Python that has sklearn installed
        foreach ($candidates as $cmd) {
            $test = shell_exec(sprintf('"%s" -c "import sklearn" 2>&1', $cmd));
            if ($test === '' || $test === null) {
                return $cmd;
            }
        }

        // Fallback: any Python
        foreach ($candidates as $cmd) {
            $test = shell_exec(sprintf('"%s" --version 2>&1', $cmd));
            if ($test && str_contains($test, 'Python')) {
                return $cmd;
            }
        }

        return '/opt/anaconda3/bin/python';
    }

    // =========================================================================
    // PAGE PRINCIPALE DU RAPPORT IA
    // =========================================================================
    #[Route('/proprietaire/rapport-ia', name: 'ai_rapport')]
    public function rapport(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // ── 1. Récupérer les logements du propriétaire ──
        $logements = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->where('l.proprietaire = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        if (empty($logements)) {
            $this->addFlash('warning', 'Vous n\'avez aucun logement. Publiez d\'abord un logement.');
            return $this->redirectToRoute('proprietaire_logement_list');
        }

        // ── 2. Récupérer toutes les réservations de ses logements ──
        $logementIds = array_map(fn($l) => $l->getId(), $logements);

        $reservations = $em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->where('r.logement IN (:ids)')
            ->setParameter('ids', $logementIds)
            ->getQuery()
            ->getResult();

        // ── 3. Préparer les données pour Python ──
        $donneesLogements = [];
        foreach ($logements as $l) {
            $adresse = $l->getAdresse();
            $photos  = $l->getPhotos() ?? [];

            $donneesLogements[] = [
                'id'                 => $l->getId(),
                'titre'              => $l->getTitre(),
                'prix'               => $l->getPrix(),
                'superficie'         => $l->getSuperficie(),
                'nombre_chambres'    => $l->getNombreChambres(),
                'amenites'           => $l->getAmenites() ?? [],
                'note_moyenne'       => $l->getNoteMoyenne(),
                'nb_favoris'         => $l->getNombreFavoris(),
                'a_promo'            => $l->getPromoActive() !== null,
                'nb_photos'          => count($photos),
                'description_longue' => strlen($l->getDescription() ?? '') > 100,
                'ville'              => is_array($adresse) ? ($adresse['ville'] ?? '') : '',
            ];
        }

        $donneesReservations = [];
        foreach ($reservations as $r) {
            $donneesReservations[] = [
                'logement_id'  => $r->getLogement()->getId(),
                'date_debut'   => $r->getDateDebut()?->format('Y-m-d'),
                'date_fin'     => $r->getDateFin()?->format('Y-m-d'),
                'prix_total'   => $r->getPrixTotal(),
                'status'       => $r->getStatus(),
                'nb_personnes' => $r->getNombrePersonnes(),
            ];
        }

        $payload = json_encode([
            'logements'    => $donneesLogements,
            'reservations' => $donneesReservations,
        ], JSON_UNESCAPED_UNICODE);

        // ── 4. Appeler le script Python ──
        $scriptPath = $this->getParameter('kernel.project_dir') . '/ai/analyze.py';
        $pythonPath = $this->getPythonPath(); // ✅ Détection automatique

        // Passe par un fichier JSON temporaire
        $tmpFile = sys_get_temp_dir() . '/stayzy_ai_' . uniqid() . '.json';
        file_put_contents($tmpFile, $payload);

        $commande = sprintf(
            '"%s" "%s" "%s" 2>&1',
            $pythonPath,
            $scriptPath,
            $tmpFile
        );
        $sortie = shell_exec($commande);

        // Nettoyer le fichier temporaire
        if (file_exists($tmpFile)) unlink($tmpFile);

        // ── 5. Décoder la réponse Python ──
        $rapport = null;
        $erreur  = null;

        if ($sortie) {
            // Chercher le début du JSON dans la sortie
            $jsonStart = strpos($sortie, '{');
            if ($jsonStart !== false) {
                $jsonStr = substr($sortie, $jsonStart);
                $decoded = json_decode($jsonStr, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($decoded['erreur'])) {
                        $erreur = $decoded['erreur'];
                    } else {
                        $rapport = $decoded;
                    }
                } else {
                    $erreur = 'JSON invalide reçu de Python. Erreur : ' . json_last_error_msg();
                }
            } else {
                $erreur = 'Aucun JSON détecté. Sortie Python : ' . substr($sortie, 0, 500);
            }
        } else {
            $erreur = 'Python n\'a retourné aucune sortie. Vérifiez que Python est installé.';
        }

        if ($rapport !== null && isset($rapport['erreur'])) {
            $erreur  = $rapport['erreur'];
            $rapport = null;
        }

        // ── 6. Afficher le rapport ──
        return $this->render('frontOffice/ai/rapport.html.twig', [
            'rapport'   => $rapport,
            'erreur'    => $erreur,
            'logements' => $logements,
        ]);
    }
}