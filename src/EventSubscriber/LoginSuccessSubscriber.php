<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(private RouterInterface $router) {}

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }

  public function onLoginSuccess(LoginSuccessEvent $event): void
{
    $user = $event->getUser();
    $roles = $user->getRoles();
    dump($roles); // ← TEMPORAIRE pour debug
    
    if (in_array('ROLE_ADMIN', $roles, true)) {
        $url = $this->router->generate('admin_dashboard');
    } elseif (in_array('ROLE_PROPRIETAIRE', $roles, true)) {
        $url = $this->router->generate('app_proprietaire_profile');
    } elseif (in_array('ROLE_CLIENT', $roles, true)) {
        $url = $this->router->generate('app_client_dashboard');
    } else {
        $url = $this->router->generate('app_home');
    }

    $event->setResponse(new RedirectResponse($url));
}
}