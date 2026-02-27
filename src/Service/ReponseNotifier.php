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
            'Nouvelle réponse',
            ['browser']
        );

        $notification->content(
            'Votre réclamation a reçu une réponse ✅'
        );

        // ✅ sans User
        $this->notifier->send($notification);
    }
}