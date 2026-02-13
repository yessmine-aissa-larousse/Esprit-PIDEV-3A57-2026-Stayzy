<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/forum/post')]
final class PostCrudController extends AbstractController
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_post_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;
        $posts = $this->postRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            self::PER_PAGE,
            $offset
        );
        $total = $this->postRepository->count([]);
        $totalPages = (int) ceil($total / self::PER_PAGE);

        return $this->render('backOffice/forum/post/index.html.twig', [
            'posts' => $posts,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'admin_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le post a été créé avec succès.');
            return $this->redirectToRoute('admin_post_index');
        }

        return $this->render('backOffice/forum/post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_post_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('backOffice/forum/post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_post_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Le post a été modifié avec succès.');
            return $this->redirectToRoute('admin_post_index');
        }

        return $this->render('backOffice/forum/post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), (string) $request->request->get('_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le post a été supprimé.');
        }
        return $this->redirectToRoute('admin_post_index');
    }
}
