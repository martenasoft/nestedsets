<?php

namespace MartenaSoft\NestedSets\Repository;

use MartenaSoft\NestedSets\Entity\NodeInterface;

interface NestedSetsMoveUpDownInterface
{
    public function change(NodeInterface $node, bool $isUp = true): void;
}

