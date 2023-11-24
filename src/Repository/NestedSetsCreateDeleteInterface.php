<?php

namespace Martenasoft\Nestedsets\Repository;

use Martenasoft\Nestedsets\Entity\NodeInterface;

interface NestedSetsCreateDeleteInterface
{
    public function create(NodeInterface $nestedSetEntity, ?NodeInterface $parent = null): NodeInterface;
    public function delete(NodeInterface $node, bool $isSafeDelete = true): void;
}