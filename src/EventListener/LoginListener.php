<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginListener
{
    public function __construct(private RouterInterface $router) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Bloquer propriétaires non approuvés
        if ($user->isProprietaire()) {
            if ($user->isPending()) {
                // Déconnecter et rediriger
                $event->setResponse(new RedirectResponse(
                    $this->router->generate('app_login') . '?status=pending'
                ));
                return;
            }

            if ($user->isRejected()) {
                $event->setResponse(new RedirectResponse(
                    $this->router->generate('app_login') . '?status=rejected'
                ));
                return;
            }
        }
    }
}