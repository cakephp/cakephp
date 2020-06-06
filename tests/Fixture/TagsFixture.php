<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\Database\Schema\TableSchema;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * TagFixture
 */
class TagsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'null' => false],
        'name' => ['type' => 'string', 'null' => false],
        'description' => ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['name' => 'tag1', 'description' => 'A big description', 'created' => '2016-01-01 00:00'],
        ['name' => 'tag2', 'description' => 'Another big description', 'created' => '2016-01-01 00:00'],
        ['name' => 'tag3', 'description' => 'Yet another one', 'created' => '2016-01-01 00:00'],
    ];
}
