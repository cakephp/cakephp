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
 * @since         0.0.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TestPluginCommentFixture
 */
class TestPluginCommentsFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'article_id' => ['type' => 'integer', 'null' => false],
        'user_id' => ['type' => 'integer', 'null' => false],
        'comment' => 'text',
        'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
        'created' => 'datetime',
        'updated' => 'datetime',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:45:23', 'updated' => '2008-09-24 10:47:31'],
        ['article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:47:23', 'updated' => '2008-09-24 10:49:31'],
        ['article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:49:23', 'updated' => '2008-09-24 10:51:31'],
        ['article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Plugin Article', 'published' => 'N', 'created' => '2008-09-24 10:51:23', 'updated' => '2008-09-24 10:53:31'],
        ['article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:53:23', 'updated' => '2008-09-24 10:55:31'],
        ['article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:55:23', 'updated' => '2008-09-24 10:57:31']
    ];
}
