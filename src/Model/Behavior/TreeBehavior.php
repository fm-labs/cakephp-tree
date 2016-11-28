<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 5/25/15
 * Time: 3:51 PM
 */

namespace Tree\Model\Behavior;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior\TreeBehavior as BaseTreeBehavior;
use Cake\ORM\Table;

class TreeBehavior extends BaseTreeBehavior
{

    public function __construct(Table $table, array $config = [])
    {
        $this->_defaultConfig['implementedMethods']['moveAfter'] = 'moveAfter';
        $this->_defaultConfig['implementedMethods']['moveTo'] = 'moveTo';
        parent::__construct($table, $config);
    }

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function moveTo(EntityInterface $node, $newParentId, $newPos, $oldPos)
    {
        $oldParentId = $node->parent_id;
        // position change within same parent node
        if ($newParentId == $oldParentId) {
            $delta = $newPos - $oldPos;
            if ($delta > 0) {
                $menuItem = $this->moveDown($node, $delta);
            } elseif ($delta < 0) {
                $node = $this->moveUp($node, abs($delta));
            }
        }
        // Moved to different parent node
        else {
            $node->set('parent_id', $newParentId);
            $node = $this->_table->patchEntity($node, ['parent_id' => $newParentId]);
            if (!$this->_table->save($node)) {
                //debug("Error: Failed to change parent");
                //@TODO Error Handling
                return false;

            } else{

                if ($newParentId) {
                    $childCount = $this->_scope($this->_table->find('all'))
                        ->where(['parent_id' => $newParentId])
                        ->count();
                } else {
                    $childCount = $this->_scope($this->_table->find('all'))
                        ->where(['parent_id IS' => null])
                        ->count();
                }

                $delta = $newPos - ($childCount - 1);
                if ($delta > 0) {
                    $node = $this->moveDown($node, $delta);
                } elseif ($delta < 0) {
                    $node = $this->moveUp($node, abs($delta));
                }
            }
        }
        return $node;
    }

    public function moveAfter(EntityInterface $node, $after)
    {
        if ($after < 1) {
            return false;
        }

        $nodeLevel = $this->getLevel($node);
        list($nodeLeft, $nodeRight) = array_values($node->extract(['lft', 'rght']));

        $targetNode = $this->_table->get($after);
        //debug($targetNode);
        $targetLevel = $this->getLevel($targetNode);
        list($targetLeft, $targetRight) = array_values($targetNode->extract(['lft', 'rght']));

        debug('NodeLeft: ' . $nodeLeft . ' | NodeRight: ' . $nodeRight);
        debug('TargetLeft: ' . $targetLeft . ' | TargetRight: ' . $targetRight);


        //debug($node);
        if ($targetLevel !== $nodeLevel) {
            debug("level change $nodeLevel -> $targetLevel");

            if ($targetLevel === 0) {

            } else {

            }

            $this->_setParent($node, $targetLevel);
        } else {
            $shift = ($targetLeft > $nodeRight) ? $targetLeft - $nodeRight - 1 : $nodeLeft - $targetRight - 1;
            if ($targetLeft > $nodeRight) {
                debug('Moving down ' . $shift);
                $node = $this->moveDown($node, $shift);
            } else {
                debug('Moving up ' . $shift);
                $node = $this->moveUp($node, $shift);
            }
        }
        //debug($node);
        return $node;
    }

    public function moveBefore(EntityInterface $node, $before)
    {
        if ($before < 1) {
            return false;
        }
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
