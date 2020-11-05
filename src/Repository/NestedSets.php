<?php

namespace MartenaSoft\NestedSets\Repository;

use MartenaSoft\NestedSets\Entity\NodeInterface;

class NestedSets extends AbstractBase
{
    public static function getDeleteQuery (
        NodeInterface $nestedSetEntity,
        string $tableName
    ): string {

        $sql = "DELETE FROM `{$tableName}` 
                    WHERE lft >= {$nestedSetEntity->getLft()} 
                        AND rgt <= {$nestedSetEntity->getRgt()} 
                        AND tree = {$nestedSetEntity->getTree()};";

        $sql .= "UPDATE `{$tableName}` SET
                    lft = IF (lft > {$nestedSetEntity->getLft()},
                    lft - (((( {$nestedSetEntity->getRgt()} - {$nestedSetEntity->getLft()} - 1) / 2) + 1)*2), lft),
                    rgt = rgt- (((( {$nestedSetEntity->getRgt()} - {$nestedSetEntity->getLft()} - 1) / 2) + 1)*2)

                 WHERE rgt > {$nestedSetEntity->getRgt()} AND tree = {$nestedSetEntity->getTree()};";

        return $sql;
    }

    public static function getLastTreeIdSql(string $tableName): string
    {
        return "SELECT MAX(tree) FORM {$tableName}";
    }
}