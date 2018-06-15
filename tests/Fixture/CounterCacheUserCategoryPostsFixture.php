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
 * @since         3.1.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CounterCache test fixture for testing binding keys.
 */
class CounterCacheUserCategoryPostsFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'integer'],
        'category_id' => ['type' => 'integer'],
        'user_id' => ['type' => 'integer'],
        'post_count' => ['type' => 'integer', 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    public $records = [
        ['category_id' => 1, 'user_id' => 1, 'post_count' => 1],
        ['category_id' => 2, 'user_id' => 1, 'post_count' => 1],
        ['category_id' => 2, 'user_id' => 2, 'post_count' => 1],
    ];
}
