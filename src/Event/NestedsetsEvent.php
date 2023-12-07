<?php

namespace Martenasoft\Nestedsets\Event;

use Martenasoft\Nestedsets\Entity\NodeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class NestedsetsEvent extends Event
{
    private $node;
    private $parent;

    private $isSafeDelete;

    public const NAME_CREATE_BEFORE = "nestedsets.event.create.before";
    public const NAME_CREATE_AFTER = "nestedsets.event.create.after";

    public const NAME_MOVE_BEFORE = "nestedsets.event.move.before";
    public const NAME_MOVE_AFTER = "nestedsets.event.move.after";

    public const NAME_UP_BEFORE = "nestedsets.event.up.before";
    public const NAME_UP_AFTER = "nestedsets.event.up.after";

    public const NAME_DOWN_BEFORE = "nestedsets.event.down.before";
    public const NAME_DOWN_AFTER = "nestedsets.event.down.after";

    public const NAME_DELETE_BEFORE = "nestedsets.event.delete.before";
    public const NAME_DELETE_AFTER = "nestedsets.event.delete.after";

    public function __construct(NodeInterface $node, ?NodeInterface $parent = null, bool $isSafeDelete = false)
    {
        $this->node = $node;
        $this->parent = $parent;
        $this->isSafeDelete = $isSafeDelete;
    }
    public function getNode(): ?NodeInterface
    {
        return $this->node;
    }

    public function getParent(): ?NodeInterface
    {
        return $this->parent;
    }
}