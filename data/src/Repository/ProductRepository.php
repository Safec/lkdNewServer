<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Draft;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(Product $entity): self
    {
        $this->getEntityManager()->persist($entity);

        return $this;
    }

    /** @noinspection PhpUnused */
    public function remove(Product $entity): self
    {
        $this->getEntityManager()->remove($entity);

        return $this;
    }

    public function findParsedByDraft(Draft $draft): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.url IN (:urls)')
            ->andWhere('p.parsedAt IS NOT NULL')
            ->setParameter('urls', $draft->getProducts())
            ->getQuery()
            ->getResult();
    }
}
