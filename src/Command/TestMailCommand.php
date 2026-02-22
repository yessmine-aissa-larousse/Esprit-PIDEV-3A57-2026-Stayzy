<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-mail',
    description: 'Send a test email to verify mailer configuration',
)]
final class TestMailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $adminEmail,
        private readonly string $mailerFrom,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Sending test email to: ' . $this->adminEmail);
        $io->info('From: ' . $this->mailerFrom);

        try {
            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($this->adminEmail)
                ->subject('[Stayzy Forum] Test mail')
                ->text('Test email - if you see this in Mailtrap, it works!');
            $this->mailer->send($email);
            $io->success('Email sent! Check your Mailtrap inbox.');
        } catch (\Throwable $e) {
            $io->error('Failed to send: ' . $e->getMessage());
            $io->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
