<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ReclamationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use App\Service\ReclamationIntelligenceService;
use App\Service\MLScoringService;                  // ← AJOUT
use Doctrine\ORM\EntityManagerInterface;

final class SupervisionReclamation extends AbstractController
{
    #[Route('/admin/supervision-reclamation', name: 'admin_supervision_reclamation')]
    public function dashboard(
        ReclamationRepository $repo,
        ChartBuilderInterface $chartBuilder,
        ReclamationIntelligenceService $intelligenceService,
        MLScoringService $mlScoring,                       // ← AJOUT
        EntityManagerInterface $em
    ): Response {

        // 🔥 AUTO ESCALATION CHECK
        $intelligenceService->checkEscalation($em);

        $reclamations = $repo->findAll();

        // ================= GLOBAL COUNTS =================
        $total       = count($reclamations);
        $enAttente   = $repo->count(['statut' => 'EN_ATTENTE']);
        $traitee     = $repo->count(['statut' => 'TRAITEE']);
        $urgentCount = $repo->count(['statut' => 'URGENT']);

        // ================= RISK ANALYSIS VIA IA ==================
        $critique = 0;
        $moyenne  = 0;
        $normale  = 0;

        foreach ($reclamations as $rec) {
            // 🤖 Score IA depuis Flask (fallback rule-based si Flask down)
            $iaResult = $mlScoring->analyzeReclamation($rec);
            $score    = $iaResult['risk']['score'] ?? ($rec->getRiskScore() ?? 0);

            if ($score > 70) {
                $critique++;
            } elseif ($score > 30) {
                $moyenne++;
            } else {
                $normale++;
            }
        }
        // ==========================================================

        // ================= ABUSE DETECTION =================
        $lastWeek = new \DateTime('-7 days');

        $abuseUsers = $repo->createQueryBuilder('r')
            ->select('IDENTITY(r.user) as userId, COUNT(r.id) as total')
            ->where('r.dateReclamation >= :date')
            ->setParameter('date', $lastWeek)
            ->groupBy('r.user')
            ->having('COUNT(r.id) > 5')
            ->getQuery()
            ->getResult();

        $abuseCount  = count($abuseUsers);
        $normalUsers = max(0, $total - $abuseCount);

        // ================= COLORS =================
        $primary = '#5a6acf';
        $warning = '#e6a23c';
        $success = '#2eae7b';
        $danger  = '#d64545';
        $dark    = '#495057';

        // ================= CHART 1 - STATUT =================
        $statutChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $statutChart->setData([
            'labels'   => ['En attente', 'Traitée', 'Urgente'],
            'datasets' => [[
                'data'            => [$enAttente, $traitee, $urgentCount],
                'backgroundColor' => [$warning, $success, $danger],
                'borderWidth'     => 0,
            ]]
        ]);
        $statutChart->setOptions([
            'plugins' => ['legend' => ['labels' => ['color' => $dark, 'font' => ['size' => 13]]]]
        ]);

        // ================= CHART 2 - RISK IA =================
        $riskChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $riskChart->setData([
            'labels'   => ['🚨 Critiques (IA)', '⚠️ Moyennes (IA)', '✅ Normales (IA)'],
            'datasets' => [[
                'label'           => '🤖 Analyse IA des Risques',
                'data'            => [$critique, $moyenne, $normale],
                'backgroundColor' => [$danger, $warning, $success],
                'borderRadius'    => 8,
            ]]
        ]);
        $riskChart->setOptions([
            'plugins' => ['legend' => ['labels' => ['color' => $dark]]],
            'scales'  => [
                'y' => ['ticks' => ['color' => $dark]],
                'x' => ['ticks' => ['color' => $dark]],
            ]
        ]);

        // ================= CHART 3 - SECURITY =================
        $securityChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $securityChart->setData([
            'labels'   => ['Users Normaux', 'Users Suspects'],
            'datasets' => [[
                'label'           => 'Security Monitoring',
                'data'            => [$normalUsers, $abuseCount],
                'backgroundColor' => [$primary, $dark],
                'borderRadius'    => 8,
            ]]
        ]);
        $securityChart->setOptions([
            'plugins' => ['legend' => ['labels' => ['color' => $dark]]],
            'scales'  => [
                'y' => ['ticks' => ['color' => $dark]],
                'x' => ['ticks' => ['color' => $dark]],
            ]
        ]);

        return $this->render('backOffice/reclamation/admin/supervision_reclamation.html.twig', [
    'total'         => $total,
    'enAttente'     => $enAttente,
    'traitee'       => $traitee,
    'urgentCount'   => $urgentCount,
    'abuseCount'    => $abuseCount,
    'normalUsers'   => $normalUsers,
    'critiques'     => $critique,    // ← IA
    'moyennes'      => $moyenne,     // ← IA
    'normales'      => $normale,     // ← IA
    'statutChart'   => $statutChart,
    'riskChart'     => $riskChart,
    'securityChart' => $securityChart,
]);
    }
}