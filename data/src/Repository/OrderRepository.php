<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(Order $entity): self
    {
        $this->getEntityManager()->persist($entity);

        return $this;
    }

    public function remove(Order $entity): self
    {
        $this->getEntityManager()->remove($entity);

        return $this;
    }

    public function findOrdersByUser($userId)
    {
        return $this->createQueryBuilder('ur')
            ->where('ur.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ur.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function search(array $filter): Paginator
    {
        $default = [
            'query' => '',
            'per_page' => 600,
            'page' => 1,
        ];

        $filter = array_merge($default, $filter);

        // Определение допустимого направления сортировки
        //$sortDirection = strtolower($sortDirection) === 'desc' ? 'DESC' : 'ASC';

        // Создание части ORDER BY
        $orderBy = match ($sort) {
            //default => "ur.processed ASC, ur.createdAt DESC",
            default => "ur.date DESC",
        };

        $query = $this->getEntityManager()->createQuery(
            'SELECT ur FROM App\Entity\Order ur '.
            'INNER JOIN ur.user u ' .
            'LEFT JOIN u.profile p ' .
            'LEFT JOIN u.requisites r ' .
            'WHERE ' .
            ' (
                :query = \'\'
                OR LOWER(ur.orderNumber) LIKE LOWER(:query)
                OR LOWER(ur.status) LIKE LOWER(:query)
                OR LOWER(p.fullName) LIKE LOWER(:query)
                OR LOWER(u.phone) LIKE LOWER(:query)
                OR LOWER(u.email) LIKE LOWER(:query)
                OR LOWER(u.login) LIKE LOWER(:query)
            ) ' .
            ' ORDER BY ' . $orderBy
        );

        $query->setParameters([
            'query' => strlen($filter['query']) ? "%{$filter['query']}%" : ''
        ]);

        $query->setFirstResult((int)$filter['per_page'] * ((int)$filter['page'] - 1));
        $query->setMaxResults((int)$filter['per_page']);

        return new Paginator($query);
    }

}
