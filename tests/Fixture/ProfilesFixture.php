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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProfileFixture
 */
class ProfilesFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'autoIncrement' => true],
        'user_id' => ['type' => 'integer', 'null' => false],
        'first_name' => ['type' => 'string', 'null' => true],
        'last_name' => ['type' => 'string', 'null' => true],
        'is_active' => ['type' => 'boolean', 'null' => false, 'default' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['user_id' => 1, 'first_name' => 'mariano', 'last_name' => 'iglesias', 'is_active' => false],
        ['user_id' => 2, 'first_name' => 'nate', 'last_name' => 'abele', 'is_active' => false],
        ['user_id' => 3, 'first_name' => 'larry', 'last_name' => 'masters', 'is_active' => true],
        ['user_id' => 4, 'first_name' => 'garrett', 'last_name' => 'woodworth', 'is_active' => false],
    ];
}
