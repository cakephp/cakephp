<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SiteArticlesFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'author_id' => ['type' => 'integer', 'null' => true],
        'site_id' => ['type' => 'integer', 'null' => true],
        'title' => ['type' => 'string', 'null' => true],
        'body' => 'text',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id', 'site_id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'author_id' => 1,
            'site_id' => 1,
            'title' => 'First Article',
            'body' => 'First Article Body',
        ],
        [
            'id' => 2,
            'author_id' => 3,
            'site_id' => 2,
            'title' => 'Second Article',
            'body' => 'Second Article Body',
        ],
        [
            'id' => 3,
            'author_id' => 1,
            'site_id' => 2,
            'title' => 'Third Article',
            'body' => 'Third Article Body',
        ],
        [
            'id' => 4,
            'author_id' => 3,
            'site_id' => 1,
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
        ]
    ];
}
