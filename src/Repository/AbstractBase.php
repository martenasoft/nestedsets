<?php

namespace MartenaSoft\NestedSets\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use MartenaSoft\NestedSets\Entity\NodeInterface;

abstract class AbstractBase extends ServiceEntityRepository
{
    protected string $alias = 'ns';

    public function getTableName(): string
    {
        return $this->getClassMetadata()->getTableName();
    }

    public function execQuery(string $sql): ?int
    {
        print $sql;
        try {
            return $this->getEntityManager()->getConnection()->executeQuery($sql)->rowCount();
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function beginTransaction(): void
    {
        $this->getEntityManager()->beginTransaction();
    }

    public function commit(): void
    {
        $this->getEntityManager()->commit();
    }

    public function rollback(): void
    {
        $this->getEntityManager()->rollback();
    }

    public function getItemsQueryBuilder(NodeInterface $nestedSetEntity): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder($this->alias);
        $queryBuilder->andWhere("{$this->alias}.lft>:lft")
            ->setParameter('lft', $nestedSetEntity->getLft());

        $queryBuilder->andWhere("{$this->alias}.rgt<:rgt")
            ->setParameter('rgt', $nestedSetEntity->getRgt());

        $queryBuilder->andWhere("{$this->alias}.tree=:tree")
            ->setParameter('tree', $nestedSetEntity->getTree());

        return $queryBuilder;
    }

    public function findNear(NodeInterface $node, bool $isUp = true): ?NodeInterface
    {
        $queryBuilder =  $this->createQueryBuilder($this->alias);

        if ($isUp) {
            $queryBuilder->andWhere("{$this->alias}.lft>:lft")->setParameter("lft", $node->getLft());
        } else {
            $queryBuilder->andWhere("{$this->alias}.rgt>:rgt")->setParameter("rgt", $node->getRgt());
        }

        return $queryBuilder
            ->andWhere("{$this->alias}.tree=:tree")
            ->setParameter("tree", $node->getTree())
            ->orderBy("{$this->alias}.lft", "ASC")
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExtreme(NodeInterface $node, bool $isFirst = true): ?NodeInterface
    {
        $queryBuilder =  $this->createQueryBuilder($this->alias);

        if ($isFirst) {
            $queryBuilder->andWhere("{$this->alias}.lft=:lft")->setParameter("lft", 1);
        } else {
            $queryBuilder->orderBy("{$this->alias}.lvl", "DESC");
        }

        return $queryBuilder
            ->andWhere("{$this->alias}.tree=:tree")
            ->setParameter("tree", $node->getTree())
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}