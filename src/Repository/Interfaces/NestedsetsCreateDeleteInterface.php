<?php

namespace Martenasoft\Nestedsets\Repository\Interfaces;

use Martenasoft\Nestedsets\Entity\NodeInterface;

interface NestedsetsCreateDeleteInterface
{
    public function create(NodeInterface $nestedSetEntity, ?NodeInterface $parent = null): NodeInterface;
    public function delete(NodeInterface $node, bool $isSafeDelete = true): void;
}