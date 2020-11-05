<?php

namespace MartenaSoft\NestedSets\Repository;

use MartenaSoft\NestedSets\Entity\NodeInterface;

interface NestedSetsMoveItemsInterface
{
    public function move(NodeInterface $node, ?NodeInterface $parent): void;
}
