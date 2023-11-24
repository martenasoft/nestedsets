<?php

namespace Martenasoft\Nestedsets\Repository;

use Martenasoft\Nestedsets\Entity\NodeInterface;

interface NestedSetsMoveItemsInterface
{
    public function move(NodeInterface $node, ?NodeInterface $parent): void;
}
