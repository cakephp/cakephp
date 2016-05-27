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
 * @since         3.2.11
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SiteCategoriesFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'parent_id' => ['type' => 'integer', 'null' => true],
        'site_id' => ['type' => 'integer', 'null' => false],
        'name' => ['type' => 'string', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['site_id', 'id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'parent_id' => null,
            'site_id' => 1,
            'name' => 'Languages',
        ],
        [
            'id' => 2,
            'parent_id' => 1,
            'site_id' => 1,
            'name' => 'PHP',
        ],
        [
            'id' => 3,
            'parent_id' => 1,
            'site_id' => 1,
            'name' => 'SQL',
        ],
        [
            'id' => 5,
            'parent_id' => null,
            'site_id' => 1,
            'name' => 'Tools',
        ],
        [
            'id' => 6,
            'parent_id' => 5,
            'site_id' => 1,
            'name' => 'SSH',
        ],
        [
            'id' => 7,
            'parent_id' => 5,
            'site_id' => 1,
            'name' => 'GIT',
        ],
    ];
}
