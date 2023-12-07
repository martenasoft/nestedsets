<?php

namespace Martenasoft\Nestedsets\Repository\Interfaces;

use Martenasoft\Nestedsets\Entity\NodeInterface;

interface NestedsetsMoveItemsInterface
{
    public function move(NodeInterface $node, ?NodeInterface $parent): void;
}
