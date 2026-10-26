<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        if (!strlen($identifier)) {
            return null;
        }

        $query = $this->getEntityManager()->createQuery(
            'SELECT user FROM App\Entity\User user ' .
            'WHERE user.phone = :identifier OR LOWER(user.email) = LOWER(:identifier) OR LOWER(user.login) = LOWER(:identifier)'
        )->setParameter('identifier', $identifier);

        return $query->getOneOrNullResult();
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(User $user): self
    {
        $this->getEntityManager()->persist($user);

        return $this;
    }

    public function remove(User $user): self
    {
        $this->getEntityManager()->remove($user);

        return $this;
    }

    public function findByRole(string $role): array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT u FROM App\Entity\User u ' .
            'LEFT JOIN u.profile p ' .
            'WHERE u.roles LIKE :role ' .
            'ORDER BY p.fullName ASC'
        )->setParameter('role', "%$role%");

        return $query->getResult();
    }

    public function findActiveByRole(string $role): array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT u FROM App\Entity\User u ' .
            'LEFT JOIN u.profile p ' .
            'WHERE u.roles LIKE :role ' .
            'AND u.active = true ' .
            'ORDER BY p.fullName ASC'
        )->setParameter('role', "%$role%");

        return $query->getResult();
    }

    public function search(array $filter, string $sort = '', string $sortDirection = 'asc'): Paginator
    {
        $default = [
            'role' => '',
            'query' => '',
            'only_new' => false,
            'per_page' => 6,
            'page' => 1,
        ];

        $filter = array_merge($default, $filter);

        // Определение допустимого направления сортировки
        $sortDirection = strtolower($sortDirection) === 'desc' ? 'DESC' : 'ASC';

        // Создание части ORDER BY
        $orderBy = match ($sort) {
            'fullName' => "p.fullName $sortDirection, u.id $sortDirection",
            'registeredAt' => "u.createdAt DESC, u.id $sortDirection",
            'phone' => "u.phone $sortDirection",
            'email' => "u.email $sortDirection",
            'legalEntity' => "p.legalEntity $sortDirection",
            'status' => "u.status $sortDirection",
            default => "u.id $sortDirection",
        };

        $query = $this->getEntityManager()->createQuery(
            'SELECT u FROM App\Entity\User u ' .
            'LEFT JOIN u.profile p ' .
            'LEFT JOIN u.requisites r ' .
            'WHERE (:role = \'\' OR u.roles LIKE :role) ' .
            'AND (
                :query = \'\'
                OR LOWER(p.fullName) LIKE LOWER(:query)
                OR LOWER(u.phone) LIKE LOWER(:query)
                OR LOWER(u.email) LIKE LOWER(:query)
                OR LOWER(u.login) LIKE LOWER(:query)
            ) ' .
            'AND (:only_new = false OR (
                u.active != true
                OR r.contractNumber IS NULL
                OR r.contractNumber = \'\'
            )) ORDER BY ' . $orderBy
        );

        $query->setParameters([
            'query' => strlen($filter['query']) ? "%{$filter['query']}%" : '',
            'role' => $filter['role'] ? "%{$filter['role']}%" : '',
            'only_new' => (bool)$filter['only_new'],
        ]);

        $query->setFirstResult((int)$filter['per_page'] * ((int)$filter['page'] - 1));
        $query->setMaxResults((int)$filter['per_page']);

        return new Paginator($query);
    }
}
