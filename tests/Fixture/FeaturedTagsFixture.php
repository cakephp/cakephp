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
 * @since         3.0.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FeaturedTagsFixture
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
