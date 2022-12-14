<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SiteArticlesFixture extends TestFixture
{
    /**
     * records property
     *
     * @var array
     */
    public array $records = [
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
        ],
    ];
}
