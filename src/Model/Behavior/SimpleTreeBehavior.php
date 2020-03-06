<?php
namespace Tree\Model\Behavior;

use ArrayObject;
use Cake\Core\Exception\Exception;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Http\Exception\NotImplementedException;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

/**
 * Class SimpleTreeBehavior
 *
 * @package Tree\Model\Behavior
 *
 * @see http://book.cakephp.org/3.0/en/orm/behaviors.html
 */
class SimpleTreeBehavior extends Behavior
{
    /**
     * @var array
     */
    protected $_defaultConfig = [
        'implementedFinders' => [
            'sorted' => 'findSorted',
        ],
        'implementedMethods' => [
            'moveUp' => 'moveUp',
            'moveDown' => 'moveDown',
            'moveTop' => 'moveTop',
            'moveBottom' => 'moveBottom',
            'moveAfter' => 'moveAfter',
            'moveBefore' => 'moveBefore',
            'reorder' => 'reorder'
        ],
        'field' => 'pos', // the sort position field
        'scope' => [], // sorting scope
    ];

    /**
     * @param array $config Behavior config
     * @return void
     */
    public function initialize(array $config)
    {
    }

    /**
     * @param Event $event The event
     * @param Entity $entity The entity
     * @param \ArrayObject $options
     * @param $operation
     * @return void
     */
    public function beforeRules(Event $event, Entity $entity, ArrayObject $options, $operation)
    {
    }

    /**
     * @param Event $event The event
     * @param Entity $entity The entity
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity)
    {
        if ($entity->get($this->_config['field']) === null) {
            $entity->set($this->_config['field'], $this->_getMaxPos($entity) + 1);
        }
    }

    public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $conditions = $entity->extract($this->_config['scope']);
        $this->reorder($conditions, [
            'field' => $this->_config['field'],
            'order' => 'ASC'
        ]);
    }

    public function findSorted(Query $query, array $options = [])
    {
        $options += [ 'reverse' => false ];

        $scope = (array)$this->getConfig('scope');
        array_push($scope, $this->getConfig('field'));

        $dir = ($options['reverse']) ? 'desc' : 'asc';
        $order = array_combine($scope, array_fill(0, count($scope), $dir));

        $query->order($order);

        return $query;
    }

    public function moveUp(EntityInterface $node, $number = 1)
    {
        $delta = max(0, $number);

        return $this->_table->connection()->transactional(function () use ($node, $delta) {
            //$this->_ensureFields($node);
            return $this->_moveByDelta($node, $delta);
        });
    }

    public function moveDown(EntityInterface $node, $number = 1)
    {
        $delta = max(0, $number) * -1;

        return $this->_table->connection()->transactional(function () use ($node, $delta) {
            //$this->_ensureFields($node);
            return $this->_moveByDelta($node, $delta);
        });
    }

    public function moveTop(EntityInterface $node)
    {
        return $this->_table->connection()->transactional(function () use ($node) {
            //$this->_ensureFields($node);
            return $this->_moveToPosition($node, 1);
        });
    }

    public function moveBottom(EntityInterface $node)
    {
        return $this->_table->connection()->transactional(function () use ($node) {
            //$this->_ensureFields($node);
            return $this->_moveToPosition($node, $this->_getMaxPos($node));
        });
    }

    public function moveAfter(EntityInterface $node, $targetId)
    {
        if ($targetId === 0) {
            return $this->moveTop($node);
        }

        return $this->_table->connection()->transactional(function () use ($node, $targetId) {
            //$this->_ensureFields($node);

            $targetQuery = $this->_scoped($this->_table->query(), $node);
            $targetNode = $targetQuery
                ->hydrate(false)
                ->select($this->_config['field'])
                ->where([ 'id' => $targetId ])
                ->first();

            if (!$targetNode) {
                return false;
            }

            $pos = $node->get($this->_config['field']);
            $targetPos = $targetNode[$this->_config['field']];
            $newPos = ($pos > $targetPos) ? $targetPos + 1 : $targetPos;

            //debug("Move $pos AFTER $targetPos : NewPos $newPos --> Delta " . ($pos - $newPos));
            return $this->_moveToPosition($node, $newPos);
        });
    }

    public function moveBefore(EntityInterface $node, $targetId)
    {
        return $this->_table->connection()->transactional(function () use ($node, $targetId) {
            //$this->_ensureFields($node);

            $targetQuery = $this->_scoped($this->_table->query(), $node);
            $targetNode = $targetQuery
                ->hydrate(false)
                ->select($this->_config['field'])
                ->where([ 'id' => $targetId ])
                ->first();

            if (!$targetNode) {
                return false;
            }

            $pos = $node->get($this->_config['field']);
            $targetPos = $targetNode[$this->_config['field']];
            $newPos = ($pos < $targetPos) ? $targetPos - 1 : $targetPos;

            //debug("Move $pos BEFORE $targetPos : NewPos $newPos --> Delta " . ($pos - $newPos));
            return $this->_moveToPosition($node, $newPos);
        });
    }

    /**
     * Reorder
     *
     * Options:
     * - field: Order Field (Default to primary key)
     * - order: Order Direction ASC|DESC (Default: ASC)
     *
     * @param array $scope Scope conditions
     * @param array $options Order options
     * @throws \Exception
     *
     * @todo Refactor reordering with shifting instead of assigning new positions
     */
    public function reorder($scope = [], $options = [])
    {

        $primaryKey = $this->_getPrimaryKey();
        $options += ['field' => $primaryKey, 'order' => 'ASC'];

        if (count($scope) !== count($this->_config['scope'])) {
            throw new \Exception("Can not reorder table " . $this->_table->getAlias(). ": Scope count does not match");
        }

        $list = $this->_table
            ->find('list')
            ->where($scope)
            ->order([$options['field'] => $options['order']]);

        $this->_table->getConnection()->transactional(function () use ($list, $primaryKey) {

            $i = 1;
            foreach (array_keys($list->toArray()) as $id) {
                $this->_table->updateAll([$this->_config['field'] => $i++], [$primaryKey => $id]);
            }
        });

        return true;
    }

