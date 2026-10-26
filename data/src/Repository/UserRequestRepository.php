<?php

namespace App\Repository;

use App\Entity\UserRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRequest[]    findAll()
 * @method UserRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRequest::class);
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(UserRequest $userRequest): self
    {
        $this->getEntityManager()->persist($userRequest);

        return $this;
    }

    public function findRequestsByUser($userId)
    {
        return $this->createQueryBuilder('ur')
            ->where('ur.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ur.createdAt', 'DESC')
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
            default => "ur.createdAt DESC",
        };

        $query = $this->getEntityManager()->createQuery(
            'SELECT ur FROM App\Entity\UserRequest ur '.
            'INNER JOIN ur.user u ' .
            'LEFT JOIN u.profile p ' .
            'LEFT JOIN u.requisites r ' .
            'WHERE ' .
            ' (
                :query = \'\'
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
