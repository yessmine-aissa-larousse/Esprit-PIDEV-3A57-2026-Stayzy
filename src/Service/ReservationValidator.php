<?php

namespace App\Service;

use App\Entity\Reservation;

class ReservationValidator
{
    /**
     * Valide qu'une réservation respecte toutes les règles métier
     * 
     * @throws \InvalidArgumentException si une règle n'est pas respectée
     */
    public function validate(Reservation $reservation): bool
    {
        // Règle 1 : La date de fin doit être postérieure à la date de début
        if ($reservation->getDateDebut() === null || $reservation->getDateFin() === null) {
            throw new \InvalidArgumentException('Les dates de début et de fin sont obligatoires');
        }

        if ($reservation->getDateFin() <= $reservation->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début');
        }

        // Règle 2 : Le nombre de personnes doit être supérieur à zéro
        if ($reservation->getNombrePersonnes() === null || $reservation->getNombrePersonnes() <= 0) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être supérieur à zéro');
        }

        // Règle 3 : Le prix total doit être positif
        if ($reservation->getPrixTotal() !== null && $reservation->getPrixTotal() <= 0) {
            throw new \InvalidArgumentException('Le prix total doit être positif');
        }

        // Règle 4 : Le message de demande ne peut pas dépasser 500 caractères
        if ($reservation->getMessageDemande() !== null && strlen($reservation->getMessageDemande()) > 500) {
            throw new \InvalidArgumentException('Le message de demande ne peut pas dépasser 500 caractères');
        }

        return true;
    }

    /**
     * Vérifie que les dates sont cohérentes
     */
    public function areDatesValides(\DateTime $dateDebut, \DateTime $dateFin): bool
    {
        return $dateFin > $dateDebut;
    }

    /**
     * Vérifie que le nombre de personnes est valide
     */
    public function isNombrePersonnesValide(int $nombre): bool
    {
        return $nombre > 0;
    }

    /**
     * Calcule la durée du séjour en jours
     */
    public function calculerDureeSejour(Reservation $reservation): int
    {
        if ($reservation->getDateDebut() === null || $reservation->getDateFin() === null) {
            throw new \InvalidArgumentException('Les dates sont obligatoires pour calculer la durée');
        }

        $interval = $reservation->getDateDebut()->diff($reservation->getDateFin());
        return $interval->days;
    }
}