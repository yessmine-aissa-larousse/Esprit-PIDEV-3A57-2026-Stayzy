<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
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
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;
        $postFilter = $request->query->getInt('post', 0);
        $post = null;

        if ($postFilter > 0) {
            $post = $this->postRepository->find($postFilter);
            if ($post) {
                $comments = $this->commentRepository->findByPostPaginated($post, self::PER_PAGE, $offset);
                $total = $this->commentRepository->countByPost($post);
            } else {
                $comments = [];
                $total = 0;
            }
        } else {
            $comments = $this->commentRepository->findBy(
                [],
                ['createdAt' => 'DESC'],
                self::PER_PAGE,
                $offset
            );
            $total = $this->commentRepository->count([]);
        }

        $totalPages = (int) ceil($total / self::PER_PAGE);

        return $this->render('backOffice/forum/comment/index.html.twig', [
            'comments' => $comments,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'filter_post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_comment_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Le commentaire a été modifié.');
            $redirectParams = [];
            if ($request->query->getInt('post')) {
                $redirectParams['post'] = $comment->getPost()->getId();
            }
            return $this->redirectToRoute('admin_comment_index', $redirectParams);
        }

        return $this->render('backOffice/forum/comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
            'filter_post_id' => $request->query->getInt('post'),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Comment $comment): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), (string) $request->request->get('_token'))) {
            $this->entityManager->remove($comment);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le commentaire a été supprimé.');
        }
        $redirectParams = [];
        if ($request->request->getInt('filter_post')) {
            $redirectParams['post'] = $request->request->getInt('filter_post');
        }
        return $this->redirectToRoute('admin_comment_index', $redirectParams);
    }
}
