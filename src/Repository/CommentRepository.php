<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return Comment[]
     */
    public function findByPostOrderedByDate(Post $post): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :post')
            ->setParameter('post', $post)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Root comments only (no parent), with replies loaded, ordered by date DESC.
     *
     * @return Comment[]
     */
    public function findRootCommentsByPost(Post $post): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :post')
            ->andWhere('c.parent IS NULL')
            ->setParameter('post', $post)
            ->leftJoin('c.replies', 'r')
            ->addSelect('r')
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Comment[]
     */
    public function search(string $q, ?int $postId = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->addSelect('p');
        if ($q !== '') {
            $qb->andWhere('c.content LIKE :q OR c.author LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        if ($postId > 0) {
            $qb->andWhere('p.id = :postId')->setParameter('postId', $postId);
        }
        return $qb->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countSearch(string $q, ?int $postId = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->leftJoin('c.post', 'p');
        if ($q !== '') {
            $qb->andWhere('c.content LIKE :q OR c.author LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        if ($postId > 0) {
            $qb->andWhere('p.id = :postId')->setParameter('postId', $postId);
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Comment[]
     */
    public function findMostLiked(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->addSelect('p')
            ->orderBy('c.avisCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Comment[]
     */
    public function findMostDisliked(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->addSelect('p')
            ->orderBy('c.dislikeCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
