<?php

namespace App\Repository;

use App\Entity\Plantao;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Plantao|null find($id, $lockMode = null, $lockVersion = null)
 * @method Plantao|null findOneBy(array $criteria, array $orderBy = null)
 * @method Plantao[]    findAll()
 * @method Plantao[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlantaoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plantao::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Plantao $entity, bool $flush = true): void
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
    public function remove(Plantao $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Plantao[] Returns an array of Plantao objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Plantao
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @return Plantao[] Returns an array of plantao objects
     */
    public function getAdminPlantoes()
    {
        $qb = $this->createQueryBuilder('plantao');

        $qb
            ->select('
                plantao.id AS id,
                plantao.start_at AS startAt,
                plantao.duration AS duration,
                plantao.type AS type,
                plantao.local AS local,
                plantao.value AS value,
                plantao.company AS company
            ')
            ->innerJoin(User::class,'user','WITH','plantao.user_id = user.id')
            // ->where('post.deleted = false')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Plantao[] Returns an array of plantao objects
     */
    public function getPlantoes($user)
    {
        $qb = $this->createQueryBuilder('plantao');

        $qb
            ->select('
                plantao
            ')
            ->where('plantao.user = :user')
            ->setParameter('user',$user)
            ->orderBy('plantao.id','DESC')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return Plantao[] Returns an array of plantao objects
     */
    public function getPegaPlantoes()
    {
        $qb = $this->createQueryBuilder('plantao');

        $qb
            ->select('
                plantao
            ')
            ->where('plantao.private = false')
            ->andWhere('plantao.user IS NULL')
            ->andWhere('plantao.is_valid = true')
            ->orderBy('plantao.id','DESC')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return Plantao[] Returns an array of platao objects
     */
    public function getPlantoesSchedule($user)
    {
        $qb = $this->createQueryBuilder('plantao');

        $qb
            ->select('
                plantao.local AS title,
                plantao.start_at AS date
            ')
            ->where('plantao.user = :user')
            ->setParameter('user',$user)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Plantao[] Returns an array of platao objects
     */
    public function getTodayPlantoesSchedule($user, $today)
    {
        $qb = $this->createQueryBuilder('plantao');

        $qb
            ->select('
                plantao
            ')
            ->where('plantao.user = :user')
            ->andWhere('plantao.start_at LIKE :today')
            ->setParameter('user',$user)
            ->setParameter('today','%'.$today.'%')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Plantao[] Returns an array of platao objects
     */
    public function getDashboardData($user)
    {
        $qb = $this->createQueryBuilder('plantao');

        $qb
            ->select('
                plantao.id AS id,
                plantao.value AS value
            ')
            ->where('plantao.user = :user')
            ->andWhere('MONTH(plantao.start_at) = MONTH(CURRENT_DATE())')
            ->andWhere('YEAR(plantao.start_at) = YEAR(CURRENT_DATE())')
            ->setParameter('user',$user)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Plantao[] Returns an array of platao objects
     */
    public function getLatestPlantao($user)
    {
        $qb = $this->createQueryBuilder('plantao');

        $qb
            ->select('
                plantao
            ')
            ->where('plantao.user = :user')
            ->andWhere('MONTH(plantao.start_at) = MONTH(CURRENT_DATE())')
            ->andWhere('YEAR(plantao.start_at) = YEAR(CURRENT_DATE())')
            ->andWhere('DAY(plantao.start_at) = DAY(CURRENT_DATE())')
            ->andWhere('HOUR(plantao.start_at) > HOUR(CURRENT_TIME())')
            ->setParameter('user',$user)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getResult();
    }
}
