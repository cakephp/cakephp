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

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Counter Cache Test Fixtures
 */
class CounterCachePostsFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'length' => 255],
        'user_id' => ['type' => 'integer', 'null' => true],
        'category_id' => ['type' => 'integer', 'null' => true],
        'published' => ['type' => 'boolean', 'null' => false, 'default' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    public $records = [
        ['title' => 'Rock and Roll', 'user_id' => 1, 'category_id' => 1, 'published' => 0],
        ['title' => 'Music', 'user_id' => 1, 'category_id' => 2, 'published' => 1],
        ['title' => 'Food', 'user_id' => 2, 'category_id' => 2, 'published' => 1],
    ];
}
