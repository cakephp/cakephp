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

class SiteArticlesTagsFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'article_id' => ['type' => 'integer', 'null' => false],
        'tag_id' => ['type' => 'integer', 'null' => false],
        'site_id' => ['type' => 'integer', 'null' => false],
        '_constraints' => [
            'UNIQUE_TAG2' => ['type' => 'primary', 'columns' => ['article_id', 'tag_id', 'site_id']]
        ]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['article_id' => 1, 'tag_id' => 1, 'site_id' => 1],
        ['article_id' => 1, 'tag_id' => 2, 'site_id' => 2],
        ['article_id' => 2, 'tag_id' => 4, 'site_id' => 2],
        ['article_id' => 4, 'tag_id' => 1, 'site_id' => 1],
        ['article_id' => 1, 'tag_id' => 3, 'site_id' => 1]
    ];
}
