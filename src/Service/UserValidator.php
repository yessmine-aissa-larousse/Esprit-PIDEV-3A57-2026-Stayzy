<?php

namespace App\Service;

use App\Entity\User;

class UserValidator
{
    /**
     * Valide qu'un utilisateur respecte toutes les règles métier
     * 
     * @throws \InvalidArgumentException si une règle n'est pas respectée
     */
    public function validate(User $user): bool
    {
        // Règle 1 : L'email doit être valide
        if ($user->getEmail() === null || !filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email n\'est pas valide');
        }

        // Règle 2 : Le nom doit contenir au moins 2 caractères
        if ($user->getNom() === null || strlen($user->getNom()) < 2) {
            throw new \InvalidArgumentException('Le nom doit contenir au moins 2 caractères');
        }

        // Règle 3 : Le prénom doit contenir au moins 2 caractères
        if ($user->getPrenom() === null || strlen($user->getPrenom()) < 2) {
            throw new \InvalidArgumentException('Le prénom doit contenir au moins 2 caractères');
        }

        // Règle 4 : Le téléphone doit contenir au moins 8 caractères (si renseigné)
        if ($user->getTel() !== null && strlen($user->getTel()) < 8) {
            throw new \InvalidArgumentException('Le téléphone doit contenir au moins 8 caractères');
        }

        // Règle 5 : Le téléphone doit être numérique (si renseigné)
        if ($user->getTel() !== null && !preg_match('/^[0-9+\-\s\(\)]{8,20}$/', $user->getTel())) {
            throw new \InvalidArgumentException('Le numéro de téléphone est invalide');
        }

        return true;
    }

    /**
     * Vérifie que l'email est valide
     */
    public function isEmailValide(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Vérifie que le nom/prénom a une longueur minimale
     */
    public function isNomValide(string $nom, int $minLength = 2): bool
    {
        return strlen($nom) >= $minLength;
    }

    /**
     * Vérifie que le téléphone est valide
     */
    public function isTelephoneValide(?string $tel): bool
    {
        if ($tel === null) {
            return true; // Optionnel
        }

        return strlen($tel) >= 8 && preg_match('/^[0-9+\-\s\(\)]{8,20}$/', $tel);
    }

    /**
     * Génère le nom complet de l'utilisateur
     */
    public function getFullName(User $user): string
    {
        return trim($user->getPrenom() . ' ' . $user->getNom());
    }
}