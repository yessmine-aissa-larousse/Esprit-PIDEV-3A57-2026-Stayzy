<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
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
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_comment_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;
        $comments = $this->commentRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            self::PER_PAGE,
            $offset
        );
        $total = $this->commentRepository->count([]);
        $totalPages = (int) ceil($total / self::PER_PAGE);

        return $this->render('backOffice/forum/comment/index.html.twig', [
            'comments' => $comments,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
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
            return $this->redirectToRoute('admin_comment_index');
        }

        return $this->render('backOffice/forum/comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
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
        return $this->redirectToRoute('admin_comment_index');
    }
}
