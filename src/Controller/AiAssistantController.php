<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PROPRIETAIRE')]
final class AiAssistantController extends AbstractController
{
    private const SCRIPT_PATH = '/ai/analyze.py';

    // =========================================================================
    // DÉTECTION AUTOMATIQUE DU CHEMIN PYTHON (portable sur toutes les machines)
    // =========================================================================
    private function getPythonPath(): string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $candidates = $isWindows
            ? ['python', 'python3', 'py']
            : [
                // Conda / Anaconda paths (macOS)
                '/opt/anaconda3/bin/python',
                '/opt/anaconda3/bin/python3',
                '/usr/local/anaconda3/bin/python',
                '/opt/miniconda3/bin/python',
                '/usr/local/miniconda3/bin/python',
                // Standard paths
                '/usr/local/bin/python3',
                '/usr/bin/python3',
                'python3',
                'python',
            ];

        foreach ($candidates as $cmd) {
            $test = shell_exec(sprintf('"%s" -c "import sklearn" 2>&1', $cmd));
            if ($test === '' || $test === null) {
                // sklearn imports without error → this Python has required packages
                return $cmd;
            }
        }

        // Fallback: just find any Python
        foreach ($candidates as $cmd) {
            $test = shell_exec(sprintf('"%s" --version 2>&1', $cmd));
            if ($test && str_contains($test, 'Python')) {
                return $cmd;
            }
        }

