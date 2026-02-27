<?php

namespace App\Service;

use App\Entity\Reponse;
use App\Entity\Reclamation;


use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MLScoringService
 * ────────────────
 * Appelle l'API Flask (projet-ia/app.py) pour scorer
 * la qualité d'une Reponse via le modèle ML.
 */
class MLScoringService
{
    private string $flaskUrl = 'http://localhost:5000';

    public function __construct(private HttpClientInterface $httpClient) {}

    /**
     * Retourne le score complet d'une Reponse.
     *
     * Exemple de retour :
     * [
     *   'quality' => ['label' => 'excellent', 'score' => 95, 'confidence' => 87.3, 'color' => 'success', 'icon' => '⭐'],
     *   'delay'   => ['label' => 'rapide', 'hours' => 3.5, 'color' => 'success'],
     *   'scored_at' => '2024-01-15T10:30:00',
     *   'fallback'  => false,
     * ]
     */
    public function scoreReponse(Reponse $reponse): array
    {
        try {
            $reclamation = $reponse->getReclamation();

            $response = $this->httpClient->request('POST', $this->flaskUrl . '/score-reponse', [
                'json' => [
                    'id'               => $reponse->getId(),
                    'contenu'          => $reponse->getContenu(),
                    'date_reclamation' => $reclamation?->getDateReclamation()?->format('Y-m-d\TH:i:s'),
                    'date_reponse'     => $reponse->getDateReponse()?->format('Y-m-d\TH:i:s'),
                ],
                'timeout' => 4,
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray() + ['fallback' => false];
            }
        } catch (\Exception) {
            // Flask down → fallback
        }

        return $this->fallback($reponse);
    }


    /* ===== ANALYSE RISK RÉCLAMATION (admin supervision) ===== */
public function analyzeReclamation(Reclamation $reclamation): array
{
    try {
        $response = $this->httpClient->request('POST', $this->flaskUrl . '/analyze', [
            'json' => [
                'id'               => $reclamation->getId(),
                'description'      => $reclamation->getDescription(),
                'statut'           => $reclamation->getStatut(),
                'date_reclamation' => $reclamation->getDateReclamation()?->format('Y-m-d\TH:i:s'),
            ],
            'timeout' => 4,
        ]);
        if ($response->getStatusCode() === 200) {
            return $response->toArray() + ['fallback' => false];
        }
    } catch (\Exception) {}

    // fallback si Flask down
    $desc = strtolower($reclamation->getDescription());
    $score = 0;
    foreach (['arnaque', 'fraude', 'menace', 'scandale', 'urgence'] as $kw) {
        if (str_contains($desc, $kw)) $score += 20;
    }
    $score = min($score, 100);
    $label = match(true) {
        $score >= 70 => 'high',
        $score >= 40 => 'medium',
        default      => 'low',
    };
    return [
        'risk'     => ['label' => $label, 'score' => $score, 'color' => match($label) { 'high' => 'danger', 'medium' => 'warning', default => 'success' }, 'icon' => match($label) { 'high' => '🚨', 'medium' => '⚠️', default => '✅' }, 'confidence' => 0],
        'fallback' => true,
    ];
}

    /**
     * Score rapide sans appel API (pour les cas où Flask est down).
     */
    private function fallback(Reponse $reponse): array
    {
        $len = strlen($reponse->getContenu() ?? '');

        [$label, $score, $color, $icon] = match(true) {
            $len > 200 => ['excellent',  85, 'success', '⭐'],
            $len > 80  => ['acceptable', 55, 'warning', '👍'],
            default    => ['faible',     20, 'danger',  '⚠️'],
        };

        return [
            'quality'   => ['label' => $label, 'score' => $score, 'confidence' => 0, 'color' => $color, 'icon' => $icon],
            'delay'     => ['label' => 'inconnu', 'hours' => null, 'color' => 'secondary'],
            'scored_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'fallback'  => true,
        ];
    }
}