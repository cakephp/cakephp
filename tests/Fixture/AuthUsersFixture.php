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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 */
class AuthUsersFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'username' => ['type' => 'string', 'null' => false],
        'password' => ['type' => 'string', 'null' => false],
        'created' => 'datetime',
        'updated' => 'datetime',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['username' => 'mariano', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'],
        ['username' => 'larry', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'],
        ['username' => 'chartjes', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'],
        ['username' => 'garrett', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'],
        ['username' => 'nate', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'],
    ];
}
