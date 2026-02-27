<?php


namespace App\Service;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MLScoringService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ReservationRepository $reservationRepo
    ) {}

    public function getScore(Reservation $reservation): array
    {
        $user = $reservation->getUser();

        // Historique du user — réservations payées/confirmées
        $historique = $this->reservationRepo->findBy([
            'user'   => $user,
            'status' => 'CONFIRMÉE'
        ]);

        // Durée de la réservation actuelle en mois
        $diff  = $reservation->getDateDebut()->diff($reservation->getDateFin());
        $duree = ($diff->y * 12) + $diff->m;

        // Durée moyenne de ses anciens séjours
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

        } catch (\Exception $e) {
            // Si Flask est down → score par défaut
            return [
                'score'    => 0,
                'fiable'   => false,
                'priorite' => 'Non disponible',
                'emoji'    => '❓'
            ];
        }
    }
}