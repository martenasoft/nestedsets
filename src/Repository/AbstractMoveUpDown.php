<?php

namespace MartenaSoft\NestedSets\Repository;

use Codeception\Util\Debug;
use MartenaSoft\NestedSets\DataDriver\DataDriverInterface;
use MartenaSoft\NestedSets\Entity\NodeInterface;
use MartenaSoft\NestedSets\Exception\NestedSetsNodeNotFoundException;

abstract class AbstractMoveUpDown extends AbstractBase
{
    public function change(NodeInterface $node, bool $isUp = true): void
    {
        try {
            $this->beginTransaction();
            $nextNode = $this->findNear($node, $isUp);

            if (empty($nextNode)) {
                $nextNode = $this->findExtreme($node, $isUp);
            }

            if (empty($nextNode)) {
                throw new NestedSetsNodeNotFoundException();
            }

            $this->exchangeKeys($node, $nextNode);

            if ($isUp) {
                $this->exchangeParentIdForSubItems($node, $nextNode);
           //     $updetesLength = $this->exchangeParentIdForSubItems($nextNode, $node);
            } else {

            }

            $this->commit();
        } catch (\Throwable $exception) {
            $this->rollback();
            Debug::debug($exception->getMessage());
            throw $exception;
        }
    }

    public function exchangeKeys(NodeInterface $node1, NodeInterface $node2): ?int
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
            return $this->execQuery($sql);
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function exchangeParentIdForSubItems(NodeInterface $node1, NodeInterface $node2): ?int
    {
        $sql = "UPDATE `{$this->getTableName()}` SET `parent_id` = {$node2->getId()}
                WHERE `lvl` > {$node1->getId()} AND                                
                      `rgt` < {$node1->getRgt()} AND
                      `lvl` = {$node1->getLvl()} + 1 AND
                      `tree` = {$node1->getTree()}";


        try {
            return $this->execQuery($sql);
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}