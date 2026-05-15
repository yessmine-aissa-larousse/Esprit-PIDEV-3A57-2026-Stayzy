<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\CategorieRepository;
use App\Repository\CommandeRepository;
use App\Repository\CommentRepository;
use App\Repository\LogementRepository;
use App\Repository\NotificationRepository;
use App\Repository\PostRepository;
use App\Repository\PromotionRepository;
use App\Repository\ReclamationRepository;
use App\Repository\ReponseRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\VisiteRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class ForumAiAssistant
{
    private const POST_LIMIT     = 10;
    private const LOGEMENT_LIMIT = 8;

    private const OPENAI_URL   = 'https://openrouter.ai/api/v1/chat/completions';
    private const OPENAI_MODEL = 'meta-llama/llama-3.3-70b-instruct';

    private readonly string $apiKey;
    private readonly string $apiUrl;
    private readonly string $model;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly LogementRepository $logementRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly UserRepository $userRepository,
        private readonly ReclamationRepository $reclamationRepository,
        private readonly CategorieRepository $categorieRepository,
        private readonly CommandeRepository $commandeRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly PromotionRepository $promotionRepository,
        private readonly ReponseRepository $reponseRepository,
        private readonly VisiteRepository $visiteRepository,
        private readonly HttpClientInterface $httpClient,
        ?string $apiKey = null,
    ) {
        $this->apiKey = $apiKey ?? '';
        $this->apiUrl = self::OPENAI_URL;
        $this->model  = self::OPENAI_MODEL;
    }

    public function ask(string $question): string
    {
        $question = trim($question);
        if ($question === '') {
            return $this->getHelpMessage();
        }

        $context  = $this->buildWholeSiteContext();
        $fallback = $this->getFallbackReply($question);

        if ($this->apiKey === '') {
            return $fallback ?? $this->getHelpMessage();
        }

        $payload = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'       => $this->model,
                'messages'    => [
                    ['role' => 'system', 'content' => $this->getSiteWidePrompt()],
                    ['role' => 'user',   'content' => $context . "\n\nQuestion: " . $question]
                ],
                'max_tokens'  => 800,
                'temperature' => 0.7,
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, $payload);
            $data = $response->toArray();
            return trim($data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu générer de réponse.');
        } catch (\Throwable $e) {
            return $fallback ?? $this->getHelpMessage(true);
        }
    }

    public function testConnection(): array
    {
        if ($this->apiKey === '') {
            return ['ok' => false, 'error' => 'API key missing'];
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'    => $this->model,
                    'messages' => [['role' => 'user', 'content' => 'Say OK']],
                    'max_tokens' => 5,
                ],
            ]);

            $data = $response->toArray();
            return [
                'ok' => true,
                'message' => $data['choices'][0]['message']['content'] ?? ''
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function getSiteWidePrompt(): string
    {
        return <<<PROMPT
Tu es l'assistant intelligent officiel de Stayzy.
Réponds uniquement avec les données fournies.
Si l'information n'existe pas, dis exactement :
"Je n'ai pas cette information dans les données actuelles du site Stayzy."
PROMPT;
    }

    private function buildWholeSiteContext(): string
    {
        $lines = ["=== ÉTAT GLOBAL DU SITE STAYZY ==="];

        $lines[] = "Utilisateurs : " . $this->userRepository->count([]);
        $lines[] = "Logements : " . $this->logementRepository->count([]);
        $lines[] = "Réservations : " . $this->reservationRepository->count([]);
        $lines[] = "Réclamations : " . $this->reclamationRepository->count([]);
        $lines[] = "Posts : " . $this->postRepository->count([]);

        return implode("\n", $lines);
    }

    private function getHelpMessage(bool $error = false): string
    {
        if ($error) {
            return "Je n'ai pas pu traiter votre demande.";
        }
        return "Exemples :\n• Combien de logements ?\n• Combien de posts ?\n• Combien de réservations ?";
    }

    private function getFallbackReply(string $question): ?string
    {
        $lower = mb_strtolower($question);
        if (str_contains($lower, 'logement')) {
            return "Il y a " . $this->logementRepository->count([]) . " logements.";
        }
        if (str_contains($lower, 'post')) {
            return "Il y a " . $this->postRepository->count([]) . " posts.";
        }
        if (str_contains($lower, 'réservation')) {
            return "Il y a " . $this->reservationRepository->count([]) . " réservations.";
        }
        return null;
    }
}