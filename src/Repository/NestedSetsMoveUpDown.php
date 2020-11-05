<?php

namespace MartenaSoft\NestedSets\Repository;

use MartenaSoft\NestedSets\DataDriver\DataDriverInterface;
use MartenaSoft\NestedSets\Entity\NodeInterface;
use MartenaSoft\NestedSets\Exception\NestedSetsNodeNotFoundException;

class NestedSetsMoveUpDown extends AbstractBase implements NestedSetsMoveUpDownInterface
{
    public function change(NodeInterface $node, bool $isUp = true): void
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $nextNode = $this->findNear($node, $isUp);

            if (empty($nextNode)) {
                $nextNode = $this->findExtreme($node, $isUp);
            }

            if (empty($nextNode)) {
                throw new NestedSetsNodeNotFoundException();
            }

            $this->exchangeKeys($node, $nextNode);
            $this->exchangeParentIdForSubItems($node, $nextNode);

            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $exception) {
            $this->getEntityManager()->getConnection()->rollback();
            throw $exception;
        }
    }

    private function exchangeKeys(NodeInterface $node1, NodeInterface $node2): ?int
    {
        $lft = $node1->getLft();
        $rgt = $node1->getRgt();
        $lvl = $node1->getLvl();

        $parentId = $node1->getParentId();

        $sql = "UPDATE `{$this->getTableName()}` SET 
                            `lft` = {$node2->getLft()},
                            `rgt` = {$node2->getRgt()},
                            `lvl` = {$node2->getLvl()},                          
                            `parent_id` = {$node2->getParentId()}                          
                         WHERE `id` = {$node1->getId()} AND `tree` = {$node1->getTree()}       
                    ;";

        $sql .= "UPDATE `{$this->getTableName()}` SET 
                            `lft` = {$lft},
                            `rgt` = {$rgt},
                            `lvl` = {$lvl},
                            `parent_id` = {$parentId}
                         WHERE `id` = {$node2->getId()} AND `tree` = {$node1->getTree()}       
                    ;";

        try {
            return $this->getEntityManager()->getConnection()->executeQuery($sql)->rowCount();
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    private function exchangeParentIdForSubItems(NodeInterface $node1, NodeInterface $node2): ?int
    {
        $sql = "UPDATE `{$this->getTableName()}` SET `parent_id` = {$node2->getId()}
                WHERE `lft` > {$node1->getLft()} AND                                
                     `rgt` < {$node1->getRgt()} AND 
                      `lvl` <= {$node1->getLvl()} + 1 AND
                      `tree` = {$node1->getTree()};";

        $sql .= "UPDATE `{$this->getTableName()}` SET `parent_id` = {$node1->getId()}
                WHERE `lft` > {$node2->getLft()} AND                                
                     `rgt` < {$node2->getRgt()} AND 
                      `lvl` <= {$node2->getLvl()} + 1 AND
                      `tree` = {$node1->getTree()};";


        try {
            return $this->getEntityManager()->getConnection()->executeQuery($sql)->rowCount();
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    private function findNear(NodeInterface $node, bool $isUp = true): ?NodeInterface
    {
        $this->getgetEntityManager()->refresh($node);

        $sql = "SELECT * FROM `{$this->getTableName()}` WHERE ";
        $sql .= $isUp ? "`rgt` > {$node->getRgt()}" : "`lft` > {$node->getLft()}";
        $sql .= " AND `tree` = {$node->getTree()}";
        $sql .= " ORDER BY `lft` ASC";
        $sql .= " LIMIT 1";
        $result = $this->getEntityManager()->getConnection()->fetchAssociative($sql);

        if (!empty($result)) {
            return $this->getEntity($result['id'], $result['lft'], $result['rgt'], $result['lvl'], $result['tree']);
        }
    }

    private function findExtreme(NodeInterface $node, bool $isLast = true): ?NodeInterface
    {
        $sql = "SELECT * FROM `{$this->getTableName()}` WHERE";
        $sql .= !$isLast ? "`lft`= 1 AND " : "";
        $sql .= " `tree` = {$node->getTree()}";
        $sql .= $isLast ? " ORDER BY `lvl` DESC" : "";
        $sql .= " LIMIT 1";
        $result = $this->getEntityManager()->getConnection()->fetchAssociative($sql);
        if (!empty($result)) {
            return $this->getEntity($result['id'], $result['lft'], $result['rgt'], $result['lvl'], $result['tree']);
        }
    }
}