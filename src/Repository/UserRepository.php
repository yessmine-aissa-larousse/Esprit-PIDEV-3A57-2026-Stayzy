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
     * 🔍 Recherche par nom, prénom, email ou téléphone, avec filtre optionnel par rôle
     */
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
     * 🧩 Trouver les utilisateurs par rôle avec un statut d'approbation donné
     * $limit = null pour tout récupérer, sinon nombre max de résultats
     */
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
     * 🔸 Trouver les utilisateurs par rôle
     */
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
     * 🟢 Compter les utilisateurs actifs
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
     * 🔹 Compter les utilisateurs par rôle
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

    /**
     * ⚡ Trouver les utilisateurs récents (par date de création)
     */
    public function findRecentUsers(int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔔 Trouver les propriétaires en attente d’approbation
     */
    public function findPendingOwners(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->andWhere('u.approvalStatus = :status')
            ->setParameter('role', '%ROLE_PROPRIETAIRE%')
            ->setParameter('status', User::STATUS_PENDING)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 🚫 Trouver les propriétaires rejetés
     */
    public function findRejectedOwners(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->andWhere('u.approvalStatus = :status')
            ->setParameter('role', '%ROLE_PROPRIETAIRE%')
            ->setParameter('status', User::STATUS_REJECTED)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ Trouver les propriétaires approuvés
     */
    public function findApprovedOwners(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->andWhere('u.approvalStatus = :status')
            ->setParameter('role', '%ROLE_PROPRIETAIRE%')
            ->setParameter('status', User::STATUS_APPROVED)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 🧮 Statistiques : nombre d’utilisateurs par statut d’approbation
     */
    public function countByApprovalStatus(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.approvalStatus, COUNT(u.id) as total')
            ->groupBy('u.approvalStatus')
            ->getQuery()
            ->getResult();
    }
}