        return '/opt/anaconda3/bin/python';
    }

    // =========================================================================
    // HELPER : appelle le script Python et retourne le tableau décodé
    // =========================================================================
    private function callPython(array $payload, string $kernelDir): array
    {
        $scriptPath = $kernelDir . self::SCRIPT_PATH;
        $tmpFile    = sys_get_temp_dir() . '/stayzy_ai_' . uniqid() . '.json';

        file_put_contents($tmpFile, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $commande = sprintf('"%s" "%s" "%s" 2>&1', $this->getPythonPath(), $scriptPath, $tmpFile);
        $sortie   = shell_exec($commande);

        if (file_exists($tmpFile)) unlink($tmpFile);

        if (!$sortie) {
            return ['erreur' => 'Python n\'a retourné aucune sortie.'];
        }

        $jsonStart = strpos($sortie, '{');
        if ($jsonStart === false) {
            return ['erreur' => 'Aucun JSON détecté. Sortie : ' . substr($sortie, 0, 300)];
        }

        $decoded = json_decode(substr($sortie, $jsonStart), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['erreur' => 'JSON invalide : ' . json_last_error_msg()];
        }

        return $decoded;
    }

    // =========================================================================
    // HELPER : récupérer les données du propriétaire
    // =========================================================================
    private function getProprietaireData(EntityManagerInterface $em): array
    {
        $user      = $this->getUser();
        $logements = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->where('l.proprietaire = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        $logementIds  = array_map(fn($l) => $l->getId(), $logements);
        $reservations = [];

        if (!empty($logementIds)) {
            $reservations = $em->getRepository(Reservation::class)
                ->createQueryBuilder('r')
                ->where('r.logement IN (:ids)')
                ->setParameter('ids', $logementIds)
                ->getQuery()->getResult();
        }

        $donneesLogements = array_map(fn($l) => [
            'id'                   => $l->getId(),
            'titre'                => $l->getTitre(),
            'prix'                 => $l->getPrix(),
            'superficie'           => $l->getSuperficie(),
            'nombre_chambres'      => $l->getNombreChambres(),
            'nombre_salle_de_bain' => $l->getNombreSalleDeBain(),
            'amenites'             => $l->getAmenites() ?? [],
            'note_moyenne'         => $l->getNoteMoyenne(),
            'nb_favoris'           => $l->getNombreFavoris(),
            'a_promo'              => $l->getPromoActive() !== null,
            'nb_photos'            => count($l->getPhotos() ?? []),
            'description_longue'   => strlen($l->getDescription() ?? '') > 100,
            'categorie'            => $l->getCategorie()?->getNom() ?? '',
            'ville'                => is_array($l->getAdresse()) ? ($l->getAdresse()['ville'] ?? '') : '',
        ], $logements);

        $donneesReservations = array_map(fn($r) => [
            'logement_id'  => $r->getLogement()->getId(),
            'date_debut'   => $r->getDateDebut()?->format('Y-m-d'),
            'date_fin'     => $r->getDateFin()?->format('Y-m-d'),
            'prix_total'   => $r->getPrixTotal(),
            'status'       => $r->getStatus(),
            'nb_personnes' => $r->getNombrePersonnes(),
        ], $reservations);

        return [
            'logements'    => $donneesLogements,
            'reservations' => $donneesReservations,
        ];
    }

    // =========================================================================
    // ROUTE : CHAT IA
    // =========================================================================
    #[Route('/proprietaire/ai/chat', name: 'ai_chat', methods: ['POST'])]
    public function chat(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $body     = json_decode($request->getContent(), true);
        $question = trim($body['question'] ?? '');

        if (empty($question)) {
            return $this->json(['erreur' => 'Question vide.'], 400);
        }

        $data             = $this->getProprietaireData($em);
        $data['mode']     = 'chat';
        $data['question'] = $question;

        $result = $this->callPython($data, $this->getParameter('kernel.project_dir'));

        return $this->json($result);
    }

    // =========================================================================
    // ROUTE : GÉNÉRATEUR DE DESCRIPTION
    // =========================================================================
    #[Route('/proprietaire/ai/description/{logementId}', name: 'ai_description', methods: ['GET'])]
    public function description(int $logementId, EntityManagerInterface $em): JsonResponse
    {
        $logement = $em->getRepository(Logement::class)->find($logementId);

        if (!$logement || $logement->getProprietaire() !== $this->getUser()) {
            return $this->json(['erreur' => 'Logement introuvable.'], 404);
        }

        $adresse = $logement->getAdresse();

        $payload = [
            'mode'     => 'description',
            'logement' => [
                'id'                   => $logement->getId(),
                'titre'                => $logement->getTitre(),
                'prix'                 => $logement->getPrix(),
                'superficie'           => $logement->getSuperficie(),
                'nombre_chambres'      => $logement->getNombreChambres(),
                'nombre_salle_de_bain' => $logement->getNombreSalleDeBain(),
                'amenites'             => $logement->getAmenites() ?? [],
                'categorie'            => $logement->getCategorie()?->getNom() ?? '',
                'ville'                => is_array($adresse) ? ($adresse['ville'] ?? '') : '',
            ],
        ];

        $result = $this->callPython($payload, $this->getParameter('kernel.project_dir'));
        return $this->json($result);
    }

    // =========================================================================
    // ROUTE : SUGGESTION DE PRIX
    // =========================================================================
    #[Route('/proprietaire/ai/prix/{logementId}', name: 'ai_prix', methods: ['GET'])]
    public function prix(int $logementId, EntityManagerInterface $em): JsonResponse
    {
        $logement = $em->getRepository(Logement::class)->find($logementId);

        if (!$logement || $logement->getProprietaire() !== $this->getUser()) {
            return $this->json(['erreur' => 'Logement introuvable.'], 404);
        }

        $data         = $this->getProprietaireData($em);
        $adresse      = $logement->getAdresse();
        $reservations = array_filter($data['reservations'],
            fn($r) => $r['logement_id'] === $logementId);

        $payload = [
            'mode'           => 'prix',
            'logement'       => [
                'id'           => $logement->getId(),
                'titre'        => $logement->getTitre(),
                'prix'         => $logement->getPrix(),
                'superficie'   => $logement->getSuperficie(),
                'nombre_chambres' => $logement->getNombreChambres(),
                'amenites'     => $logement->getAmenites() ?? [],
                'note_moyenne' => $logement->getNoteMoyenne(),
                'nb_photos'    => count($logement->getPhotos() ?? []),
                'ville'        => is_array($adresse) ? ($adresse['ville'] ?? '') : '',
            ],
            'reservations'   => array_values($reservations),
            'tous_logements' => $data['logements'],
        ];

        $result = $this->callPython($payload, $this->getParameter('kernel.project_dir'));
        return $this->json($result);
    }

    // =========================================================================
    // ROUTE : MEILLEURES PÉRIODES POUR PROMOS
    // =========================================================================
    #[Route('/proprietaire/ai/periodes/{logementId}', name: 'ai_periodes', methods: ['GET'])]
    public function periodes(int $logementId, EntityManagerInterface $em): JsonResponse
    {
        $logement = $em->getRepository(Logement::class)->find($logementId);

        if (!$logement || $logement->getProprietaire() !== $this->getUser()) {
            return $this->json(['erreur' => 'Logement introuvable.'], 404);
        }

        $data         = $this->getProprietaireData($em);
        $reservations = array_filter($data['reservations'],
            fn($r) => $r['logement_id'] === $logementId);

        $payload = [
            'mode'         => 'periodes',
            'logement'     => ['id' => $logementId, 'titre' => $logement->getTitre()],
            'reservations' => array_values($reservations),
        ];

        $result = $this->callPython($payload, $this->getParameter('kernel.project_dir'));
        return $this->json($result);
    }
}