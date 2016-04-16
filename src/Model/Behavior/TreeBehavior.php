<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 5/25/15
 * Time: 3:51 PM
 */

namespace Tree\Model\Behavior;

use Cake\ORM\Behavior\TreeBehavior as BaseTreeBehavior;

class TreeBehavior extends BaseTreeBehavior
{
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
