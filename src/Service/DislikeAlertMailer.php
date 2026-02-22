<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Post;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class DislikeAlertMailer
{
    private const DISLIKE_THRESHOLD = 5;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $adminEmail,
        private readonly string $mailerFrom,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function sendTestEmail(): void
    {
        $subject = 'Test mail - Stayzy Forum';
        $text = "Ceci est un email de test.\nSi vous le voyez dans votre Mailtrap Inbox, l'envoi fonctionne correctement.";
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($this->adminEmail)
            ->subject('[Stayzy Forum] ' . $subject)
            ->text($text);
        $this->mailer->send($email);
    }

    public function sendPostAlertIfNeeded(Post $post): void
    {
        if ($post->getDislikeCount() >= self::DISLIKE_THRESHOLD) {
            $this->sendEmail(
                'Post fortement disliké - Action requise',
                sprintf(
                    "Le post \"%s\" (ID: %d) a atteint %d dislikes.\n\nAuteur: %s\nLien admin: %s\n\nVeuillez envisager de le supprimer ou le modérer.",
                    $post->getTitle(),
                    $post->getId(),
                    $post->getDislikeCount(),
                    $post->getAuthor(),
                    '/admin/forum/post/' . $post->getId()
                )
            );
        }
    }

    public function sendCommentAlertIfNeeded(Comment $comment): void
    {
        if ($comment->getDislikeCount() >= self::DISLIKE_THRESHOLD) {
            $this->sendEmail(
                'Commentaire fortement disliké - Action requise',
                sprintf(
                    "Un commentaire (ID: %d) sur le post \"%s\" a atteint %d dislikes.\n\nAuteur: %s\nContenu: %s\n\nVeuillez envisager de le supprimer.",
                    $comment->getId(),
                    $comment->getPost()->getTitle(),
                    $comment->getDislikeCount(),
                    $comment->getAuthor(),
                    substr($comment->getContent() ?? '', 0, 200)
                )
            );
        }
    }

    private function sendEmail(string $subject, string $text): void
    {
        try {
            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($this->adminEmail)
                ->replyTo($this->adminEmail)
                ->subject('[Stayzy Forum] ' . $subject)
                ->text($text);
            $this->mailer->send($email);
            $this->logger?->info('Dislike alert email sent', ['to' => $this->adminEmail, 'subject' => $subject]);
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to send dislike alert email', [
                'to' => $this->adminEmail,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - dislike action should succeed even if mail fails
        }
    }
}
