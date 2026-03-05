<?php

namespace App\Repository;

use App\Entity\Visite;
use App\Entity\User;
use App\Entity\Logement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VisiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visite::class);
    }

    // 🔹 Visites d’un client
    public function findByClient(User $client)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.client = :client')
            ->setParameter('client', $client)
            ->orderBy('v.dateVisite', 'DESC')
            ->addOrderBy('v.heureVisite', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // 🔹 Visites d’un propriétaire
    public function findByProprietaire(User $proprietaire)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.proprietaire = :prop')
            ->setParameter('prop', $proprietaire)
            ->orderBy('v.dateVisite', 'DESC')
            ->addOrderBy('v.heureVisite', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // 🔹 Visites d’un logement
    public function findByLogement(Logement $logement)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.logement = :logement')
            ->setParameter('logement', $logement)
            ->orderBy('v.dateVisite', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // 🔹 Toutes les visites (Admin)
    public function findAllOrdered()
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // 🔹 Visites en attente (important pour propriétaire)
    public function findPendingByProprietaire(User $proprietaire)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.proprietaire = :prop')
            ->andWhere('v.statut = :statut')
            ->setParameter('prop', $proprietaire)
            ->setParameter('statut', 'en_attente')
            ->orderBy('v.dateVisite', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
