<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Invoice $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Invoice $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Invoice[] Returns an array of Invoice objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @return Invoice[] Returns an array of invoices objects
     */
    public function getCurrentInvoice($user)
    {
        $qb = $this->createQueryBuilder('invoice');

        $qb
            ->select('
                invoice.value AS value
            ')
            ->where('invoice.user = :user')
            ->andWhere('MONTH(invoice.due_date) = MONTH(CURRENT_DATE())')
            ->andWhere('YEAR(invoice.due_date) = YEAR(CURRENT_DATE())')
            ->setParameter('user',$user)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Invoice[] Returns an array of invoices objects
     */
    public function getMonthInvoices()
    {
        $qb = $this->createQueryBuilder('invoice');

        $qb
            ->select('
                invoice.value AS value,
                invoice.is_paid AS isPaid
            ')
            ->where('MONTH(invoice.due_date) = MONTH(CURRENT_DATE())')
            ->andWhere('YEAR(invoice.due_date) = YEAR(CURRENT_DATE())')
        ;

        return $qb->getQuery()->getResult();
    }

    
}
