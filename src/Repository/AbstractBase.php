<?php

namespace Martenasoft\Nestedsets\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Martenasoft\Nestedsets\Entity\NodeInterface;
use Martenasoft\Nestedsets\Exception\NestedSetsException;

abstract class AbstractBase
{
    private EntityManagerInterface $entityManager;
    private string $tableName;
    private string $entityClassName;

    protected $alias = 'ns';

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

    protected function getEntity(int $id, int $lft, int $rgt, int $lvl, int $parentId, int $tree): ?NodeInterface
    {
        $parent = $this->getEntityById($parentId);

        $node = new $this->entityClassName();
        $node
            ->setId($id)
            ->setLft($lft)
            ->setRgt($rgt)
            ->setLvl($lvl)
            ->setParent($parent)
            ->setTree($tree);
        return $node;
    }

    protected function getTableName(): string
    {
        return $this->tableName;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function getEntityById(?int $id): ?NodeInterface
    {
        if (empty($id)) {
            return null;
        }

       return  $this->getEntityManager()->find($this->entityClassName, $id);
    }
}