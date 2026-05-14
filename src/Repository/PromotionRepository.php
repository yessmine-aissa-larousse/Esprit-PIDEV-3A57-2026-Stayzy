<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    //    /**
    //     * @return Promotion[] Returns an array of Promotion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Promotion
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne la promotion ACTIVE EN COURS pour un logement donné
     * (priorité à la plus récente si plusieurs)
     */


    public function findPromoActiveByLogement(int $logementId): ?Promotion
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('p')
            ->where('p.logement = :logementId')
            ->andWhere('p.active = true')
            ->andWhere('p.dateDebut <= :now')
            ->andWhere('p.dateFin >= :now')
            ->setParameter('logementId', $logementId)
            ->setParameter('now', $now)
            ->orderBy('p.pourcentage', 'DESC') 
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne toutes les promotions actives en cours (pour la page liste)
     */
    /** @return Promotion[] */
    public function findAllActives(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('p')
            ->join('p.logement', 'l')
            ->where('p.active = true')
            ->andWhere('p.dateDebut <= :now')
            ->andWhere('p.dateFin >= :now')
            ->setParameter('now', $now)
            ->orderBy('p.pourcentage', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
