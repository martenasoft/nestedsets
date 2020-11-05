<?php

namespace MartenaSoft\NestedSets\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use MartenaSoft\NestedSets\Entity\NodeInterface;
use MartenaSoft\NestedSets\Exception\NestedSetsException;

abstract class AbstractBase
{
    private EntityManagerInterface $entityManager;
    private string $tableName;
    private string $entityClassName;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setEntityClassName(string $entityClassName): void
    {
        if (!is_subclass_of(new $entityClassName(), NodeInterface::class)) {
            throw new NestedSetsException(
                sprintf(
                    "The class %s not implement interface %s",
                    $entityClassName,
                    NodeInterface::class
                )
            );
        }
        $this->entityClassName = $entityClassName;
        $this->tableName = $this->getEntityManager()->getClassMetadata($entityClassName)->getTableName();
    }

    protected function getEntity(int $id, int $lft, int $rgt, int $lvl, int $tree): ?NodeInterface
    {
        $node = new $this->entityClassName();
        $node->setId($id)
            ->setLft($lft)
            ->setRgt($rgt)
            ->setLvl($lvl)
            ->setTree($tree);
    }

    protected function getTableName(): string
    {
        return $this->tableName;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}