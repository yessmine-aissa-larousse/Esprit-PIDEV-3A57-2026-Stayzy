<?php

namespace App\Service;

use App\Entity\Logement;

class LogementValidator
{
    /**
     * Valide qu'un logement respecte toutes les règles métier
     */
    public function validate(Logement $logement): bool
    {
        // Règle 1 : Prix > 0
        if ($logement->getPrix() === null || $logement->getPrix() <= 0) {
            throw new \InvalidArgumentException('Le prix doit être supérieur à 0');
        }

        // Règle 2 : Superficie > 0
        if ($logement->getSuperficie() === null || $logement->getSuperficie() <= 0) {
            throw new \InvalidArgumentException('La superficie doit être supérieure à 0');
        }

        // Règle 3 : Nombre de chambres > 0
        if ($logement->getNombreChambres() === null || $logement->getNombreChambres() <= 0) {
            throw new \InvalidArgumentException('Le nombre de chambres doit être supérieur à 0');
        }

        // Règle 4 : Titre minimum 5 caractères
        if ($logement->getTitre() === null || strlen($logement->getTitre()) < 5) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 5 caractères');
        }

        return true;
    }

    /**
     * Valide uniquement le prix (pour tester une règle isolée)
     */
    public function isPrixValide(float $prix): bool
    {
        return $prix > 0;
    }

    /**
     * Valide uniquement la superficie
     */
    public function isSuperficieValide(int $superficie): bool
    {
        return $superficie > 0;
    }
}