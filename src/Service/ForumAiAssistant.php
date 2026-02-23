<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class ForumAiAssistant
{
    private const CONTENT_SNIPPET_LENGTH = 500;
    private const POST_CONTEXT_LIMIT = 20;
    private const COMMENT_CONTEXT_LIMIT = 25;

    private const OPENAI_URL = 'https://api.openai.com/v1/chat/completions';
    private const OPENAI_MODEL = 'gpt-4o-mini';

    private readonly string $apiKey;
    private readonly string $apiUrl;
    private readonly string $model;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly HttpClientInterface $httpClient,
        ?string $apiKey = null,
    ) {
        $this->apiKey = $apiKey ?? '';
        $this->apiUrl = self::OPENAI_URL;
        $this->model = self::OPENAI_MODEL;
    }

    public function ask(string $question): string
    {
        $question = $this->normalizeQuestion($question);
        if ($question === '') {
            return $this->getHelpMessage();
        }
        $context = $this->buildForumContext();
        $fallback = $this->getFallbackReply($question);

        if ($this->apiKey === '') {
            return $fallback ?? $this->getHelpMessage();
        }

        $systemPrompt = $this->getSystemPrompt();
        $userContent = "=== DONNÉES DU FORUM STAYZY (à utiliser pour répondre) ===\n\n" . $context . "\n\n=== QUESTION DE L'UTILISATEUR ===\n" . $question;

        $payload = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'max_tokens' => 700,
            ],
        ];

        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->httpClient->request('POST', $this->apiUrl, $payload);
                $data = $response->toArray();
                $reply = $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu générer de réponse.';
                return trim($reply);
            } catch (\Throwable $e) {
                $is429 = false;
                if ($e instanceof ClientExceptionInterface) {
                    $is429 = $e->getResponse()->getStatusCode() === 429;
                } else {
                    $is429 = str_contains($e->getMessage(), '429');
                }
                if ($is429 && $attempt < $maxAttempts) {
                    usleep(2 * $attempt * 1_000_000);
                    continue;
                }
                break;
            }
        }

        return $fallback ?? $this->getHelpMessage(true);
    }

    /**
     * Test the API connection. Returns ['ok' => true] or ['ok' => false, 'error' => string].
     */
    public function testConnection(): array
    {
        if ($this->apiKey === '') {
            return ['ok' => false, 'error' => 'No API key configured. Set OPENAI_API_KEY in .env'];
        }
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [['role' => 'user', 'content' => 'Say OK']],
                    'max_tokens' => 5,
                ],
            ]);
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            return ['ok' => true, 'message' => trim($content)];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($e instanceof ClientExceptionInterface && $e->getResponse()) {
                $code = $e->getResponse()->getStatusCode();
                $msg = "HTTP {$code}: " . $msg;
            }
            return ['ok' => false, 'error' => $msg];
        }
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
Tu es l'assistant du forum Stayzy (plateforme de location de logements). Tu réponds UNIQUEMENT à partir des données du forum fournies dans le message utilisateur.

