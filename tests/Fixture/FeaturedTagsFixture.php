<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/3.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/3.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.0.7
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class FeaturedTagsFixture
 *
 */
class FeaturedTagsFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'tag_id' => ['type' => 'integer', 'null' => false],
        'priority' => ['type' => 'integer', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['tag_id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['priority' => 1],
        ['priority' => 2],
        ['priority' => 3]
    ];
}
