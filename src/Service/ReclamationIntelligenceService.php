<?php

namespace App\Service;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;


class ReclamationIntelligenceService
{
    private ReclamationRepository $repo;

    public function __construct(ReclamationRepository $repo)
    {
        $this->repo = $repo;
    }

    /* ===== CALCUL SCORE ===== */
    public function calculateRiskScore(Reclamation $reclamation): int
    {
        $score = 0;
        $description = strtolower($reclamation->getDescription());

        $keywords = ['arnaque','urgence','menace','fraude','scandale'];

        foreach ($keywords as $word) {
            if (str_contains($description, $word)) {
                $score += 20;
            }
        }

        if (strlen($description) > 200) {
            $score += 20;
        }

        $count = $this->repo->count([
            'user' => $reclamation->getUser()
        ]);

        if ($count > 3) {
            $score += 30;
        }

        return min($score, 100);
    }

    /* ===== DETECTION ABUS ===== */
    public function detectAbuse(Reclamation $reclamation): bool
    {
        $lastWeek = new \DateTime('-7 days');

        $count = $this->repo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.dateReclamation >= :date')
            ->setParameter('user', $reclamation->getUser())
            ->setParameter('date', $lastWeek)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 5;
    }

    /* ===== ORIENTATION ===== */
    public function detectTargetRole(Reclamation $reclamation): string
    {
        $text = strtolower($reclamation->getDescription());

        $logementKeywords = ['appartement','villa','studio','maison','chambre','proprietaire','logement'];
        $platformKeywords = ['bug','erreur','paiement','transaction','carte','site','application','connexion'];

        foreach ($logementKeywords as $word) {
            if (str_contains($text, $word)) {
                return 'PROPRIETAIRE';
            }
        }

        foreach ($platformKeywords as $word) {
            if (str_contains($text, $word)) {
                return 'ADMIN';
            }
        }

        return 'ADMIN';
    }



    /* ===== SMART REPLY ===== */
public function generateSmartReply(Reclamation $reclamation): string
{
    $score = $this->calculateRiskScore($reclamation);
    $role = $this->detectTargetRole($reclamation);
    $description = strtolower($reclamation->getDescription());

    //  Urgent case
    if ($score >= 80) {
        return "Votre réclamation est classée prioritaire. Elle est en cours de traitement urgent.";
    }

    //  Paiement
    if (str_contains($description, 'paiement') || str_contains($description, 'transaction')) {
        return "Votre problème de paiement est en cours de vérification par notre équipe financière.";
    }

    //  Bug technique
    if (str_contains($description, 'bug') || str_contains($description, 'erreur')) {
        return "Nous avons détecté un problème technique. Notre équipe travaille actuellement dessus.";
    }

    //  Propriétaire
    if ($role === 'PROPRIETAIRE') {
        return "Votre réclamation a été transmise au propriétaire concerné.";
    }

    // Default
    return "Nous avons bien reçu votre réclamation et elle est en cours de traitement.";
}




/* ===== AUTO ESCALATION ===== */
public function shouldEscalate(Reclamation $reclamation): bool
{
    if ($reclamation->getStatut() !== 'EN_ATTENTE') {
        return false;
    }

    $createdAt = $reclamation->getDateReclamation();
    $now = new \DateTime();

    $interval = $createdAt->diff($now);

    return ($interval->days >= 2);
}


public function checkEscalation(EntityManagerInterface $em): void
{
    $limitDate = new \DateTime('-48 hours');

    $reclamations = $em->getRepository(Reclamation::class)
        ->createQueryBuilder('r')
        ->where('r.dateReclamation < :limit')
        ->andWhere('r.statut != :traitee')
        ->setParameter('limit', $limitDate)
        ->setParameter('traitee', 'TRAITEE')
        ->getQuery()
        ->getResult();

    foreach ($reclamations as $rec) {

        if ($rec->getReponses()->isEmpty()) {
            $rec->setStatut('URGENT'); 
        }

    }

    $em->flush();
}


}