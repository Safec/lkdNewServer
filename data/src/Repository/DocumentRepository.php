<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Document;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 *
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(Document $entity): self
    {
        $this->getEntityManager()->persist($entity);

        return $this;
    }

    /** @noinspection PhpUnused */
    public function remove(Document $entity): self
    {
        $this->getEntityManager()->remove($entity);

        return $this;
    }

    public function findOneByUserAndName(User $user, string $name): ?Document
    {
        return $this->findOneBy(['relatedUser' => $user, 'name' => $name]);
    }

    /**
     * @param string $path
     * @return Document[]
     */
    public function findByPath(string $path): array
    {
        return $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.files LIKE :path')
            ->setParameter('path', '%' . $path . '%')
            ->getQuery()
            ->getResult();
    }
}
