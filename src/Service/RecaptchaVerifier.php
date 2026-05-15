<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaVerifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $secretKey,
    ) {}

    public function verify(string $token, string $remoteIp = ''): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret'   => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ],
                'timeout' => 5,
            ]);
            $data = $response->toArray();
            return $data['success'] ?? false;
        } catch (\Throwable) {
            return true; // fail open if Google is unreachable
        }
    }
}
