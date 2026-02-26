<?php

namespace App\Service;

use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Notification\Notification;

class ReponseNotifier
{
    private NotifierInterface $notifier;

    public function __construct(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    public function notifyReponse(): void
    {
        $notification = new Notification(
            'Nouvelle réponse à votre réclamation',
            ['browser']
        );

        $notification->content(
            'Votre réclamation a reçu une réponse.'
        );

        $this->notifier->send($notification);
    }
}