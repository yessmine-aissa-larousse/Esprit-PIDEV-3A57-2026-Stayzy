<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Post & comment stats
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

        // User counts
        $totalUsers   = $this->userRepository->count([]);
        $activeUsers  = $this->userRepository->count(['isActive' => true]);
        $pendingCount = $this->userRepository->count(['approvalStatus' => 'pending']);

        $clientCount = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_CLIENT%')
            ->getQuery()->getSingleScalarResult();

        $proprietaireCount = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PROPRIETAIRE%')
            ->getQuery()->getSingleScalarResult();

        $adminCount = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()->getSingleScalarResult();

        // User lists
        $recentUsers  = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $pendingUsers = $this->userRepository->findBy(['approvalStatus' => 'pending'], [], 5);

        $pendingProprietaires = $this->userRepository->createQueryBuilder('u')
            ->where('u.approvalStatus = :status')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('status', 'pending')
            ->setParameter('role', '%ROLE_PROPRIETAIRE%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Monthly registration stats (last 6 months) using BETWEEN
        $monthlyLabels = [];
        $monthlyData   = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = new \DateTime("-$i months");
            $start = new \DateTime($date->format('Y-m-01 00:00:00'));
            $end   = new \DateTime($date->format('Y-m-t 23:59:59'));

            $monthlyLabels[] = $date->format('M Y');
            $monthlyData[]   = (int) $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()->getSingleScalarResult();
        }

        return $this->render('backOffice/dashboard.html.twig', [
            // Post & comment
            'most_liked_posts'        => $mostLikedPosts,
            'most_disliked_posts'     => $mostDislikedPosts,
            'most_liked_comments'     => $mostLikedComments,
            'most_disliked_comments'  => $mostDislikedComments,
            'total_post_likes'        => $totalPostLikes,
            'total_post_dislikes'     => $totalPostDislikes,
            'total_comment_likes'     => $totalCommentLikes,
            'total_comment_dislikes'  => $totalCommentDislikes,
            // User stats
            'total_users'             => $totalUsers,
            'active_users'            => $activeUsers,
            'client_count'            => $clientCount,
            'proprietaire_count'      => $proprietaireCount,
            'pending_count'           => $pendingCount,
            'admin_count'             => $adminCount,
            // User lists
            'recent_users'            => $recentUsers,
            'pending_users'           => $pendingUsers,
            'pending_proprietaires'   => $pendingProprietaires,
            // Chart data
            'monthly_stats'           => ['labels' => $monthlyLabels, 'data' => $monthlyData],
        ]);
    }

    #[Route('/admin/forum', name: 'admin_forum', methods: ['GET'])]
    public function forum(): Response
    {
        return $this->redirectToRoute('admin_post_index');
    }
}