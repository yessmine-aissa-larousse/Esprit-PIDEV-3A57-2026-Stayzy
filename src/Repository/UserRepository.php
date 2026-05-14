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

    /**
     * Recherche par nom, prénom, email ou téléphone, avec filtre optionnel par rôle
     */
    /** @return User[] */
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

    /**
     * Trouver les utilisateurs par rôle avec un statut d'approbation donné
     * $limit = null pour tout récupérer, sinon nombre max de résultats
     */
    /** @return User[] */
    public function findByRoleAndStatus(string $role, string $status, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->andWhere('u.approvalStatus = :status')
            ->setParameter('role', '%' . $role . '%')
            ->setParameter('status', $status)
            ->orderBy('u.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver les utilisateurs par rôle
     */
    /** @return User[] */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les utilisateurs actifs
     */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compter par rôle
     */
    public function countByRole(string $role): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->getQuery()
            ->getSingleScalarResult();
    }
}