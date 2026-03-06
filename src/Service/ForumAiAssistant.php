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
    private const POST_LIMIT = 10;
    private const LOGEMENT_LIMIT = 8;

    private const OPENAI_URL = 'https://openrouter.ai/api/v1/chat/completions';
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
        $this->model = self::OPENAI_MODEL;
    }

    public function ask(string $question): string
    {
        $question = trim($question);

        if ($question === '') {
            return $this->getHelpMessage();
        }

        $context = $this->buildWholeSiteContext();
        $fallback = $this->getFallbackReply($question);

        if ($this->apiKey === '') {
            return $fallback ?? $this->getHelpMessage();
        }

        $payload = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    [
                        'role'=>'system',
                        'content'=>$this->getSiteWidePrompt()
                    ],
                    [
                        'role'=>'user',
                        'content'=>$context."\n\nQuestion: ".$question
                    ]
                ],
                'max_tokens'=>700,
                'temperature'=>0.7
            ]
        ];

        try {

            $response = $this->httpClient->request(
                'POST',
                $this->apiUrl,
                $payload
            );

            $data = $response->toArray();

            return trim(
                $data['choices'][0]['message']['content'] ?? 'Erreur IA'
            );

        } catch (\Throwable $e) {

            return $fallback ?? $this->getHelpMessage(true);

        }
    }

    public function testConnection(): array
    {
        if ($this->apiKey === '') {
            return ['ok'=>false,'error'=>'API key missing'];
        }

        try {

            $response = $this->httpClient->request('POST',$this->apiUrl,[
                'headers'=>[
                    'Authorization'=>'Bearer '.$this->apiKey,
                    'Content-Type'=>'application/json'
                ],
                'json'=>[
                    'model'=>$this->model,
                    'messages'=>[
                        ['role'=>'user','content'=>'Say OK']
                    ],
                    'max_tokens'=>5
                ]
            ]);

            $data=$response->toArray();

            return [
                'ok'=>true,
                'message'=>$data['choices'][0]['message']['content'] ?? ''
            ];

        } catch (\Throwable $e) {

            return [
                'ok'=>false,
                'error'=>$e->getMessage()
            ];
        }
    }

    private function getSiteWidePrompt(): string
    {
        return <<<PROMPT
Tu es l'assistant intelligent officiel du site Stayzy.

Tu dois répondre uniquement avec les données fournies.

Si l'information n'existe pas dans les données du site,
réponds exactement :

"Je n'ai pas cette information dans les données actuelles du site Stayzy."
PROMPT;
    }

    private function buildWholeSiteContext(): string
    {
        $lines = [];

        $lines[]="=== ETAT GLOBAL DU SITE STAYZY ===";

        $lines[]="";
        $lines[]="Utilisateurs: ".$this->userRepository->count([]);

        $lines[]="Logements: ".$this->logementRepository->count([]);

        $lines[]="Réservations: ".$this->reservationRepository->count([]);

        $lines[]="Commandes: ".$this->commandeRepository->count([]);

        $lines[]="Promotions: ".$this->promotionRepository->count([]);

        $lines[]="Notifications: ".$this->notificationRepository->count([]);

        $lines[]="Réclamations: ".$this->reclamationRepository->count([]);

        $lines[]="Visites: ".$this->visiteRepository->count([]);

        $lines[]="";

        $lines[]="=== FORUM ===";

        $lines[]="Posts: ".$this->postRepository->count([]);

        $lines[]="Commentaires: ".$this->commentRepository->count([]);

        $lines[]="Réponses: ".$this->reponseRepository->count([]);

        $posts = $this->postRepository->findBy(
            [],
            ['createdAt'=>'DESC'],
            self::POST_LIMIT
        );

        foreach ($posts as $post) {

            $lines[] =
                "Post: ".$post->getTitle().
                " | Auteur: ".$post->getAuthor().
                " | Date: ".$post->getCreatedAt()?->format('d/m/Y');

        }

        $lines[]="";
        $lines[]="=== LOGEMENTS RÉCENTS ===";

        $logements = $this->logementRepository->findBy(
            [],
            ['id'=>'DESC'],
            self::LOGEMENT_LIMIT
        );

        foreach ($logements as $logement) {

            if(method_exists($logement,'getTitle')){

                $lines[] =
                    "Logement: ".$logement->getTitre().
                    " | Ville: ".$logement->getAdresse().
                    " | Prix: ".$logement->getPrix();

            }

        }

        return implode("\n",$lines);
    }

    private function getHelpMessage(bool $error=false): string
    {
        if($error){
            return "Je n'ai pas pu traiter votre demande.";
        }

        return
        "Exemples de questions:\n".
        "Combien de logements existe-t-il?\n".
        "Combien de posts sur le forum?\n".
        "Quel est le dernier post?\n".
        "Combien de réservations?\n".
        "Combien d'utilisateurs inscrits?";
    }

    private function getFallbackReply(string $question): ?string
    {
        $lower = mb_strtolower($question);

        if(str_contains($lower,'post')){

            $n=$this->postRepository->count([]);

            return "Il y a {$n} posts sur le forum.";
        }

        if(str_contains($lower,'commentaire')){

            $n=$this->commentRepository->count([]);

            return "Il y a {$n} commentaires.";
        }

        if(str_contains($lower,'logement')){

            $n=$this->logementRepository->count([]);

            return "Il y a {$n} logements sur la plateforme.";
        }

        if(str_contains($lower,'réservation')){

            $n=$this->reservationRepository->count([]);

            return "Il y a {$n} réservations.";
        }

        return null;
    }

    private function searchPostsByKeyword(string $keyword): array
    {
        $posts = $this->postRepository->findBy([],['createdAt'=>'DESC'],50);

        $keyword = mb_strtolower($keyword);

        $found = [];

        foreach($posts as $post){

            $search =
                mb_strtolower(
                    ($post->getTitle() ?? '').
                    ' '.
                    ($post->getContent() ?? '')
                );

            if(str_contains($search,$keyword)){
                $found[]=$post;
            }

        }

        return array_slice($found,0,5);
    }
}