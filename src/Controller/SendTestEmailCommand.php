<?php

namespace App\Controller;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(name: 'app:send-test-email', description: 'Envoie un email de test via le transport configuré')]
class SendTestEmailCommand extends Command
{
    public function __construct(private MailerInterface $mailer, private string $mailerFrom)
{
    parent::__construct();
}

    protected function configure(): void
    {
        $this->addOption('to', null, InputOption::VALUE_REQUIRED, 'Adresse email destinataire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = $input->getOption('to');
        if (!$to) {
            $output->writeln('<error>Option --to requise.</error>');
            return Command::FAILURE;
        }

        $from = $_SERVER['MAILER_FROM'] ?? 'no-reply@localhost';
        if (!$from) {
            $output->writeln('<comment>MAILER_FROM non défini ; utilisation de no-reply@localhost</comment>');
            $from = 'no-reply@localhost';
        }

        $email = (new TemplatedEmail())
            ->from(new Address($from, 'Stayzy Test'))
            ->to($to)
            ->subject('Test d\'envoi - Stayzy')
            ->text('Ceci est un test d\'envoi d\'email depuis l\'application Stayzy.')
            ->html('<p>Ceci est un <strong>test</strong> d\'envoi d\'email depuis l\'application Stayzy.</p>');

        try {
            $this->mailer->send($email);
            $output->writeln('<info>Email envoyé (ou transmis au transport).</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Échec envoi email : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
