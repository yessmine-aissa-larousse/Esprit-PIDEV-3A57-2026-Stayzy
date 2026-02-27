<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        $mostLikedPosts = $this->postRepository->findMostLiked(5);
        $mostDislikedPosts = $this->postRepository->findMostDisliked(5);
        $mostLikedComments = $this->commentRepository->findMostLiked(5);
        $mostDislikedComments = $this->commentRepository->findMostDisliked(5);

        $totalPostLikes = (int) $this->postRepository->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.avisCount), 0)')
            ->getQuery()->getSingleScalarResult();
        $totalPostDislikes = (int) $this->postRepository->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.dislikeCount), 0)')
            ->getQuery()->getSingleScalarResult();
        $totalCommentLikes = (int) $this->commentRepository->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.avisCount), 0)')
            ->getQuery()->getSingleScalarResult();
        $totalCommentDislikes = (int) $this->commentRepository->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.dislikeCount), 0)')
            ->getQuery()->getSingleScalarResult();

        return $this->render('backOffice/dashboard.html.twig', [
            'most_liked_posts' => $mostLikedPosts,
            'most_disliked_posts' => $mostDislikedPosts,
            'most_liked_comments' => $mostLikedComments,
            'most_disliked_comments' => $mostDislikedComments,
            'total_post_likes' => $totalPostLikes,
            'total_post_dislikes' => $totalPostDislikes,
            'total_comment_likes' => $totalCommentLikes,
            'total_comment_dislikes' => $totalCommentDislikes,
        ]);
    }

    #[Route('/admin/forum', name: 'admin_forum', methods: ['GET'])]
    public function forum(): Response
    {
        return $this->redirectToRoute('admin_post_index');
    }
}
