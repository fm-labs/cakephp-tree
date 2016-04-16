<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 4/16/16
 * Time: 4:56 PM
 */

namespace Tree\Test\Model\Behavior;


use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use DebugKit\Database\Log\DebugLog;

/**
 * @property \Cake\ORM\Table table
 */
class TreeBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.tree.number_trees'
    ];

    /**
     * @var DebugLog
     */
    public $dbLogger;

    public function setUp()
    {
        parent::setUp();
        $this->table = TableRegistry::get('Tree.NumberTrees');
        $this->table->primaryKey(['id']);
        $this->table->addBehavior('Tree.Tree');

        $this->_setupDbLogging();
    }


    /**
     * Initialize hook - configures logger.
     *
     * This will unfortunately build all the connections, but they
     * won't connect until used.
     *
     * @return array
     */
    protected function _setupDbLogging()
    {

        $connection = ConnectionManager::get('test');

        $logger = $connection->logger();
        $this->dbLogger = new DebugLog($logger, 'test');

        $connection->logQueries(true);
        $connection->logger($this->dbLogger);
    }

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Sanity test
     *
     * Make sure the assert method acts as you'd expect, this is the expected
     * initial db state
     *
     * @return void
     */
    public function testAssertMpttValues()
    {
        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    public function testMove()
    {

        // move up second root node
        $this->table->moveUp($this->table->get(11, ['cache' => false]), 1);
        $expected = [
            //' -1: 0 - xxx',
            ' 1: 2 - 11:alien hardware', // -> 21:22
            ' 3:22 -  1:electronics', // -2: [21-1]
            '_ 4:11 -  2:televisions',
            '__ 5: 6 -  3:tube',
            '__ 7: 8 -  4:lcd',
            '__ 9:10 -  5:plasma',
            '_12:21 -  6:portable',
            '__13:16 -  7:mp3',
            '___14:15 -  8:flash',
            '__17:18 -  9:cd',
            '__19:20 - 10:radios',
        ];
        $this->assertMpttValues($expected, $this->table);

        // move root node to bottom
        $node = $this->table->moveDown($this->table->get(11, ['cache' => false]), 1);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '21:22 - 11:alien hardware'
        ];
        //debug($this->table->find('treeList')->toArray());
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Assert MPTT values
     *
     * Custom assert method to make identifying the differences between expected
     * and actual db state easier to identify.
     *
     * @param array $expected tree state to be expected
     * @param \Cake\ORM\Table $table Table instance
     * @param \Cake\ORM\Query $query Optional query object
     * @return void
     */
    public function assertMpttValues($expected, $table, $query = null)
    {
        $query = $query ?: $table->find();
        $primaryKey = $table->primaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = $primaryKey[0];
        }
        $displayField = $table->displayField();
        $options = [
            'valuePath' => function ($item, $key, $iterator) use ($primaryKey, $displayField) {
                return sprintf(
                    '%s:%s - %s:%s',
                    str_pad($item->lft, 2, ' ', STR_PAD_LEFT),
                    str_pad($item->rght, 2, ' ', STR_PAD_LEFT),
                    str_pad($item->$primaryKey, 2, ' ', STR_PAD_LEFT),
                    $item->{$displayField}
                );
            }
        ];
        $result = array_values($query->find('treeList', $options)->toArray());
        if (count($result) === count($expected)) {
            $subExpected = array_diff($expected, $result);
            if ($subExpected) {
                $subResult = array_intersect_key($result, $subExpected);
                $this->assertSame($subExpected, $subResult, 'Differences in the tree were found (lft:rght id:display-name)');
            }
        }
        $this->assertSame($expected, $result, 'The tree is not the same (lft:rght id:display-name)');
    }

}