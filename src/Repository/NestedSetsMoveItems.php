<?php

namespace MartenaSoft\Repository\NestedSets;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use MartenaSoft\Common\Entity\NestedSetEntityInterface;
use MartenaSoft\Common\Repository\NestedSetServiceRepositoryInterface;
use MartenaSoft\NestedSets\Exception\NestedSetsNodeNotFoundException;
use MartenaSoft\NestedSets\Repository\AbstractBase;
use MartenaSoft\NestedSets\Repository\NestedSets;
use MartenaSoft\NestedSets\Repository\NestedSetsMoveItemsInterface;

class NestedSetsMoveItems extends AbstractBase implements NestedSetsMoveItemsInterface
{
    private const MOVE_TMP_TABLE = '_move_tmp';
    private const MOVE_TMP_TABLE_ALL_NODES = '_move_tmp_all_nodes';

    public function move(NestedSetEntityInterface $node, ?NestedSetEntityInterface $parent): void
    {
        $moveTmpTable = $this->getMovedTemporaryTableName();
        $nsTableName = $this->getTableName();
        $tmpAllNodesTableName = $this->getMovedTemporaryTableNameForAllNodes();

        $this->deleteTemplateTables();

        $sql = "CREATE TABLE IF NOT EXISTS `{$moveTmpTable}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,                
                `lft` int unsigned DEFAULT NULL,
                `rgt` int unsigned DEFAULT NULL,          
                `tree` int unsigned DEFAULT NULL,          
                `parent_id` int unsigned DEFAULT NULL,
                PRIMARY KEY (`id`)); ";

        $treeIdArray = [
            $node->getTree()
        ];

        if (!empty($parent) && $parent->getTree() != $node->getTree()) {
            $treeIdArray[] = $parent->getTree();
        }

        $sql .=  " CREATE TABLE `{$tmpAllNodesTableName}` 
                SELECT `ns`.`id`, 
                       `ns`.`parent_id`, 
                       `ns`.`lft`, 
                       `ns`.`rgt`, 
                       `ns`.`tree`, 
                       `ns`.`lvl`,
                       0 i 
                    FROM `{$nsTableName}` `ns` 
                WHERE `ns`.`tree` IN (" . implode(',', $treeIdArray) . ");";

        $this->getEntityManager()->getConnection()->executeQuery($sql);

        $this->getEntityManager()->getConnection()->beginTransaction();
        $throwException = null;

