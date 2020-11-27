<?php
declare(strict_types=1);

namespace Tree\Test\TestCase\Model\Behavior;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use DebugKit\Database\Log\DebugLog;

/**
 * Class SimpleTreeBehaviorTest
 *
 * @package Tree\Test\Model\Behavior
 */
class SimpleTreeBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.Tree.SortedPosts',
    ];

    /**
     * @var DebugLog
     */
    public $dbLogger;

    /**
     * @var PostsTable
     */
    public $table;

    public function setUp(): void
    {
        parent::setUp();
        $this->table = TableRegistry::getTableLocator()->get('Tree.SortedPosts');
        $this->table->setPrimaryKey(['id']);
        if ($this->table->behaviors()->has('SimpleTree')) {
            $this->table->behaviors()->unload('SimpleTree');
        }
        $this->table->addBehavior('Tree.SimpleTree', ['scope' => []]);

        //$this->_setupDbLogging();
    }

    protected function _setupDbLogging()
    {

        $connection = ConnectionManager::get('test');

        $logger = $connection->getLogger();
        $this->dbLogger = new DebugLog($logger, 'test');

        $connection->enableQueryLogging(true);
        $connection->setLogger($this->dbLogger);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        TableRegistry::getTableLocator()->clear();
    }

    public function testValues()
    {
        $this->assertPositions([
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
        ]);
    }

    public function testFindSorted()
    {
        $sorted = $this->table->find('sorted')->select(['id', 'title', 'pos'])->enableHydration(false)->all();
        //debug($sorted->toArray());
        $this->markTestIncomplete();
    }

    public function testFindSortedReverse()
    {
        $sorted = $this->table->find('sorted')->select(['id', 'title', 'pos'])->enableHydration(false)->all();
        //debug($sorted->toArray());
        $this->markTestIncomplete();
    }

    public function testFindSortedList()
    {
        $sorted = $this->table
            ->find('list')
            ->find('sorted');

        $this->assertEquals([1, 2, 3, 4], array_keys($sorted->toArray()));
    }

    public function testFindSortedListReverse()
    {
        $sorted = $this->table
            ->find('list')
            ->find('sorted', ['reverse' => true]);

        $this->assertEquals([4, 3, 2, 1], array_keys($sorted->toArray()));
    }

    public function testMoveUp()
    {
        $entity = $this->table->moveUp($this->table->get(3));
        $this->assertEquals(2, $entity->pos);
        $this->assertPositions([
            1 => 1,
            2 => 3,
            3 => 2,
            4 => 4,
        ]);

        $entity = $this->table->moveUp($this->table->get(1));
        $this->assertEquals(1, $entity->pos);
        $this->assertPositions([
            1 => 1,
            2 => 3,
            3 => 2,
            4 => 4,
        ]);
    }

    public function testMoveDown()
    {
        $entity = $this->table->moveDown($this->table->get(2));
        $this->assertEquals(3, $entity->pos);
        $this->assertPositions([
            1 => 1,
            2 => 3,
            3 => 2,
            4 => 4,
        ]);

        $entity = $this->table->moveDown($this->table->get(4));
        $this->assertEquals(4, $entity->pos);
        $this->assertPositions([
            1 => 1,
            2 => 3,
            3 => 2,
            4 => 4,
        ]);
    }

    public function testMoveTop()
    {
        $entity = $this->table->moveTop($this->table->get(3));
        //debug($this->dbLogger->queries());
        $this->assertEquals(1, $entity->pos);
        $this->assertPositions([
            1 => 2,
            2 => 3,
            3 => 1,
            4 => 4,
        ]);
    }

    public function testMoveTopNodeToTop()
    {
        $entity = $this->table->moveTop($this->table->get(1));
        //debug($this->dbLogger->queries());
        $this->assertEquals(1, $entity->pos);
    }

    public function testMoveBottomNodeToTop()
    {
        $entity = $this->table->moveTop($this->table->get(4));
        //debug($this->dbLogger->queries());
        $this->assertEquals(1, $entity->pos);
        $this->assertPositions([
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 1,
        ]);
    }

    public function testMoveBottom()
    {
        $entity = $this->table->moveBottom($this->table->get(2));
        //debug($this->dbLogger->queries());
        $this->assertEquals(4, $entity->pos);
        $this->assertPositions([
            1 => 1,
            2 => 4,
            3 => 2,
            4 => 3,
        ]);
    }

    public function testMoveAfter()
    {
        // move after lower node
        $entity = $this->table->moveAfter($this->table->get(2), 3);
        $this->assertEquals(3, $entity->pos);
        $this->assertPositions([
            1 => 1,
            3 => 2,
            2 => 3,
            4 => 4,
        ]);

        // move after higher node
        $entity = $this->table->moveAfter($this->table->get(4), 1);
        $this->assertEquals(2, $entity->pos);
        $this->assertPositions([
            1 => 1,
            4 => 2,
            3 => 3,
            2 => 4,
        ]);
    }

    public function testMoveAfterLast()
    {
        $entity = $this->table->moveAfter($this->table->get(2), 4);
        //debug($this->dbLogger->queries());
        $this->assertEquals(4, $entity->pos);
        $this->assertPositions([
            1 => 1,
            3 => 2,
            4 => 3,
            2 => 4,
        ]);
    }

    public function testMoveFirstAfterLast()
    {
        $entity = $this->table->moveAfter($this->table->get(1), 4);
        //debug($this->dbLogger->queries());
        $this->assertEquals(4, $entity->pos);
        $this->assertPositions([
            1 => 4,
            2 => 1,
            3 => 2,
            4 => 3,
        ]);
    }

    public function testMoveAfterOutOfBounds()
    {
        $entity = $this->table->moveAfter($this->table->get(1), 4);
        //debug($this->dbLogger->queries());
        $this->assertEquals(4, $entity->pos);
        $this->assertPositions([
            1 => 4,
            2 => 1,
            3 => 2,
            4 => 3,
        ]);
    }

    public function testMoveAfterSelf()
    {
        $entity = $this->table->moveAfter($this->table->get(1), 1);
        //debug($this->dbLogger->queries());
        $this->assertEquals(1, $entity->pos);
    }

    public function testMoveBefore()
    {
        // move before higher node
        $entity = $this->table->moveBefore($this->table->get(4), 2);
        $this->assertEquals(2, $entity->pos);
        $this->assertPositions([
            1 => 1,
            4 => 2,
            2 => 3,
            3 => 4,
        ]);

        // move before lower node
        $entity = $this->table->moveBefore($this->table->get(1), 3);
        $this->assertEquals(3, $entity->pos);
        $this->assertPositions([
            4 => 1,
            2 => 2,
            1 => 3,
            3 => 4,
        ]);
    }

    public function testMoveBeforeFirst()
    {
        $entity = $this->table->moveBefore($this->table->get(4), 1);
        //debug($this->dbLogger->queries());
        $this->assertEquals(1, $entity->pos);
        $this->assertPositions([
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 1,
        ]);
    }

    public function testMoveBeforeLast()
    {
        $entity = $this->table->moveBefore($this->table->get(1), 4);
        //debug($this->dbLogger->queries());
        $this->assertEquals(3, $entity->pos);
        $this->assertPositions([
            1 => 3,
            2 => 1,
            3 => 2,
            4 => 4,
        ]);
    }

    public function testNewRecord()
    {
        $new = $this->table->save($this->table->newEntity(['refscope' => 'Test', 'refid' => 5, 'title' => 'Test', 'is_published' => true]));
        $this->assertEquals(5, $new->pos);
        $this->assertPositions([
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
        ]);
    }

    public function testDeleteRecord()
    {
        $this->table->delete($this->table->get(3));
        $this->assertPositions([
            1 => 1,
            2 => 2,
            4 => 3,
        ]);
    }

    /********* S C O P E D    B E H A V I O R    T E S T S ****************************/

    public function setupScoped()
    {
        $this->loadScopedBehavior();
        $this->loadScopedRecords();
    }

    protected function loadScopedBehavior()
    {
        if ($this->table->behaviors()->has('SimpleTree')) {
            $this->table->behaviors()->unload('SimpleTree');
        }
        $this->table->addBehavior('Tree.SimpleTree', ['scope' => ['refscope', 'refid']]);
    }

    protected function loadScopedRecords()
    {
        return $this->table->getConnection()->transactional(function () {
            $this->table->deleteAll([1 => 1]);
            $this->table->save($this->table->newEntity(['refscope' => 'TestScope', 'refid' => 99, 'pos' => 1, 'title' => 'Test Scoped 2', 'is_published' => true]));
            $this->table->save($this->table->newEntity(['refscope' => 'TestScope', 'refid' => 99, 'pos' => 2, 'title' => 'Test Scoped 2', 'is_published' => true]));
            $this->table->save($this->table->newEntity(['refscope' => 'TestScope', 'refid' => 99, 'pos' => 3, 'title' => 'Test Scoped 3', 'is_published' => true]));
            $this->table->save($this->table->newEntity(['refscope' => 'TestScope', 'refid' => 99, 'pos' => 4, 'title' => 'Test Scoped 4', 'is_published' => true]));
            $this->table->save($this->table->newEntity(['refscope' => 'TestScope', 'refid' => 111, 'pos' => 1, 'title' => 'Test Scoped Alt 1', 'is_published' => true]));
            $this->table->save($this->table->newEntity(['refscope' => 'TestScope', 'refid' => 111, 'pos' => 2, 'title' => 'Test Scoped Alt 2', 'is_published' => true]));
            $this->table->save($this->table->newEntity(['refscope' => 'TestScope', 'refid' => 111, 'pos' => 3, 'title' => 'Test Scoped Alt 3', 'is_published' => true]));
        });
    }

    /**
     * @group scoped
     */
    public function testFindSortedScoped()
    {
        $this->setupScoped();
        $sorted = $this->table->find('sorted')->select(['id', 'title', 'pos'])->enableHydration(false)->all();
        //debug($sorted->toArray());

        $this->markTestIncomplete();
    }

    /**
     * @group scoped
     */
    public function testScopedValues()
    {
        $this->setupScoped();
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            9 => 1,
            10 => 2,
            11 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedMoveUp()
    {
        $this->setupScoped();

        $entity = $this->table->moveUp($this->table->get(10));
        $this->assertEquals(1, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);

        $entity = $this->table->moveUp($this->table->get(10));
        $this->assertEquals(1, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);

        $entity = $this->table->moveUp($this->table->get(7));
        $this->assertEquals(2, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            7 => 2,
            6 => 3,
            8 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedMoveDown()
    {
        $this->setupScoped();
        $entity = $this->table->moveDown($this->table->get(9));
        $this->assertEquals(2, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);

        $entity = $this->table->moveDown($this->table->get(8));
        $this->assertEquals(4, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);

        $entity = $this->table->moveDown($this->table->get(6));
        $this->assertEquals(3, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            7 => 2,
            6 => 3,
            8 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedMoveTop()
    {
        $this->setupScoped();
        $entity = $this->table->moveTop($this->table->get(11));
        $this->assertEquals(1, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            11 => 1,
            9 => 2,
            10 => 3,
        ]);

        $entity = $this->table->moveTop($this->table->get(8));
        $this->assertEquals(1, $entity->pos);
        $this->assertScopedPositions([
            8 => 1,
            5 => 2,
            6 => 3,
            7 => 4,
            11 => 1,
            9 => 2,
            10 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedMoveBottom()
    {
        $this->setupScoped();
        $entity = $this->table->moveBottom($this->table->get(9));
        $this->assertEquals(3, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            10 => 1,
            11 => 2,
            9 => 3,
        ]);

        $entity = $this->table->moveBottom($this->table->get(6));
        $this->assertEquals(4, $entity->pos);
        $this->assertScopedPositions([
            5 => 1,
            7 => 2,
            8 => 3,
            6 => 4,
            10 => 1,
            11 => 2,
            9 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedMoveAfter()
    {
        // move after lower node
        $this->setupScoped();
        $entity = $this->table->moveAfter($this->table->get(5), 8);
        $this->assertEquals(4, $entity->pos);
        $this->assertScopedPositions([
            6 => 1,
            7 => 2,
            8 => 3,
            5 => 4,
            9 => 1,
            10 => 2,
            11 => 3,
        ]);

        // move after higher node
        $entity = $this->table->moveAfter($this->table->get(5), 6);
        $this->assertEquals(2, $entity->pos);
        $this->assertScopedPositions([
            6 => 1,
            5 => 2,
            7 => 3,
            8 => 4,
            9 => 1,
            10 => 2,
            11 => 3,
        ]);

        // moving after node with different scope -> fail
        $entity = $this->table->moveAfter($this->table->get(5), 10);
        $this->assertFalse($entity);
        $this->assertScopedPositions([
            6 => 1,
            5 => 2,
            7 => 3,
            8 => 4,
            9 => 1,
            10 => 2,
            11 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedMoveBefore()
    {
        // move before higher node
        $this->setupScoped();
        $entity = $this->table->moveBefore($this->table->get(8), 5);
        $this->assertEquals(1, $entity->pos);
        $this->assertScopedPositions([
            8 => 1,
            5 => 2,
            6 => 3,
            7 => 4,
            9 => 1,
            10 => 2,
            11 => 3,
        ]);

        // move before lower node
        $entity = $this->table->moveBefore($this->table->get(9), 11);
        $this->assertEquals(2, $entity->pos);
        $this->assertScopedPositions([
            8 => 1,
            5 => 2,
            6 => 3,
            7 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);

        // moving before node with different scope -> fail
        $entity = $this->table->moveBefore($this->table->get(8), 10);
        $this->assertFalse($entity);
        $this->assertScopedPositions([
            8 => 1,
            5 => 2,
            6 => 3,
            7 => 4,
            10 => 1,
            9 => 2,
            11 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedNewRecord()
    {
        $this->setupScoped();
        $new = $this->table->save($this->table->newEntity([
            'refscope' => 'TestScope',
            'refid' => 99,
            'title' => 'Test Scoped New Record',
            'is_published' => true,
        ]));
        $this->assertEquals(5, $new->pos);
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            7 => 3,
            8 => 4,
            12 => 5,
            9 => 1,
            10 => 2,
            11 => 3,
        ]);
    }

    /**
     * @group scoped
     */
    public function testScopedDeleteRecord()
    {
        $this->setupScoped();
        $this->table->delete($this->table->get(7));
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            8 => 3,
            9 => 1,
            10 => 2,
            11 => 3,
        ]);

        $this->table->delete($this->table->get(10));
        $this->assertScopedPositions([
            5 => 1,
            6 => 2,
            8 => 3,
            9 => 1,
            11 => 2,
        ]);
    }

    /**
     * Assert the sort order position
     *
     * @param array $expected [ id => expectedPos , ... ]
     */
    protected function assertPositions($expected = [])
    {
        $posList = $this->table->find('list', ['keyField' => 'id' , 'valueField' => 'pos'])->toArray();
        $this->assertEquals($expected, $posList);
    }

    /**
     * Assert the sort order position
     *
     * @param array $expected [ id => expectedPos , ... ]
     */
    protected function assertScopedPositions($expected = [])
    {
        $posList = $this->table->find('list', ['keyField' => 'id' , 'valueField' => 'pos'])->toArray();
        $this->assertEquals($expected, $posList);
    }
}
