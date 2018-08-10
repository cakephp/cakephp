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
 * @since         3.3.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Fixture for testing decimal, float and bigint types
 */
class DatatypesFixture extends TestFixture
{
    /**
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'biginteger'],
        'cost' => ['type' => 'decimal', 'length' => 20, 'precision' => 0, 'null' => true],
        'floaty' => ['type' => 'float', 'null' => true],
        'small' => ['type' => 'smallinteger', 'null' => true],
        'tiny' => ['type' => 'tinyinteger', 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * @var array
     */
    public $records = [];
}
