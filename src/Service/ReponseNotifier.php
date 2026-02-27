<?php

namespace App\Service;

use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use App\Entity\User;

class ReponseNotifier
{
    private NotifierInterface $notifier;

    public function __construct(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    public function notifyReponse(User $client): void
    {
        $notification = new Notification(
            'Nouvelle réponse à votre réclamation',
            ['browser']
        );

        $notification->content(
            'Votre réclamation a reçu une réponse.'
        );

        $recipient = new Recipient(
            $client->getEmail()
        );

        $this->notifier->send($notification, $recipient);
    }
}