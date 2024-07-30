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

class SiteArticlesTagsFixture extends TestFixture
{
    /**
     * records property
     */
    public array $records = [
        ['article_id' => 1, 'tag_id' => 1, 'site_id' => 1],
        ['article_id' => 1, 'tag_id' => 2, 'site_id' => 2],
        ['article_id' => 2, 'tag_id' => 4, 'site_id' => 2],
        ['article_id' => 4, 'tag_id' => 1, 'site_id' => 1],
        ['article_id' => 1, 'tag_id' => 3, 'site_id' => 1],
    ];
}
