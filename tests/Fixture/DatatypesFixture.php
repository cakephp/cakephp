<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * @var array
     */
    public $records = [];
}
