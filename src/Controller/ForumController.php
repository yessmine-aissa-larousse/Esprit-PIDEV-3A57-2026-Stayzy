<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forum')]
final class ForumController extends AbstractController
{
    private const POSTS_PER_PAGE = 9;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'forum_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::POSTS_PER_PAGE;
        $posts = $this->postRepository->findPublishedOrderedByDate(self::POSTS_PER_PAGE, $offset);
        $total = $this->postRepository->countPublished();
        $totalPages = (int) ceil($total / self::POSTS_PER_PAGE);

        return $this->render('frontOffice/forum/index.html.twig', [
            'posts' => $posts,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
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
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a été publié.');
            return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
        }

        return $this->render('frontOffice/forum/show.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }
}