        try {
            $sql = "INSERT INTO `{$moveTmpTable}` 
                    SELECT `ns`.`id`, `ns`.`lft`, `ns`.`rgt`, `ns`.`tree`, `ns`.`parent_id` FROM `{$nsTableName}` `ns` 
                       WHERE `ns`.`lft` >= {$node->getLft()}
                          AND `ns`.`rgt` <= {$node->getRgt()}
                          AND `ns`.`tree` = {$node->getTree()}
                      ORDER BY `ns`.`lft` ";

            $insertedLength = $this->getEntityManager()->getConnection()->executeQuery($sql);

            if ($insertedLength == 0) {
                throw new \Exception('Inserted move users length is 0', 4);
            }

            $insertedLength *= 2;

            $sql = NestedSets::getDeleteQuery($node, $tmpAllNodesTableName);

            $this->getEntityManager()->getConnection()->executeQuery($sql);

            if ($parent !== null) {
                $parentNew = $this->getEntityManager()->getConnection()
                    ->fetchAssociative("SELECT * FROM {$tmpAllNodesTableName} WHERE id=:id AND tree=:tree",
                                 [
                                     "id" => $parent->getId(),
                                     "tree" => $parent->getTree()
                                 ]);

                if (empty($parentNew)) {
                    throw new NestedSetsNodeNotFoundException($parent->getId());
                }

                $sql = "UPDATE `{$tmpAllNodesTableName}` SET
                            lft = (
                                CASE
                                   WHEN lft > {$parentNew['lft']}
                                        AND rgt < {$parentNew['rgt']}
                                        AND tree = {$parentNew['tree']}
                                THEN lft + {$insertedLength}
                                
                                WHEN lft > {$parentNew['lft']}
                                     AND rgt > {$parentNew['rgt']}
                                     AND tree = {$parentNew['tree']}
                                THEN lft + {$insertedLength}
                                ELSE lft END
                            ),

                            rgt = (
                                CASE
                                    WHEN lft > {$parentNew['lft']}
                                         AND rgt < {$parentNew['rgt']}
                                         AND tree = {$parentNew['tree']}
                                    THEN rgt + {$insertedLength}
                                    
                                    WHEN (lft > {$parentNew['lft']}
                                         AND rgt > {$parentNew['rgt']}
                                         AND tree = {$parentNew['tree']}
                                         ) OR (lft <= {$parentNew['lft']}
                                         AND rgt >= {$parentNew['rgt']}
                                         AND tree = {$parentNew['tree']}
                                         ) 
                                    THEN rgt + {$insertedLength}
                                    ELSE rgt END
                                )
                        WHERE tree = {$parent->getTree()};";
                $sql .= "SET @s_ := 0;";
                $sql .= "INSERT INTO `{$tmpAllNodesTableName}` 
                        SELECT  id,
                                IF (@s_ = 0, {$parentNew['id']}, parent_id),
                                lft - {$node->getLft()} + 1 + {$parentNew['lft']}, 
                                rgt - {$node->getLft()} + 1 + {$parentNew['lft']},
                                {$parentNew['tree']},
                                 
                                ( 
                                    (SELECT COUNT(*) FROM {$moveTmpTable} t1 WHERE t1.lft < t2.lft AND t1.rgt>t2.rgt)  
                                    + {$parent->getLvl()} + 1
                                ),
                                @s_ := @s_ + 1
                                FROM {$moveTmpTable} t2;";

                $this->getEntityManager()->getConnection()->executeQuery($sql);
            } else {

                $maxTree = $this->getEntityManager()->getConnection()->fetchNumeric(
                    NestedSets::getLastTreeIdSql($nsTableName)
                );

                $sql = "@s_ := 0;";
                $sql .= "INSERT INTO `{$tmpAllNodesTableName}` 
                        SELECT IF (@s_ = 0, 0, parent_id),
                               @s_ := 1,
                               lft - {$node->getLft()} + 1, 
                               rgt - {$node->getLft()} + 1,
                               " . ($maxTree + 1) . ",
                               (
                                    (SELECT COUNT(*) FROM user_front_moved_users_ns_tmp t1 
                                        WHERE t1.lft < t2.lft AND t1.rgt>t2.rgt)  + 1
                               )         
                        FROM {$moveTmpTable} t2";
                $this->getEntityManager()->getConnection()->executeQuery($sql);
            }


            $this->migrateFromTemporaryTable();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $exception) {
            $this->getEntityManager()->getConnection()->rollBack();
            $throwException = $exception;
        }

        $this->deleteTemplateTables();

        if ($throwException instanceof \Throwable) {
            throw $throwException;
        }
    }

    private function getMovedTemporaryTableName(): string
    {
        return $this->getTableName() . '_' . self::MOVE_TMP_TABLE;
    }

    private function getMovedTemporaryTableNameForAllNodes(): string
    {
        return $this->getTableName() . '_' . self::MOVE_TMP_TABLE_ALL_NODES;
    }

    private function migrateFromTemporaryTable(): void
    {
        $allNodesTmpTableName = $this->getMovedTemporaryTableNameForAllNodes();
        $nsTableName = $this->getTableName();
        $sql = "UPDATE `{$nsTableName}` ns 
                    INNER JOIN {$allNodesTmpTableName} nst 
                    ON ns.id = nst.id 
                SET ns.lft = nst.lft, 
                    ns.rgt = nst.rgt, 
                    ns.tree = nst.tree, 
                    ns.lvl = nst.lvl, 
                    ns.parent_id = nst.parent_id";

        $this->getEntityManager()->getConnection()->executeQuery($sql);
    }

    private function deleteTemplateTables(): void
    {
        $moveTmpTable = $this->getMovedTemporaryTableName();
        $tmpAllNodesTableName = $this->getMovedTemporaryTableNameForAllNodes();
       $sql = "DROP TABLE IF EXISTS `{$moveTmpTable}`;";
        $sql .= "DROP TABLE IF EXISTS `{$tmpAllNodesTableName}`;";
        $this->getEntityManager()->getConnection()->execQuery($sql);
    }
}

