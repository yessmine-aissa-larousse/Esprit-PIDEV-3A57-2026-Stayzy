<?php

namespace App\Command;

use App\Service\ForumAiAssistant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-openai',
    description: 'Test the OpenAI API for the forum chatbot',
)]
final class TestOpenAiCommand extends Command
{
    public function __construct(
        private readonly ForumAiAssistant $forumAiAssistant,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Testing chat AI connection...');

        $result = $this->forumAiAssistant->testConnection();

        if ($result['ok']) {
            $io->success('API is working. Response: ' . ($result['message'] ?? 'OK'));
            return Command::SUCCESS;
        }

        $io->error('API test failed: ' . ($result['error'] ?? 'Unknown error'));
        $io->note('Ensure OPENAI_API_KEY is set in .env. If using Docker, run: docker compose up -d --force-recreate php');
        return Command::FAILURE;
    }
}
