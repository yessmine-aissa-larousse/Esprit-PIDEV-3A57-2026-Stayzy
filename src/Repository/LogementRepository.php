<?php

namespace App\Repository;

use App\Entity\Logement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Logement>
 */
class LogementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logement::class);
    }

    // Exemple de méthode personnalisée
    // public function findByPrixGreaterThan(float $prix): array
    // {
    //     return $this->createQueryBuilder('l')
    //         ->andWhere('l.prix > :prix')
    //         ->setParameter('prix', $prix)
    //         ->orderBy('l.prix', 'ASC')
    //         ->getQuery()
    //         ->getResult();
    // }
}


