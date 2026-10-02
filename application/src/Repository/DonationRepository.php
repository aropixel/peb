<?php

namespace App\Repository;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Donation>
 */
class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    /**
     * @return iterable<Donation>
     */
    public function iterateForExport(?DonationStatus $status): iterable
    {
        $qb = $this->createQueryBuilder('d')->orderBy('d.createdAt', 'DESC');

        if (null !== $status) {
            $qb->andWhere('d.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->toIterable();
    }
}
