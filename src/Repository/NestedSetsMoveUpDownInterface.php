<?php

namespace Martenasoft\NestedSets\Repository;

use Martenasoft\NestedSets\Entity\NodeInterface;

interface NestedSetsMoveUpDownInterface
{
    public function upDown(NodeInterface $node, bool $isUp = true): void;
}

