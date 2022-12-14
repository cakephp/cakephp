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
 * PostFixture
 */
class PostsFixture extends TestFixture
{
    /**
     * records property
     *
     * @var array
     */
    public array $records = [
        ['author_id' => 1, 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y'],
        ['author_id' => 3, 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y'],
        ['author_id' => 1, 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y'],
    ];
}
