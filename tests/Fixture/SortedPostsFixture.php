<?php
namespace Tree\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PostsFixture
 *
 */
class SortedPostsFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    //public $table = 'posts';

    /**
     * Fields
     *
     * @var array
     */
    // phpcs::disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'title' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'refscope' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'refid' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'is_published' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null],
        'pos' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // phpcs::enable

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'refscope' => null,
            'refid' => null,
            'title' => 'Lorem ipsum dolor sit amet',
            'pos' => 1,
        ],
        [
            'id' => 2,
            'refscope' => null,
            'refid' => null,
            'title' => 'Lorem ipsum dolor sit amet',
            'pos' => 2,
        ],
        [
            'id' => 3,
            'refscope' => null,
            'refid' => null,
            'title' => 'Lorem ipsum dolor sit amet',
            'pos' => 3,
        ],
        [
            'id' => 4,
            'refscope' => null,
            'refid' => null,
            'title' => 'Lorem ipsum dolor sit amet',
            'pos' => 4,
        ],
    ];
}
