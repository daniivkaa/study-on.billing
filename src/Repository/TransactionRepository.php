<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * @return Transaction[]
     */
    public function findByFilters($filters, EntityManagerInterface $em)
    {
        $query = $this->createQueryBuilder('t');
        if ($filters) {
            if (isset($filters['type'])) {
                $query
                    ->andWhere('t.operationType = :type')
                    ->setParameter('type', $filters['type']);
            }
            if (isset($filters['course_code'])) {
                $course = $em->getRepository(Course::class)->findOneBy([
                    'code' => $filters['course_code'],
                ]);
                $query
                    ->andWhere('t.course = :course')
                    ->setParameter('course', $course);
            }
            if (isset($filters['skip_expired'])) {
                if ($filters['skip_expired']) {
                    $date = new \DateTime('now');
                    $query
                        ->andWhere('t.payTime > :date')
                        ->setParameter('date', $date);
                }
            }
        }
        return $query
            ->andWhere('t.billing_user = :billing_user')
            ->setParameter('billing_user', $filters['user'])
            ->getQuery()
            ->getResult();
    }
}
