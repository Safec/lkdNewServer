<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Draft;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Draft>
 *
 * @method Draft|null find($id, $lockMode = null, $lockVersion = null)
 * @method Draft|null findOneBy(array $criteria, array $orderBy = null)
 * @method Draft[]    findAll()
 * @method Draft[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DraftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Draft::class);
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(Draft $entity): self
    {
        $this->getEntityManager()->persist($entity);

        return $this;
    }

    public function remove(Draft $entity): self
    {
        $this->getEntityManager()->remove($entity);

        return $this;
    }

    public function findByAuthor(User $author): array
    {
        return $this->findBy(['author' => $author]);
    }
}
