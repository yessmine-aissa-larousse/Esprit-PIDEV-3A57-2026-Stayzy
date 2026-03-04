<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use Twilio\Rest\Client;

class SmsService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function sendSms(string $phone, string $message): void
    {
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'];
        $authToken  = $_ENV['TWILIO_AUTH_TOKEN'];
        $from       = $_ENV['TWILIO_FROM'];

        $this->logger->info('[SMS] Tentative envoi SMS', [
            'phone'   => $phone,
            'message' => $message,
        ]);

        $url  = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        $data = ['From' => $from, 'To' => $phone, 'Body' => $message];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ✅ Fix SSL local
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error('[SMS] ❌ Erreur curl', ['error' => $error]);
            throw new \RuntimeException('Erreur curl : ' . $error);
        }

        $json = json_decode($response, true);

        if (isset($json['error_code']) && $json['error_code'] !== null) {
            $this->logger->error('[SMS] ❌ Erreur Twilio', ['response' => $json]);
            throw new \RuntimeException('Erreur Twilio : ' . $json['error_message']);
        }

        $this->logger->info('[SMS] ✅ SMS envoyé', [
            'phone'  => $phone,
            'sid'    => $json['sid'] ?? 'N/A',
            'status' => $json['status'] ?? 'N/A',
        ]);
    }
}