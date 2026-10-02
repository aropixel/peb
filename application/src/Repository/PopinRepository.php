<?php

namespace App\Repository;

use App\Entity\Popin;
use Aropixel\AdminBundle\Entity\Publishable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Popin>
 */
class PopinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Popin::class);
    }

    /**
     * Popins en ligne et dans leur plage de publication, les plus récentes d'abord.
     *
     * @return list<Popin>
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishAt IS NULL OR p.publishAt <= :now')
            ->andWhere('p.publishUntil IS NULL OR p.publishUntil >= :now')
            ->setParameter('status', Publishable::STATUS_ONLINE)
            ->setParameter('now', new \DateTime())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
