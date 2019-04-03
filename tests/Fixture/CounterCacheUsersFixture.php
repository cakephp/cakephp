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
 * Short description for class.
 */
class CounterCacheUsersFixture extends TestFixture
{

    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'length' => 255, 'null' => false],
        'post_count' => ['type' => 'integer', 'null' => true],
        'comment_count' => ['type' => 'integer', 'null' => true],
        'posts_published' => ['type' => 'integer', 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    public $records = [
        ['name' => 'Alexander', 'post_count' => 2, 'comment_count' => 2, 'posts_published' => 1],
        ['name' => 'Steven', 'post_count' => 1, 'comment_count' => 1, 'posts_published' => 1],
    ];
}
