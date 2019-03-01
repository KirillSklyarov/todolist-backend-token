<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param User $user
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function create(User $user)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $em->persist($user);
            $em->flush($user);
            $em->refresh($user);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }

    /**
     * @param User $user
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function update(User $user)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $em->merge($user);
            $em->flush($user);
            $em->refresh($user);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }

    /**
     * @param User $user
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function delete(User $user)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $em->remove($user);
            $em->flush($user);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }
    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
