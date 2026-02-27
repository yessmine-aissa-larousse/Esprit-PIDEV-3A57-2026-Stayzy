<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\DislikeAlertMailer;
use App\Service\ForumAiAssistant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forum')]
final class ForumController extends AbstractController
{
    private const POSTS_PER_PAGE = 9;
    private const SESSION_AUTHOR_KEY = 'forum_author';

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly DislikeAlertMailer $dislikeAlertMailer,
        private readonly ForumAiAssistant $forumAiAssistant,
    ) {
    }

    #[Route('/test-mail', name: 'forum_test_mail', methods: ['GET'])]
    public function testMail(): Response
    {
        try {
            $this->dislikeAlertMailer->sendTestEmail();
            $this->addFlash('success', 'Email de test envoyé ! Vérifiez votre Mailtrap Inbox (mailtrap.io), pas Gmail.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        return $this->redirectToRoute('forum_index');
    }

    #[Route('', name: 'forum_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::POSTS_PER_PAGE;
        $posts = $this->postRepository->searchPublished($q, self::POSTS_PER_PAGE, $offset);
        $total = $this->postRepository->countSearchPublished($q);
        $totalPages = max(1, (int) ceil($total / self::POSTS_PER_PAGE));
        $currentAuthor = $request->getSession()->get(self::SESSION_AUTHOR_KEY);

        return $this->render('frontOffice/forum/index.html.twig', [
            'posts' => $posts,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'search' => $q,
            'current_author' => $currentAuthor,
        ]);
    }

    #[Route('/post/new', name: 'forum_post_new', methods: ['GET', 'POST'])]
    public function newPost(Request $request): Response
    {
        $post = new Post();
        $post->setIsPublished(true);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            $request->getSession()->set(self::SESSION_AUTHOR_KEY, $post->getAuthor());
            $this->addFlash('success', 'Votre post a été créé.');
            return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
        }

        return $this->render('frontOffice/forum/post_new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/post', name: 'forum_post_list', methods: ['GET'])]
    public function postRedirect(): Response
    {
        return $this->redirectToRoute('forum_index');
    }

    #[Route('/post/{id}', name: 'forum_post_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(Request $request, Post $post): Response
    {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException('Ce post n\'existe pas ou n\'est pas publié.');
        }

        $comment = new Comment();
        $comment->setPost($post);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->request->has('parent_id')) {
                $parentId = (int) $request->request->get('parent_id');
                if ($parentId > 0) {
                    $parent = $this->commentRepository->find($parentId);
                    if ($parent && $parent->getPost() === $post) {
                        $comment->setParent($parent);
                    }
                }
            }
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            $request->getSession()->set(self::SESSION_AUTHOR_KEY, $comment->getAuthor());
            $this->addFlash('success', 'Votre commentaire a été publié.');
            return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
        }

        $rootComments = $this->commentRepository->findRootCommentsByPost($post);
        $currentAuthor = $request->getSession()->get(self::SESSION_AUTHOR_KEY);

        return $this->render('frontOffice/forum/show.html.twig', [
            'post' => $post,
            'form' => $form,
            'root_comments' => $rootComments,
            'current_author' => $currentAuthor,
        ]);
    }

    #[Route('/post/{id}/edit', name: 'forum_post_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editPost(Request $request, Post $post): Response
    {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException('Ce post n\'existe pas ou n\'est pas publié.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Post modifié.');
            return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
        }

        return $this->render('frontOffice/forum/post_edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/post/{id}/delete', name: 'forum_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deletePost(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete_post' . $post->getId(), (string) $request->request->get('_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
            $this->addFlash('success', 'Post supprimé.');
            return $this->redirectToRoute('forum_index');
        }

        return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
    }

    #[Route('/comment/{id}/edit', name: 'forum_comment_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editComment(Request $request, Comment $comment): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Commentaire modifié.');
            return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render('frontOffice/forum/comment_edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'forum_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteComment(Request $request, Comment $comment): Response
    {
        if ($this->isCsrfTokenValid('delete_comment' . $comment->getId(), (string) $request->request->get('_token'))) {
            $this->entityManager->remove($comment);
            $this->entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        }
        return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()->getId()]);
    }

    #[Route('/post/{id}/avis', name: 'forum_post_avis', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postAvis(Post $post): Response
    {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }
        $post->incrementAvis();
        $this->entityManager->flush();
        return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
    }

    #[Route('/post/{id}/dislike', name: 'forum_post_dislike', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postDislike(Post $post): Response
    {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException();
        }
        $post->incrementDislike();
        $this->entityManager->flush();
        $this->dislikeAlertMailer->sendPostAlertIfNeeded($post);
        return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
    }

    #[Route('/comment/{id}/avis', name: 'forum_comment_avis', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function commentAvis(Comment $comment): Response
    {
        $comment->incrementAvis();
        $this->entityManager->flush();
        return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()->getId()]);
    }

    #[Route('/comment/{id}/dislike', name: 'forum_comment_dislike', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function commentDislike(Comment $comment): Response
    {
        $comment->incrementDislike();
        $this->entityManager->flush();
        $this->dislikeAlertMailer->sendCommentAlertIfNeeded($comment);
        return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()->getId()]);
    }

    #[Route('/chat', name: 'forum_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $message = trim((string) $request->request->get('message', ''));
        if ($message === '') {
            return new JsonResponse(['reply' => 'Veuillez entrer un message.'], 400);
        }

        $reply = $this->forumAiAssistant->ask($message);

        return new JsonResponse(['reply' => $reply]);
    }
}
