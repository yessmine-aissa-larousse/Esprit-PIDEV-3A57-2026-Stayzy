<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/forum/comment')]
final class CommentCrudController extends AbstractController
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_comment_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $postId = $request->query->getInt('post_id', 0);
        $criteria = [];
        if ($postId > 0) {
            $post = $this->postRepository->find($postId);
            if ($post) {
                $criteria['post'] = $post;
            }
        }
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;
        $comments = $this->commentRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            self::PER_PAGE,
            $offset
        );
        $total = $this->commentRepository->count($criteria);
        $totalPages = (int) ceil($total / self::PER_PAGE);

        return $this->render('backOffice/forum/comment/index.html.twig', [
            'comments' => $comments,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'filter_post_id' => $postId > 0 ? $postId : null,
        ]);
    }

    #[Route('/{id}', name: 'admin_comment_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->redirect($this->generateUrl('forum_post_show', ['id' => $comment->getPost()->getId()]) . '#comment-' . $comment->getId());
    }

    #[Route('/{id}/delete', name: 'admin_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Comment $comment): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), (string) $request->request->get('_token'))) {
            $this->entityManager->remove($comment);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le commentaire a été supprimé.');
        }
        return $this->redirectToRoute('admin_comment_index');
    }
}
