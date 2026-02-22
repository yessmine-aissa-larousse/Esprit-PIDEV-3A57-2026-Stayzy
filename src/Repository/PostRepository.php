<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[]
     */
    public function findPublishedOrderedByDate(int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPublished(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOnePublishedBySlugOrId(int|string $slugOrId): ?Post
    {
        if (is_numeric($slugOrId)) {
            return $this->findOneBy(['id' => (int) $slugOrId, 'isPublished' => true]);
        }
        return null;
    }

    /**
     * @return Post[]
     */
    public function searchPublished(string $q, int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true);
        if ($q !== '') {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q OR p.author LIKE :q OR p.excerpt LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        return $qb->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countSearchPublished(string $q): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true);
        if ($q !== '') {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q OR p.author LIKE :q OR p.excerpt LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Post[]
     */
    public function findMostLiked(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.avisCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Post[]
     */
    public function findMostDisliked(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.dislikeCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
