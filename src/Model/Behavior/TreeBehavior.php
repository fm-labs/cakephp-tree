<?php

namespace Tree\Model\Behavior;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior\TreeBehavior as BaseTreeBehavior;
use Cake\ORM\Table;

class TreeBehavior extends BaseTreeBehavior
{

    public function __construct(Table $table, array $config = [])
    {
        $this->_defaultConfig['implementedMethods']['moveAfter'] = 'moveAfter';
        $this->_defaultConfig['implementedMethods']['moveBefore'] = 'moveBefore';
        $this->_defaultConfig['implementedMethods']['moveTo'] = 'moveTo';
        parent::__construct($table, $config);
    }

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function moveTo(EntityInterface $node, $newParentId, $newPos, $oldPos)
    {
        if ($node->id === $newParentId) {
            throw new \LogicException('Can not move tree node into itself');
        }

        $oldParentId = $node->parent_id;

        if ($newParentId != $oldParentId) {
            $node = $this->_updateParentId($node, $newParentId);
            $childCount = $this->_getNodeCount($newParentId);
            $oldPos = ($childCount - 1);
        }

        $delta = $newPos - $oldPos;
        //debug(sprintf("TreeBehavior: Moving  [%s] node from parent %d -> %d | pos %d -> %d | delta: %d",
        //    $this->_table->alias(), $oldParentId, $newParentId, $oldPos, $newPos, $delta));

        if ($delta > 0) {
            //debug('Moving up ' . $delta);
            $node = $this->moveDown($node, $delta);
        } elseif ($delta < 0) {
            $delta = abs($delta) + 1;
            //debug('Moving up ' . $delta);
            $node = $this->moveUp($node, $delta);
        }

        return $node;
    }

    protected function _updateParentId($node, $parentId)
    {

        $node->set('parent_id', $parentId);
        $node = $this->_table->patchEntity($node, ['parent_id' => $parentId]);
        //debug(sprintf("TreeBehavior: [%s] Update parent for node %d: %d -> %d",
        //    $this->_table->alias(), $node->id, $node->parent_id, $parentId));

        $node = $this->_table->save($node);
        if (!$node) {
            //@TODO Logging
            throw new \RuntimeException("TreeBehavior: Failed to update parent ID");
        }

        return $node;
    }

    protected function _getNodeCount($parentId)
    {
        if ($parentId) {
            $childCount = $this->_scope($this->_table->find('all'))
                ->where(['parent_id' => $parentId])
                ->count();
        } else {
            $childCount = $this->_scope($this->_table->find('all'))
                ->where(['parent_id IS' => null])
                ->count();
        }

        return $childCount;
    }

    public function moveAfter(EntityInterface $node, $after)
    {
        if ($after < 1) {
            return false;
        }

        $targetNode = $this->_table->get($after);

        if ($node->parent_id !== $targetNode->parent_id) {
            $node = $this->_updateParentId($node, $targetNode->parent_id);
        }

        list($nodeLeft, $nodeRight) = array_values($node->extract(['lft', 'rght']));
        list($targetLeft, $targetRight) = array_values($targetNode->extract(['lft', 'rght']));
        //debug('NodeLeft: ' . $nodeLeft . ' | NodeRight: ' . $nodeRight);
        //debug('TargetLeft: ' . $targetLeft . ' | TargetRight: ' . $targetRight);


        $offset = $nodeLeft - $targetLeft;
        if ($offset % 2 !== 0) {
            //throw new \RuntimeException('Malformed tree');
        }
        $delta = $offset / 2;

        if ($delta > 0) {
            $delta -= 1;
            //debug('Moving up ' . $delta);
            $node = $this->moveUp($node, $delta);
        } else {
            $delta = abs($delta);
            //debug('Moving down ' . $delta);
            $node = $this->moveDown($node, $delta);
        }

        return $node;
    }

    public function moveBefore(EntityInterface $node, $before)
    {
        if ($before < 1) {
            return false;
        }

        $targetNode = $this->_table->get($before);

        if ($node->parent_id !== $targetNode->parent_id) {
            $node = $this->_updateParentId($node, $targetNode->parent_id);
        }

        list($nodeLeft, $nodeRight) = array_values($node->extract(['lft', 'rght']));
        list($targetLeft, $targetRight) = array_values($targetNode->extract(['lft', 'rght']));
        //debug('NodeLeft: ' . $nodeLeft . ' | NodeRight: ' . $nodeRight);
        //debug('TargetLeft: ' . $targetLeft . ' | TargetRight: ' . $targetRight);


        $offset = $nodeLeft - $targetLeft;
        if ($offset % 2 !== 0) {
            //throw new \RuntimeException('Malformed tree');
        }
        $delta = $offset / 2;

        if ($delta > 0) {
            //debug('Moving up ' . $delta);
            $node = $this->moveUp($node, $delta);
        } else {
            $delta = abs($delta) - 1;
            //debug('Moving down ' . $delta);
            $node = $this->moveDown($node, $delta);
        }

        return $node;
    }

    /**
     * Fixed version for CakePHP 3.1.x branch:
     *  Orders by right field instead of left field.
     * This got fixed in the 3.2.x branch
     *
     * Returns the maximum index value in the table.
     *
     * @return int
     */
    protected function _getMax()
    {
        //@TODO Do a version compare on CakePHP version. Call parent if version >= 3.2.*

        $field = $this->_config['right'];
        $edge = $this->_scope($this->_table->find())
            ->select([$field])
            ->orderDesc($field)
            ->first();

        if (empty($edge->{$field})) {
            return 0;
        }

        return $edge->{$field};
    }
}
