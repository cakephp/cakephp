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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProfileFixture
 */
class ProfilesFixture extends TestFixture
{
    /**
     * records property
     */
    public array $records = [
        ['user_id' => 1, 'first_name' => 'mariano', 'last_name' => 'iglesias', 'is_active' => false],
        ['user_id' => 2, 'first_name' => 'nate', 'last_name' => 'abele', 'is_active' => false],
        ['user_id' => 3, 'first_name' => 'larry', 'last_name' => 'masters', 'is_active' => true],
        ['user_id' => 4, 'first_name' => 'garrett', 'last_name' => 'woodworth', 'is_active' => false],
    ];
}
