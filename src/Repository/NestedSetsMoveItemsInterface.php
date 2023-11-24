<?php

namespace Martenasoft\NestedSets\Repository;

use Martenasoft\NestedSets\Entity\NodeInterface;

interface NestedSetsMoveItemsInterface
{
    public function move(NodeInterface $node, ?NodeInterface $parent): void;
}
