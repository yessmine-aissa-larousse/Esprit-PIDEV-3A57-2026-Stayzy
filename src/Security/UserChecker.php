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

        if ($user->isProprietaire() && !$user->isApproved()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte propriétaire est en attente de validation par l’administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Pas de vérification supplémentaire après authentification
    }
}