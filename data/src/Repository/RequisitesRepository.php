<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Requisites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Requisites>
 *
 * @method Requisites|null find($id, $lockMode = null, $lockVersion = null)
 * @method Requisites|null findOneBy(array $criteria, array $orderBy = null)
 * @method Requisites[]    findAll()
 * @method Requisites[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequisitesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Requisites::class);
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(Requisites $entity): self
    {
        $this->getEntityManager()->persist($entity);

        return $this;
    }

    /** @noinspection PhpUnused */
    public function remove(Requisites $entity): self
    {
        $this->getEntityManager()->remove($entity);

        return $this;
    }
}
