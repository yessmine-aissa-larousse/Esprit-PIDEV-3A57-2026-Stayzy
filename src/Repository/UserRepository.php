<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function search(?string $search = null, ?string $role = null): array
    {
        $qb = $this->createQueryBuilder('u');
        if ($search) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search OR u.tel LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($role) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%' . $role . '%');
        }
        return $qb->orderBy('u.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function countActive(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
            ->getQuery()
            ->getResult();
    }

    // Nouvelle méthode : propriétaires avec un statut donné
    public function findByRoleAndStatus(string $role, string $status): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->andWhere('u.approvalStatus = :status')
            ->setParameter('role', '%' . $role . '%')
            ->setParameter('status', $status)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function countByRole(string $role): int
{
    return $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.roles LIKE :role')
        ->setParameter('role', '%"'.$role.'"%')
        ->getQuery()
        ->getSingleScalarResult();
}
}