<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class NumberTreeFixture
 *
 * Generates a tree of data for use testing the tree behavior
 */
class NumberTreesFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'null' => false],
        'parent_id' => 'integer',
        'lft' => ['type' => 'integer'],
        'rght' => ['type' => 'integer'],
        'depth' => ['type' => 'integer'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * Records
     *
     *  - electronics:1
     *      - televisions:2
     *          - tube:3
     *          - lcd:4
     *          - plasma:5
     *      - portable:6
     *          - mp3:7
     *              - flash:8
     *          - cd:9
     *          - radios:10
     *  - alien ware: 11
     *
     * @var array
     */
    public $records = [
        [
            'name' => 'electronics',
            'parent_id' => null,
            'lft' => '1',
            'rght' => '20',
            'depth' => 0
        ],
        [
            'name' => 'televisions',
            'parent_id' => '1',
            'lft' => '2',
            'rght' => '9',
            'depth' => 1
        ],
        [
            'name' => 'tube',
            'parent_id' => '2',
            'lft' => '3',
            'rght' => '4',
            'depth' => 2
        ],
        [
            'name' => 'lcd',
            'parent_id' => '2',
            'lft' => '5',
            'rght' => '6',
            'depth' => 2
        ],
        [
            'name' => 'plasma',
            'parent_id' => '2',
            'lft' => '7',
            'rght' => '8',
            'depth' => 2
        ],
        [
            'name' => 'portable',
            'parent_id' => '1',
            'lft' => '10',
            'rght' => '19',
            'depth' => 1
        ],
        [
            'name' => 'mp3',
            'parent_id' => '6',
            'lft' => '11',
            'rght' => '14',
            'depth' => 2
        ],
        [
            'name' => 'flash',
            'parent_id' => '7',
            'lft' => '12',
            'rght' => '13',
            'depth' => 3
        ],
        [
            'name' => 'cd',
            'parent_id' => '6',
            'lft' => '15',
            'rght' => '16',
            'depth' => 2
        ],
        [
            'name' => 'radios',
            'parent_id' => '6',
            'lft' => '17',
            'rght' => '18',
            'depth' => 2
        ],
        [
            'name' => 'alien hardware',
            'parent_id' => null,
            'lft' => '21',
            'rght' => '22',
            'depth' => 0
        ]
    ];
}
