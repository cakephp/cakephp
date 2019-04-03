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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\TestSuite\TestCase;

/**
 * Test cases for functions in Core\functions.php
 */
class FunctionsTest extends TestCase
{
    /**
     * Test cases for env()
     */
    public function testEnv()
    {
        $_ENV['DOES_NOT_EXIST'] = null;
        $this->assertNull(env('DOES_NOT_EXIST'));
        $this->assertEquals('default', env('DOES_NOT_EXIST', 'default'));

        $_ENV['DOES_EXIST'] = 'some value';
        $this->assertEquals('some value', env('DOES_EXIST'));
        $this->assertEquals('some value', env('DOES_EXIST', 'default'));

        $_ENV['EMPTY_VALUE'] = '';
        $this->assertEquals('', env('EMPTY_VALUE'));
        $this->assertEquals('', env('EMPTY_VALUE', 'default'));

        $_ENV['ZERO'] = '0';
        $this->assertEquals('0', env('ZERO'));
        $this->assertEquals('0', env('ZERO', '1'));
    }
}
