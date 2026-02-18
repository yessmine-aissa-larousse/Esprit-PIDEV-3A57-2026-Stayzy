<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        // Stats générales
        $totalUsers = $userRepository->count([]);
        $pendingCount = $userRepository->count(['approvalStatus' => User::STATUS_PENDING]);
        $activeUsers = $userRepository->count(['isActive' => true]);
        $inactiveUsers = $userRepository->count(['isActive' => false]);
        
        // Compter par rôle
        $clientCount = 0;
        $proprietaireCount = 0;
        $adminCount = 0;
        
        $allUsers = $userRepository->findAll();
        foreach ($allUsers as $user) {
            if (in_array('ROLE_CLIENT', $user->getRoles())) {
                $clientCount++;
            }
            if (in_array('ROLE_PROPRIETAIRE', $user->getRoles())) {
                $proprietaireCount++;
            }
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount++;
            }
        }
        
        // Statistiques par statut d'approbation
        $approvedCount = $userRepository->count(['approvalStatus' => User::STATUS_APPROVED]);
        $rejectedCount = $userRepository->count(['approvalStatus' => User::STATUS_REJECTED]);
        
        // Inscriptions par mois (derniers 6 mois)
        $monthlyStats = $this->getMonthlyRegistrations($userRepository);
        
        // Utilisateurs récents
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Propriétaires en attente récents
        $pendingProprietaires = $userRepository->findByRoleAndStatus('ROLE_PROPRIETAIRE', User::STATUS_PENDING, 5);
        
        return $this->render('backOffice/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'pending_count' => $pendingCount,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'client_count' => $clientCount,
            'proprietaire_count' => $proprietaireCount,
            'admin_count' => $adminCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'monthly_stats' => $monthlyStats,
            'recent_users' => $recentUsers,
            'pending_proprietaires' => $pendingProprietaires,
        ]);
    }
    
    private function getMonthlyRegistrations(UserRepository $userRepository): array
    {
        $stats = [];
        $months = [];
        
        // Derniers 6 mois
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-$i month");
            
            $startOfMonth = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
            $endOfMonth = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);
            
            $count = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt >= :start')
                ->andWhere('u.createdAt <= :end')
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery()
                ->getSingleScalarResult();
            
            $monthName = $this->getMonthNameInFrench($date->format('n'));
            $months[] = $monthName;
            $stats[] = (int)$count;
        }
        
        return [
            'labels' => $months,
            'data' => $stats
        ];
    }
    
    private function getMonthNameInFrench(int $monthNumber): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        return $months[$monthNumber];
    }
}