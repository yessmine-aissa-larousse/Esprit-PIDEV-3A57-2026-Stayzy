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
}
