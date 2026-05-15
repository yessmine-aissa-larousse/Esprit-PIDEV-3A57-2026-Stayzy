<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CaptchaVerifier
{
    public function __construct(private RequestStack $requestStack) {}

    public function verify(string $submitted): bool
    {
        $session  = $this->requestStack->getSession();
        $expected = $session->get('captcha_code', '');
        $session->remove('captcha_code');

        return $expected !== '' && strtoupper(trim($submitted)) === strtoupper($expected);
    }
}
