<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Bloquer uniquement les comptes désactivés par l'admin
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été désactivé. Contactez l\'administrateur.'
            );
        }

        // Les propriétaires pending/rejetés peuvent se connecter
        // mais seront redirigés vers une page d'information
    }

    public function checkPostAuth(UserInterface $user): void {}
}
