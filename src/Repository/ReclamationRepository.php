<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

//    /**
//     * @return Reclamation[] Returns an array of Reclamation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reclamation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

public function findBySujet(string $value)
{
    return $this->createQueryBuilder('r')
        ->andWhere('r.sujet LIKE :val')
        ->setParameter('val', '%'.$value.'%')
        ->orderBy('r.id', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findByStatut(string $statut)
{
    return $this->createQueryBuilder('r')
        ->andWhere('r.statut = :statut')
        ->setParameter('statut', $statut)
        ->orderBy('r.id', 'DESC')
        ->getQuery()
        ->getResult();
}



}