    public function reorderAll($options = [])
    {
        $selectFields = $scopeFields = $this->_config['scope'];

        //$primaryKey = $this->_getPrimaryKey();
        //array_push($selectFields, $primaryKey, $this->_config['field']);
        array_push($selectFields, $this->_config['field']);

        $done = [];

        $result = $this->_table->find()->select($selectFields)->hydrate(true)->all();
        $result->filter(function (EntityInterface $row) use ($scopeFields, $options, &$done) {

            $_scope = $row->extract($scopeFields);
            $_scopeKey = md5(serialize($_scope));

            if (isset($done[$_scopeKey])) {
                return;
            }

            $this->reorder($_scope, $options);
            $done[$_scopeKey] = true;
        });

        return true;
    }

    protected function _moveToPosition(EntityInterface $node, $newPos)
    {
        $sortField = $this->_config['field'];
        $pos = $node->get($sortField);
        $delta = $pos - $newPos;

        return $this->_moveByDelta($node, $delta);
    }

    protected function _moveByDelta(EntityInterface $node, $delta)
    {
        $sortField = $this->_config['field'];
        $pos = $node->get($sortField);

        $newPos = $pos - $delta;
        $newPos = max(1, $newPos);
        //debug("Move Pos $pos by delta $delta -> New position will be: $newPos");

        if ($delta == 0) {
            return $node;
        }

        $query = $this->_scoped($this->_table->query(), $node);
        $exp = $query->newExpr();
        $shift = 1;

        if ($delta < 0) {
            // move down
            $max = $this->_getMaxPos($node);
            $newPos = min($newPos, $max);

            $movement = clone $exp;
            $movement->add($sortField)->add("{$shift}")->setConjunction("-");

            $cond1 = clone $exp;
            $cond1->add($sortField)->add("{$pos}")->setConjunction(">");

            $cond2 = clone $exp;
            $cond2->add($sortField)->add("{$newPos}")->setConjunction("<=");
        } elseif ($delta > 0) {
            // move up
            $movement = clone $exp;
            $movement->add($sortField)->add("{$shift}")->setConjunction("+");

            $cond1 = clone $exp;
            $cond1->add($sortField)->add("{$pos}")->setConjunction("<");

            $cond2 = clone $exp;
            $cond2->add($sortField)->add("{$newPos}")->setConjunction(">=");
        }

        $where = clone $exp;
        $where->add($cond1)->add($cond2)->setConjunction("AND");

        $query->update()
            ->set($exp->eq($sortField, $movement))
            ->where($where);

        $query->execute()->closeCursor();

        $node->set($sortField, $newPos);

        return $this->_table->save($node);
    }

    protected function _getMaxPos(EntityInterface $node)
    {
        $sortField = $this->_config['field'];

        $query = $this->_scoped($this->_table->query(), $node);
        $res = $query->select([$sortField])->enableHydration(false)->orderDesc($sortField)->first();

        return $res[$sortField];
    }

    protected function _scoped(Query $query, EntityInterface $node)
    {

        $scope = $this->_config['scope'];

        if ($scope) {
            $scopeData = $node->extract($scope);
            $query->where($scopeData);
        }

        return $query;
    }

    protected function _getPrimaryKey()
    {
        $pk = $this->_table->getPrimaryKey();

        return (is_array($pk)) ? $pk[0] : $pk;
    }
}
