<?php

namespace Martenasoft\Nestedsets\Repository\Interfaces;

use Martenasoft\Nestedsets\Entity\NodeInterface;

interface NestedsetsMoveUpDownInterface
{
    public function upDown(NodeInterface $node, bool $isUp = true): void;
}

