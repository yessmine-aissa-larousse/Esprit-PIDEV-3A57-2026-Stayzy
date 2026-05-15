<?php

namespace App\EventSubscriber;

use App\Service\CaptchaVerifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class LoginCaptchaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CaptchaVerifier $captchaVerifier,
        private RouterInterface $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 10]];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only check POST to the login route
        if (!$request->isMethod('POST') || $request->attributes->get('_route') !== 'app_login') {
            return;
        }

        $captchaInput = $request->request->get('captcha_code', '');
        if (!$this->captchaVerifier->verify($captchaInput)) {
            $request->getSession()->getFlashBag()->add('error', 'Code CAPTCHA incorrect. Veuillez réessayer.');
            $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
        }
    }
}