RÈGLES STRICTES:
1. Base tes réponses uniquement sur les posts, commentaires et statistiques fournis. Cite les titres exacts, auteurs et chiffres.
2. Quand on te demande "de quoi parlent les posts", "quels sont les sujets", "résume le forum": synthétise le contenu réel des posts (titres et extraits) et liste les thèmes présents dans les données.
3. Quand on te demande "quel post parle de X" ou "qui a parlé de Y": repère dans les titres et contenus les posts concernés et donne titre, auteur et un court résumé du contenu.
4. Pour les questions sur les likes, dislikes, nombre de posts/commentaires: utilise les chiffres fournis dans les données.
5. Réponds en français, de façon concise et utile. Si la question est floue ou contient des fautes, interprète-la avec bienveillance.
6. Si l'information demandée n'apparaît nulle part dans les données fournies, dis clairement: "Je n'ai pas cette information dans les données du forum."
7. Ne invente jamais de posts, d'auteurs ni de chiffres. Tout doit provenir des données fournies.
PROMPT;
    }

    private function buildForumContext(): string
    {
        $posts = $this->postRepository->findBy(
            ['isPublished' => true],
            ['createdAt' => 'DESC'],
            self::POST_CONTEXT_LIMIT
        );
        $totalPosts = $this->postRepository->countPublished();
        $totalComments = $this->commentRepository->count([]);

        $lines = [
            "--- STATISTIQUES GLOBALES ---",
            "Total: {$totalPosts} posts publiés, {$totalComments} commentaires.",
            "",
            "--- POSTS (titre, auteur, date, likes, dislikes, nb commentaires, CONTENU) ---",
        ];

        foreach ($posts as $post) {
            $content = $post->getExcerpt() ?: $post->getContent();
            $content = strip_tags($content ?? '');
            $contentSnippet = mb_substr($content, 0, self::CONTENT_SNIPPET_LENGTH);
            if (mb_strlen($content) > self::CONTENT_SNIPPET_LENGTH) {
                $contentSnippet .= '...';
            }
            $date = $post->getCreatedAt()?->format('d/m/Y') ?? '';
            $lines[] = sprintf(
                "[Post #%d] Titre: « %s » | Auteur: %s | Date: %s | Likes: %d | Dislikes: %d | Commentaires: %d",
                $post->getId(),
                $post->getTitle(),
                $post->getAuthor(),
                $date,
                $post->getAvisCount(),
                $post->getDislikeCount(),
                $post->getCommentsCount()
            );
            $lines[] = "Contenu: " . $contentSnippet;
            $lines[] = "";
        }

        $recentComments = $this->commentRepository->findBy([], ['createdAt' => 'DESC'], self::COMMENT_CONTEXT_LIMIT);
        $lines[] = "--- COMMENTAIRES RÉCENTS (contenu, auteur, post lié, date) ---";
        foreach ($recentComments as $c) {
            $content = mb_substr($c->getContent() ?? '', 0, 250);
            if (mb_strlen($c->getContent() ?? '') > 250) {
                $content .= '...';
            }
            $postTitle = $c->getPost()?->getTitle() ?? '?';
            $date = $c->getCreatedAt()?->format('d/m/Y H:i') ?? '';
            $lines[] = sprintf(
                "[Com.#%d] « %s » — par %s (post: « %s ») — %s",
                $c->getId(),
                $content,
                $c->getAuthor(),
                $postTitle,
                $date
            );
        }
        $lines[] = "";

        $lines[] = "--- TOP 3 POSTS LES PLUS AIMÉS (likes) ---";
        foreach ($this->postRepository->findMostLiked(3) as $i => $p) {
            $lines[] = sprintf('%d. « %s » par %s (%d likes)', $i + 1, $p->getTitle(), $p->getAuthor(), $p->getAvisCount());
        }
        $lines[] = "";
        $lines[] = "--- TOP 3 POSTS LES PLUS DISLIKÉS ---";
        foreach ($this->postRepository->findMostDisliked(3) as $i => $p) {
            $lines[] = sprintf('%d. « %s » par %s (%d dislikes)', $i + 1, $p->getTitle(), $p->getAuthor(), $p->getDislikeCount());
        }

        return implode("\n", $lines);
    }

    private function normalizeQuestion(string $question): string
    {
        $q = trim(preg_replace('/\s+/u', ' ', $question));
        $q = preg_replace('/\by\s+a\s+t\s*[\']?\s*il\b/ui', 'y a-t-il', $q);
        $q = preg_replace('/\by\s+at\s+il\b/ui', 'y a-t-il', $q);
        $q = preg_replace('/\bil\s+y\s+a\s+t\s*[\']?\s*il\b/ui', 'y a-t-il', $q);
        return trim($q);
    }

    private function getHelpMessage(bool $afterError = false): string
    {
        $intro = $afterError
            ? "Je n'ai pas pu traiter votre message. Voici des exemples que je comprends bien :"
            : "Posez une question sur le forum. Exemples :";
        return $intro . "\n• Combien de posts ?\n• Y a-t-il des posts ? / Dernier post ?\n• Post le plus populaire ?\n• Quel post parle de logement ?\n• De quoi parlent les posts ?";
    }

    private function extractKeywordAfterIsThere(string $lower): ?string
    {
        if (preg_match('/(?:y\s*a-t-il|il y a|est-ce qu\'?il y a|ya ?t ?il)\s+(.+)/u', $lower, $m)) {
            $rest = trim($m[1], " \t\n\r\x0B?");
            $generic = ['des posts', 'des commentaires', 'du forum', 'posts', 'commentaires', 'du contenu', 'un post', 'une publication'];
            if ($rest !== '' && mb_strlen($rest) >= 1 && !in_array($rest, $generic, true)) {
                return $rest;
            }
        }
        return null;
    }

    private function getFallbackReply(string $question): ?string
    {
        $lower = mb_strtolower(trim($question));

        if (preg_match('/y\s*a-t-il|il y a|est-ce qu\'?il y a|ya ?t ?il/u', $lower)) {
            $keyword = $this->extractKeywordAfterIsThere($lower);
            if (preg_match('/commentaire/u', $lower) && ($keyword === null || $keyword === '')) {
                $n = $this->commentRepository->count([]);
                return "Oui, il y a {$n} commentaire(s) sur le forum.";
            }
            if ($keyword !== null && $keyword !== '') {
                $found = $this->searchPostsByKeyword($keyword);
                if (!empty($found)) {
                    $list = array_map(static fn (Post $p) => "« {$p->getTitle()} » par {$p->getAuthor()}", $found);
                    return "Oui, il y a le(s) post(s) :\n" . implode("\n", $list);
                }
                return "Non, aucun post trouvé pour « {$keyword} ».";
            }
            $n = $this->postRepository->countPublished();
            return "Oui, il y a {$n} post(s) publié(s) sur le forum Stayzy.";
        }

        if (preg_match('/likes?|dislikes?|aimés?|avis/u', $lower) && preg_match('/combien|(likes?|dislikes?).*(post|article|seul)/u', $lower)) {
            $posts = $this->postRepository->findBy(['isPublished' => true], ['createdAt' => 'DESC'], 10);
            if (empty($posts)) {
                return "Il n'y a aucun post publié sur le forum.";
            }
            if (count($posts) === 1) {
                $p = $posts[0];
                if (preg_match('/dislike/u', $lower)) {
                    return "Le post « {$p->getTitle()} » par {$p->getAuthor()} a {$p->getDislikeCount()} dislike(s).";
                }
                return "Le post « {$p->getTitle()} » par {$p->getAuthor()} a {$p->getAvisCount()} like(s).";
            }
            $lines = [];
            foreach (array_slice($posts, 0, 5) as $p) {
                $lines[] = "« {$p->getTitle()} » : {$p->getAvisCount()} like(s), {$p->getDislikeCount()} dislike(s).";
            }
            return "Il y a " . count($posts) . " post(s). " . implode(' ', $lines);
        }

        if (preg_match('/combien.*(post|article|publication)/u', $lower) || preg_match('/(nb|nombre).*post/u', $lower)) {
            $n = $this->postRepository->countPublished();
            return "Il y a actuellement {$n} post(s) publié(s) sur le forum Stayzy.";
        }

        if (preg_match('/combien.*(commentaire|avis)/u', $lower)) {
            $n = $this->commentRepository->count([]);
            return "Le forum contient {$n} commentaire(s) au total.";
        }

        if (preg_match('/(plus )?(populaire|aimé|liké|meilleur)/u', $lower)) {
            $top = $this->postRepository->findMostLiked(1);
            if (!empty($top)) {
                $p = $top[0];
                return "Le post le plus aimé est « {$p->getTitle()} » par {$p->getAuthor()} avec {$p->getAvisCount()} likes et {$p->getCommentsCount()} commentaires.";
            }
        }

        if (preg_match('/(plus )?(disliké|désaimé)/u', $lower)) {
            $top = $this->postRepository->findMostDisliked(1);
            if (!empty($top)) {
                $p = $top[0];
                return "Le post le plus disliké est « {$p->getTitle()} » par {$p->getAuthor()} avec {$p->getDislikeCount()} dislikes.";
            }
        }

        if (preg_match('/(dernier|récent|récemment|nouveau)/u', $lower)) {
            $last = $this->postRepository->findBy(['isPublished' => true], ['createdAt' => 'DESC'], 1);
            if (!empty($last)) {
                $p = $last[0];
                $date = $p->getCreatedAt()?->format('d/m/Y') ?? '';
                return "Le dernier post est « {$p->getTitle()} » par {$p->getAuthor()} ({$date}). {$p->getCommentsCount()} commentaire(s), {$p->getAvisCount()} likes.";
            }
        }

        if (preg_match('/parle.*(de |du |d\'|sur )?(.+)/u', $lower, $m) || preg_match('/(quel|quelle|quels).*(post|article).*(parle|traite|sujet|sur)/u', $lower)) {
            $keyword = isset($m[2]) ? trim($m[2], " \t\n\r\0\x0B?") : preg_replace('/^(quel|quelle|quels|post|article|parle|traite|sujet|sur|de|du)\s*/u', '', $lower);
            $keyword = trim($keyword, " \t\n\r\0\x0B?");
            if (mb_strlen($keyword) >= 2) {
                $found = $this->searchPostsByKeyword($keyword);
                if (!empty($found)) {
                    $list = array_map(static fn (Post $p) => "« {$p->getTitle()} » par {$p->getAuthor()}", $found);
                    return "Posts trouvés sur « {$keyword} » :\n" . implode("\n", $list);
                }
            }
        }

        if (mb_strlen(trim($question)) >= 2 && mb_strlen(trim($question)) <= 50 && !preg_match('/\?$|^(combien|quel|quelle|qui|comment|pourquoi)/u', $lower)) {
            $found = $this->searchPostsByKeyword(trim($question));
            if (!empty($found)) {
                $list = array_map(static fn (Post $p) => "« {$p->getTitle()} » par {$p->getAuthor()}", $found);
                return "Posts trouvés pour « " . trim($question) . " » :\n" . implode("\n", $list);
            }
        }

        if (preg_match('/(qui )?(a )?(posté|écrit|publié) (le )?plus/u', $lower)) {
            $allPosts = $this->postRepository->findBy(['isPublished' => true], []);
            $byAuthor = [];
            foreach ($allPosts as $p) {
                $a = $p->getAuthor() ?? 'Inconnu';
                $byAuthor[$a] = ($byAuthor[$a] ?? 0) + 1;
            }
            arsort($byAuthor);
            $top = array_slice(array_keys($byAuthor), 0, 3);
            return 'Auteurs les plus actifs (par nombre de posts) : ' . implode(', ', $top) . '.';
        }

        return null;
    }

    /** @return Post[] */
    private function searchPostsByKeyword(string $keyword): array
    {
        $posts = $this->postRepository->findBy(['isPublished' => true], ['createdAt' => 'DESC'], 50);
        $keyword = mb_strtolower($keyword);
        $found = [];
        foreach ($posts as $post) {
            $searchIn = mb_strtolower(($post->getTitle() ?? '') . ' ' . ($post->getContent() ?? '') . ' ' . ($post->getExcerpt() ?? ''));
            if (str_contains($searchIn, $keyword)) {
                $found[] = $post;
            }
        }
        return array_slice($found, 0, 5);
    }
}
