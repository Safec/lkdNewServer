<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function save(Project $project): self
    {
        $this->getEntityManager()->persist($project);

        return $this;
    }

    public function remove(Project $project): self
    {
        $this->getEntityManager()->remove($project);

        return $this;
    }

    /**
     * @return Project[]
     */
    public function findByAuthor(User $author): array
    {
        return $this->findBy(['author' => $author]);
    }

}
