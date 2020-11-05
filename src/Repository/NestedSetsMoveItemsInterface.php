<?php

namespace MartenaSoft\NestedSets\Repository;

use MartenaSoft\Common\Entity\NestedSetEntityInterface;

interface NestedSetsMoveItemsInterface
{
    public function move(NestedSetEntityInterface $node, ?NestedSetEntityInterface $parent): void;
}
