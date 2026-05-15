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
        $q = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;
        if ($q !== '') {
            $posts = $this->postRepository->createQueryBuilder('p')
                ->where('p.title LIKE :q OR p.content LIKE :q OR p.author LIKE :q OR p.excerpt LIKE :q')
                ->setParameter('q', '%' . $q . '%')
                ->orderBy('p.createdAt', 'DESC')
                ->setMaxResults(self::PER_PAGE)
                ->setFirstResult($offset)
                ->getQuery()
                ->getResult();
            $total = (int) $this->postRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.title LIKE :q OR p.content LIKE :q OR p.author LIKE :q OR p.excerpt LIKE :q')
                ->setParameter('q', '%' . $q . '%')
                ->getQuery()
                ->getSingleScalarResult();
        } else {
            $posts = $this->postRepository->findBy([], ['createdAt' => 'DESC'], self::PER_PAGE, $offset);
            $total = $this->postRepository->count([]);
        }
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        return $this->render('backOffice/forum/post/index.html.twig', [
            'posts' => $posts,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'search' => $q,
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
