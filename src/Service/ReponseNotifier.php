<?php

namespace App\Service;

use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use App\Entity\User;

class ReponseNotifier
{
    public function __construct(private NotifierInterface $notifier) {}

    public function notifyReponse(User $client): void
    {
        $notification = (new Notification(
            'Nouvelle réponse à votre réclamation',
            ['browser']
        ))->content('Votre réclamation a reçu une réponse ✅');

        $this->notifier->send(
            $notification,
            new Recipient($client->getEmail())
        );
    }
}