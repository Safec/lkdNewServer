<?php

namespace App\Repository;

use App\Entity\Model;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Model>
 *
 * @method Model|null find($id, $lockMode = null, $lockVersion = null)
 * @method Model|null findOneBy(array $criteria, array $orderBy = null)
 * @method Model[]    findAll()
 * @method Model[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Model::class);
    }

    public function save(Model $model): self
    {
        $this->getEntityManager()->persist($model);

        return $this;
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function remove(Model $model, bool $flush = false): void
    {
        $this->getEntityManager()->remove($model);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function search(array $filter, string $sort = '', string $sortDirection = 'asc'): Paginator
    {
        $default = [
            'query' => '',
            'per_page' => 6,
            'page' => 1,
            'subCategories' => []
        ];

        $filter = array_merge($default, $filter);

        // Определение допустимого направления сортировки
        $sortDirection = strtolower($sortDirection) === 'desc' ? 'DESC' : 'ASC';

        // Создание части ORDER BY
        $orderBy = match ($sort) {
            //'fullName' => "p.fullName $sortDirection, u.id $sortDirection",
            //'registeredAt' => "u.createdAt DESC, u.id $sortDirection",
            //'phone' => "u.phone $sortDirection",
            //'email' => "u.email $sortDirection",
            //'legalEntity' => "p.legalEntity $sortDirection",
           // 'status' => "u.status $sortDirection",
            default => "m.name $sortDirection",
        };

        // Создание QueryBuilder
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->select('m, sc, c')
            ->from('App\Entity\Model', 'm')
            ->leftJoin('m.subcategory', 'sc')
            ->leftJoin('sc.category', 'c')
            ->where('1=1');

        // Добавляем условие для фильтрации по поисковому запросу
                $queryBuilder->andWhere('
            :query = \'\'
            OR LOWER(m.name) LIKE LOWER(:query)
            OR LOWER(m.platform) LIKE LOWER(:query)
            OR LOWER(m.render) LIKE LOWER(:query)
        ');

        // Условие для фильтрации по подкатегориям
        if (!empty($filter['subcategories'])) {
            $queryBuilder->andWhere('sc.id IN (:subcategories)')
                ->setParameter('subcategories', $filter['subcategories']);
        }

        $queryBuilder->setParameter('query', strlen($filter['query']) ? "%{$filter['query']}%" : '');

        // Установка параметров для пагинации
        $queryBuilder->setFirstResult((int)$filter['per_page'] * ((int)$filter['page'] - 1))
            ->setMaxResults((int)$filter['per_page']);

        // Получаем запрос и выполняем его
        $query = $queryBuilder->getQuery();

        return new Paginator($query);
    }

//    /**
//     * @return Model[] Returns an array of Model objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Model
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
