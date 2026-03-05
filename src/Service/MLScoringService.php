<?php

namespace App\Service;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MLScoringService
 * Scoring Reponse + Analyse Reclamation
 */
class MLScoringService
{
    private string $flaskUrl = 'http://localhost:5000';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ReservationRepository $reservationRepo
    ) {}

    /* =========================
       SCORE REPONSE
    ==========================*/
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
        } catch (\Exception) {}

        return $this->fallback($reponse);
    }

    /* =========================
       ANALYSE RECLAMATION
    ==========================*/
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
            'risk' => [
                'label' => $label,
                'score' => $score,
                'color' => match($label) {
                    'high' => 'danger',
                    'medium' => 'warning',
                    default => 'success'
                },
                'icon' => match($label) {
                    'high' => '🚨',
                    'medium' => '⚠️',
                    default => '✅'
                },
                'confidence' => 0
            ],
            'fallback' => true,
        ];
    }

    /* =========================
       FALLBACK REPONSE
    ==========================*/
    private function fallback(Reponse $reponse): array
    {
        $len = strlen($reponse->getContenu() ?? '');

        [$label, $score, $color, $icon] = match(true) {
            $len > 200 => ['excellent', 85, 'success', '⭐'],
            $len > 80  => ['acceptable', 55, 'warning', '👍'],
            default    => ['faible', 20, 'danger', '⚠️'],
        };

        return [
            'quality'   => [
                'label' => $label,
                'score' => $score,
                'confidence' => 0,
                'color' => $color,
                'icon' => $icon
            ],
            'delay'     => ['label' => 'inconnu', 'hours' => null, 'color' => 'secondary'],
            'scored_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'fallback'  => true,
        ];
    }

    /* =========================
       SCORE RESERVATION
    ==========================*/
    public function getScore(Reservation $reservation): array
    {
        $user = $reservation->getUser();

        $historique = $this->reservationRepo->findBy([
            'user'   => $user,
            'status' => 'CONFIRMÉE'
        ]);

        $diff  = $reservation->getDateDebut()->diff($reservation->getDateFin());
        $duree = ($diff->y * 12) + $diff->m;

        $totalMois = 0;
        foreach ($historique as $h) {
            $d = $h->getDateDebut()->diff($h->getDateFin());
            $totalMois += ($d->y * 12) + $d->m;
        }

        $moyenneDuree = count($historique) > 0
            ? round($totalMois / count($historique), 1)
            : 0;

        try {
            $response = $this->httpClient->request('POST', 'http://127.0.0.1:5000/predict', [
                'json' => [
                    'nb_reservations_passees' => count($historique),
                    'duree_mois'              => $duree,
                    'moyenne_duree_passee'    => $moyenneDuree,
                    'nombre_personnes'        => $reservation->getNombrePersonnes()
                ],
                'timeout' => 3
            ]);

            return $response->toArray();

        } catch (\Exception) {
            return [
                'score'    => 0,
                'fiable'   => false,
                'priorite' => 'Non disponible',
                'emoji'    => '❓'
            ];
        }
    }
}