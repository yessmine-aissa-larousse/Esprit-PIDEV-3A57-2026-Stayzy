<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ForumAiAssistant
{
    private const CONTENT_SNIPPET_LENGTH = 300;

    private readonly string $apiKey;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly HttpClientInterface $httpClient,
        ?string $apiKey = null,
        private readonly string $apiUrl = 'https://api.openai.com/v1/chat/completions',
    ) {
        $this->apiKey = $apiKey ?? '';
    }

    public function ask(string $question): string
    {
        $context = $this->buildForumContext();
        $fallback = $this->getFallbackReply($question);

        if ($this->apiKey === '') {
            return $fallback ?? 'Configurez OPENAI_API_KEY dans .env pour des réponses plus riches. Sinon : "Combien de posts ?", "Post le plus populaire ?", "Dernier post ?"';
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => <<<PROMPT
Tu es l'assistant intelligent du forum Stayzy (location de logements). Tu réponds UNIQUEMENT à partir des données du forum fournies.
Règles : réponds en français, de façon concise et utile. Cite les titres, auteurs et chiffres exacts des données.
Si la question porte sur un sujet présent dans les posts (ex: logement, location, quartier), synthétise les infos pertinentes.
Pour "quel post parle de X", recherche dans titres et contenus.
Si aucune donnée ne correspond, dis que tu n'as pas l'information dans le forum.
PROMPT,
                        ],
                        [
                            'role' => 'user',
                            'content' => "=== DONNÉES DU FORUM STAYZY ===\n\n" . $context . "\n\n=== QUESTION ===\n" . $question,
                        ],
                    ],
                    'max_tokens' => 600,
                ],
            ]);

            $data = $response->toArray();
            $reply = $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu générer de réponse.';

            return trim($reply);
        } catch (\Throwable $e) {
            return $fallback ?? 'Une erreur est survenue. Essayez : "Combien de posts ?", "Quel post parle de logement ?"';
        }
    }

    private function buildForumContext(): string
    {
        $posts = $this->postRepository->findBy(['isPublished' => true], ['createdAt' => 'DESC'], 25);
        $totalPosts = $this->postRepository->countPublished();
        $totalComments = $this->commentRepository->count([]);

        $lines = ["=== STATISTIQUES ===\nTotal: {$totalPosts} posts, {$totalComments} commentaires\n"];

        $lines[] = "\n=== POSTS (titre, auteur, contenu/extrait, likes, dislikes, commentaires, date) ===\n";
        foreach ($posts as $post) {
            $content = $post->getExcerpt() ?: $post->getContent();
            $contentSnippet = mb_substr(strip_tags($content ?? ''), 0, self::CONTENT_SNIPPET_LENGTH);
            if (mb_strlen($content ?? '') > self::CONTENT_SNIPPET_LENGTH) {
                $contentSnippet .= '...';
            }

            $lines[] = sprintf(
                "[Post #%d] \"%s\"\n  Auteur: %s | %s | likes: %d, dislikes: %d, %d commentaire(s)\n  Contenu: %s",
                $post->getId(),
                $post->getTitle(),
                $post->getAuthor(),
                $post->getCreatedAt()?->format('d/m/Y') ?? '',
                $post->getAvisCount(),
                $post->getDislikeCount(),
                $post->getCommentsCount(),
                $contentSnippet
            );
            $lines[] = '';
        }

        $recentComments = $this->commentRepository->findBy([], ['createdAt' => 'DESC'], 20);
        $lines[] = "\n=== COMMENTAIRES RÉCENTS (contenu, auteur, post lié, likes, dislikes, date) ===\n";
        foreach ($recentComments as $c) {
            $content = mb_substr($c->getContent() ?? '', 0, 200);
            if (mb_strlen($c->getContent() ?? '') > 200) {
                $content .= '...';
            }
            $lines[] = sprintf(
                "[Com.#%d] \"%s\" — par %s sur post \"%s\" | likes: %d, dislikes: %d | %s",
                $c->getId(),
                $content,
                $c->getAuthor(),
                $c->getPost()?->getTitle() ?? '?',
                $c->getAvisCount(),
                $c->getDislikeCount(),
                $c->getCreatedAt()?->format('d/m/Y H:i') ?? ''
            );
        }

        $lines[] = "\n=== TOP 3 POSTS LES PLUS AIMÉS ===\n";
        foreach ($this->postRepository->findMostLiked(3) as $i => $p) {
            $lines[] = sprintf('%d. "%s" par %s (%d likes)', $i + 1, $p->getTitle(), $p->getAuthor(), $p->getAvisCount());
        }

        $lines[] = "\n=== TOP 3 POSTS LES PLUS DISLIKÉS ===\n";
        foreach ($this->postRepository->findMostDisliked(3) as $i => $p) {
            $lines[] = sprintf('%d. "%s" (%d dislikes)', $i + 1, $p->getTitle(), $p->getDislikeCount());
        }

        return implode("\n", $lines);
    }

    private function getFallbackReply(string $question): ?string
    {
        $lower = mb_strtolower(trim($question));

        // Combien de posts / articles
        if (preg_match('/combien.*(post|article|publication)/u', $lower) || preg_match('/(nb|nombre).*post/u', $lower)) {
            $n = $this->postRepository->countPublished();
            return "Il y a actuellement {$n} post(s) publié(s) sur le forum Stayzy.";
        }

        // Combien de commentaires
        if (preg_match('/combien.*(commentaire|avis)/u', $lower)) {
            $n = $this->commentRepository->count([]);
            return "Le forum contient {$n} commentaire(s) au total.";
        }

        // Post le plus populaire / aimé
        if (preg_match('/(plus )?(populaire|aimé|liké|meilleur)/u', $lower)) {
            $top = $this->postRepository->findMostLiked(1);
            if (!empty($top)) {
                $p = $top[0];
                return "Le post le plus aimé est « {$p->getTitle()} » par {$p->getAuthor()} avec {$p->getAvisCount()} likes et {$p->getCommentsCount()} commentaires.";
            }
        }

        // Post le plus disliké
        if (preg_match('/(plus )?(disliké|désaimé)/u', $lower)) {
            $top = $this->postRepository->findMostDisliked(1);
            if (!empty($top)) {
                $p = $top[0];
                return "Le post le plus disliké est « {$p->getTitle()} » par {$p->getAuthor()} avec {$p->getDislikeCount()} dislikes.";
            }
        }

        // Dernier post / récent
        if (preg_match('/(dernier|récent|récemment|nouveau)/u', $lower)) {
            $last = $this->postRepository->findBy(['isPublished' => true], ['createdAt' => 'DESC'], 1);
            if (!empty($last)) {
                $p = $last[0];
                $date = $p->getCreatedAt()?->format('d/m/Y') ?? '';
                return "Le dernier post est « {$p->getTitle()} » par {$p->getAuthor()} ({$date}). {$p->getCommentsCount()} commentaire(s), {$p->getAvisCount()} likes.";
            }
        }

        // Recherche par mot-clé dans titres/contenus
        if (preg_match('/parle.*(de |du |d\'|sur )?(.+)/u', $lower, $m) || preg_match('/(quel|quelle|quels).*(post|article).*(parle|traite|sujet|sur)/u', $lower)) {
            $keyword = $m[2] ?? preg_replace('/^(quel|quelle|quels|post|article|parle|traite|sujet|sur|de|du)\s*/u', '', $lower);
            $keyword = trim($keyword, " \t\n\r\0\x0B?");
            if (mb_strlen($keyword) >= 2) {
                $found = $this->searchPostsByKeyword($keyword);
                if (!empty($found)) {
                    $list = array_map(static fn (Post $p) => "« {$p->getTitle()} » par {$p->getAuthor()}", $found);
                    return "Posts trouvés sur « {$keyword} » :\n" . implode("\n", $list);
                }
            }
        }

        // Recherche par mot-clé simple (ex: "logement", "location")
        if (mb_strlen(trim($question)) >= 2 && mb_strlen(trim($question)) <= 50 && !preg_match('/\?$|^(combien|quel|quelle|qui|comment|pourquoi)/u', $lower)) {
            $found = $this->searchPostsByKeyword(trim($question));
            if (!empty($found)) {
                $list = array_map(static fn (Post $p) => "« {$p->getTitle()} » par {$p->getAuthor()}", $found);
                return "Posts trouvés pour « " . trim($question) . " » :\n" . implode("\n", $list);
            }
        }

        // Auteur le plus actif (posts)
        if (preg_match('/(qui )?(a )?(posté|écrit|publié) (le )?plus/u', $lower)) {
            $posts = $this->postRepository->findBy(['isPublished' => true], []);
            $byAuthor = [];
            foreach ($posts as $p) {
                $a = $p->getAuthor() ?? 'Inconnu';
                $byAuthor[$a] = ($byAuthor[$a] ?? 0) + 1;
            }
            arsort($byAuthor);
            $top = array_slice(array_keys($byAuthor), 0, 3);
            return 'Auteurs les plus actifs (par nombre de posts) : ' . implode(', ', $top) . '.';
        }

        return null;
    }

    /**
     * @return Post[]
     */
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
