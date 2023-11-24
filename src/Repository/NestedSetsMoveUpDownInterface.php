<?php

namespace Martenasoft\Nestedsets\Repository;

use Martenasoft\Nestedsets\Entity\NodeInterface;

interface NestedSetsMoveUpDownInterface
{
    public function upDown(NodeInterface $node, bool $isUp = true): void;
}

