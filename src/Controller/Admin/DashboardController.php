<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        PostRepository $postRepository,
        CommentRepository $commentRepository
    ): Response
    {
        $totalUsers = $userRepository->count([]);
        $pendingCount = $userRepository->count(['approvalStatus' => User::STATUS_PENDING]);
        $activeUsers = $userRepository->count(['isActive' => true]);
        $inactiveUsers = $userRepository->count(['isActive' => false]);

        // Compter par rôle en mémoire (simple et fiable)
        $clientCount = 0;
        $proprietaireCount = 0;
        $adminCount = 0;

        $allUsers = $userRepository->findAll();
        foreach ($allUsers as $user) {
            if (in_array('ROLE_CLIENT', $user->getRoles())) {
                $clientCount++;
            } elseif (in_array('ROLE_PROPRIETAIRE', $user->getRoles())) {
                $proprietaireCount++;
            } elseif (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount++;
            }
        }

        $approvedCount = $userRepository->count(['approvalStatus' => User::STATUS_APPROVED]);
        $rejectedCount = $userRepository->count(['approvalStatus' => User::STATUS_REJECTED]);

        $monthlyStats = $this->getMonthlyRegistrations($userRepository);

        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        $pendingProprietaires = $userRepository->findByRoleAndStatus('ROLE_PROPRIETAIRE', User::STATUS_PENDING, 5);

        // ── Stats Forum ──────────────────────────────────────────────────────
        $totalPostLikes = (int) $postRepository->createQueryBuilder('p')
            ->select('SUM(p.avisCount)')
            ->getQuery()->getSingleScalarResult();

        $totalPostDislikes = (int) $postRepository->createQueryBuilder('p')
            ->select('SUM(p.dislikeCount)')
            ->getQuery()->getSingleScalarResult();

        $totalCommentLikes = (int) $commentRepository->createQueryBuilder('c')
            ->select('SUM(c.avisCount)')
            ->getQuery()->getSingleScalarResult();

        $totalCommentDislikes = (int) $commentRepository->createQueryBuilder('c')
            ->select('SUM(c.dislikeCount)')
            ->getQuery()->getSingleScalarResult();

        $mostLikedPosts = $postRepository->createQueryBuilder('p')
            ->where('p.isPublished = true')
            ->orderBy('p.avisCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $mostDislikedPosts = $postRepository->createQueryBuilder('p')
            ->where('p.isPublished = true')
            ->orderBy('p.dislikeCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $mostLikedComments = $commentRepository->createQueryBuilder('c')
            ->orderBy('c.avisCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $mostDislikedComments = $commentRepository->createQueryBuilder('c')
            ->orderBy('c.dislikeCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        return $this->render('backOffice/dashboard.html.twig', [
            'total_users'             => $totalUsers,
            'pending_count'           => $pendingCount,
            'active_users'            => $activeUsers,
            'inactive_users'          => $inactiveUsers,
            'client_count'            => $clientCount,
            'proprietaire_count'      => $proprietaireCount,
            'admin_count'             => $adminCount,
            'approved_count'          => $approvedCount,
            'rejected_count'          => $rejectedCount,
            'monthly_stats'           => $monthlyStats,
            'recent_users'            => $recentUsers,
            'pending_proprietaires'   => $pendingProprietaires,
            // Forum
            'total_post_likes'        => $totalPostLikes,
            'total_post_dislikes'     => $totalPostDislikes,
            'total_comment_likes'     => $totalCommentLikes,
            'total_comment_dislikes'  => $totalCommentDislikes,
            'most_liked_posts'        => $mostLikedPosts,
            'most_disliked_posts'     => $mostDislikedPosts,
            'most_liked_comments'     => $mostLikedComments,
            'most_disliked_comments'  => $mostDislikedComments,
        ]);
    }

    private function getMonthlyRegistrations(UserRepository $userRepository): array
    {
        $months = [];
        $stats = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-$i month");

            $startOfMonth = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
            $endOfMonth   = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

            $count = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt >= :start')
                ->andWhere('u.createdAt <= :end')
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery()
                ->getSingleScalarResult();

            $months[] = $this->getMonthNameInFrench((int) $date->format('n'));
            $stats[]  = (int) $count;
        }

        return [
            'labels' => $months,
            'data'   => $stats,
        ];
    }

    private function getMonthNameInFrench(int $monthNumber): string
    {
        $months = [
            1 => 'Janvier',  2 => 'Février',   3 => 'Mars',
            4 => 'Avril',    5 => 'Mai',        6 => 'Juin',
            7 => 'Juillet',  8 => 'Août',       9 => 'Septembre',
            10 => 'Octobre', 11 => 'Novembre',  12 => 'Décembre',
        ];
        return $months[$monthNumber];
    }
}