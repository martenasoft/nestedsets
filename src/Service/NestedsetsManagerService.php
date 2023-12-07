<?php

namespace Martenasoft\Nestedsets\Service;

use Doctrine\ORM\QueryBuilder;
use Martenasoft\Common\Repository\Traits\AliasRepositoryTrait;
use Martenasoft\Nestedsets\Entity\NodeInterface;
use Martenasoft\Nestedsets\Event\NestedsetsEvent;
use Martenasoft\Nestedsets\Repository\Interfaces\NestedsetsCreateDeleteInterface;
use Martenasoft\Nestedsets\Repository\Interfaces\NestedsetsMoveItemsInterface;
use Martenasoft\Nestedsets\Repository\Interfaces\NestedsetsMoveUpDownInterface;
use Martenasoft\Nestedsets\Repository\NestedsetsCreateDelete;
use Martenasoft\Nestedsets\Repository\NestedsetsMoveItems;
use Martenasoft\Nestedsets\Repository\NestedsetsMoveUpDown;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NestedsetsManagerService implements
    NestedsetsCreateDeleteInterface,
    NestedsetsMoveItemsInterface,
    NestedsetsMoveUpDownInterface
{

    use AliasRepositoryTrait;
    private $nestedSetsMoveItems;
    private $nestedSetsMoveUpDown;
    private $nestedSetsCreateDelete;

    private $eventDispatcher;

    public function __construct(
      NestedsetsMoveItems $nestedsetsMoveItems,
      NestedsetsMoveUpDown $nestedsetsMoveUpDown,
      NestedsetsCreateDelete $nestedsetsCreateDelete,
      EventDispatcherInterface $eventDispatcher
    ) {
        $this->nestedSetsMoveItems = $nestedsetsMoveItems;
        $this->nestedSetsMoveUpDown = $nestedsetsMoveUpDown;
        $this->nestedSetsCreateDelete = $nestedsetsCreateDelete;
        $this->eventDispatcher = $eventDispatcher;

    }

    public function init(string $alias, string $entityClass)
    {
        $this->setAlias($alias);
        $this->nestedSetsCreateDelete->setEntityClassName($entityClass);
        $this->nestedSetsMoveUpDown->setEntityClassName($entityClass);
        $this->nestedSetsMoveItems->setEntityClassName($entityClass);
    }

    public function create(NodeInterface $node, ?NodeInterface $parent = null): NodeInterface
    {
        $this->eventDispatcher->dispatch(
            NestedsetsEvent::NAME_CREATE_BEFORE,
            new NestedsetsEvent($node, $parent)
        );
        $parent = (empty($parent)) ? $node->getParent() : $parent;
        $result = $this->nestedSetsCreateDelete->create($node, $parent);

        $this->eventDispatcher->dispatch(
            NestedsetsEvent::NAME_CREATE_AFTER,
            new NestedsetsEvent($node, $parent)
        );
        return $result;
    }

    public function delete(NodeInterface $node, bool $isSafeDelete = true): void
    {
        $this->eventDispatcher->dispatch(
            NestedsetsEvent::NAME_DELETE_BEFORE,
            new NestedsetsEvent($node, null, $isSafeDelete)
        );
        $this->nestedSetsCreateDelete->delete($node, $isSafeDelete);

        $this->eventDispatcher->dispatch(
            NestedsetsEvent::NAME_DELETE_AFTER,
            new NestedsetsEvent($node, null, $isSafeDelete)
        );
    }

    public function move(NodeInterface $node, ?NodeInterface $parent = null): void
    {
        if ($parent == null) {
            $parent = $node->getParent();
        }

        $this->eventDispatcher->dispatch(
            NestedsetsEvent::NAME_MOVE_BEFORE,
            new NestedsetsEventMove($node, $parent)
        );

        $this->nestedSetsMoveItems->move($node, $parent);

        $this->eventDispatcher->dispatch(
            NestedsetsEvent::NAME_MOVE_AFTER,
            new NestedsetsEventMove($node, $parent)
        );
    }

    public function upDown(NodeInterface $node, bool $isUp = true): void
    {
        $this->eventDispatcher->dispatch(
            $isUp ? NestedsetsEvent::NAME_UP_BEFORE : NestedsetsEvent::NAME_DOWN_BEFORE,
            new NestedsetsEvent($node)
        );

        $this->nestedSetsMoveUpDown->upDown($node, $isUp);

        $this->eventDispatcher->dispatch(
            $isUp ? NestedsetsEvent::NAME_UP_AFTER : NestedsetsEvent::NAME_DOWN_AFTER,
            new NestedsetsEventMoveUp($node)
        );
    }

    public function getAllQueryBuilder(
        QueryBuilder $queryBuilder,
        string $alias,
        ?int $treeId = null,
        ?int $deep = null,
        ?NodeInterface $parentNode = null,
        bool $isReverce = false,
        bool $isIncludeCurrentItem = false
    ): void {

        if ($treeId !== null) {
            $queryBuilder
                ->andWhere("{$alias}.tree = :tree")
                ->setParameter("tree", $treeId)
            ;
        }

        if ($deep !== null) {
            if ($parentNode !== null) {
                $queryBuilder
                    ->andWhere("({$alias}.".$parentNode->getLvl()." - {$alias}.lvl) <=: lvl")
                    ->setParameter("lvl", $deep )
                ;
            } else {
                $queryBuilder
                    ->andWhere("{$alias}.lvl <=: lvl")
                    ->setParameter("lvl", $deep )
                ;
            }
        }

        $queryBuilder
            ->addOrderBy("{$alias}.tree", $isReverce ? "ASC" : "DESC");

        if (empty($parentNode)) {
            $queryBuilder
                ->addOrderBy("{$alias}.lft", $isReverce ? "ASC" : "DESC");
            return;
        }

        $equalSignLess = ($isReverce ? "<" : ">") . ($isIncludeCurrentItem ? '=' : '') ;
        $equalSignMore = ($isReverce ? ">" : "<=") . ($isIncludeCurrentItem ? '=' : '');

        $queryBuilder
            ->andWhere("{$alias}.lft $equalSignLess :lft ")
            ->setParameter("lft", $parentNode->getLft())
            ->andWhere("{$alias}.rgt $equalSignMore :rgt")
            ->setParameter("rgt", $parentNode->getRgt())
        ;
    }
}
