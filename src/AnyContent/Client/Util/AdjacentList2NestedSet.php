<?php

declare(strict_types=1);

namespace AnyContent\Client\Util;

/**
 * @link    : http://www.bluegate.at/tutorials-faqs/design-patterns/nested-sets-verstehen-und-anwenden/
 * @link    : http://gen5.info/q/2008/11/04/nested-sets-php-verb-objects-and-noun-objects/
 */
class AdjacentList2NestedSet
{
    protected $links = [];
    protected $parentIds = [];
    protected $count = 1;
    protected $nestedSet = [];
    protected $level = 0;

    public function __construct($list)
    {
        $link      = [];
        $parentIds = [];
        foreach ($list as $record) {
            $parent = $record['parentId'];
            $child  = $record['id'];
            if (!array_key_exists($parent, $link)) {
                $link[$parent] = [];
            }
            $link[$parent][]   = $child;
            $parentIds[$child] = $parent;
        }

        $this->parentIds = $parentIds;
        $this->count     = 1;
        $this->links     = $link;
        $this->nestedSet = [];
        $this->level     = 0;

        $this->traverse(0);
    }

    public function traverse($id)
    {
        $lft = $this->count;
        if ($id != 0) {
            $this->count++;
        }

        $kid = $this->getChildren($id);
        if ($kid) {
            $this->level++;
            foreach ($kid as $c) {
                $this->traverse($c);
            }
            $this->level--;
        }
        $rgt = $this->count;
        $this->count++;

        if ($id != 0) {
            $this->nestedSet[$id] = ['left' => $lft, 'right' => $rgt, 'level' => $this->level, 'parentId' => $this->parentIds[$id]];
        }
    }

    public function getChildren($id)
    {
        if (array_key_exists($id, $this->links)) {
            return $this->links[$id];
        } else {
            return false;
        }
    }

    public function getNestedSet()
    {
        asort($this->nestedSet);

        return $this->nestedSet;
    }
}
