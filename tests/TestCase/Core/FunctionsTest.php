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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
        $actual = env('DOES_NOT_EXIST');
        $this->assertNull($actual);
        $actual = env('DOES_NOT_EXIST', 'default');
        $this->assertEquals('default', $actual);
        $_ENV['DOES_EXIST'] = 'some value';
        $actual = env('DOES_EXIST');
        $this->assertEquals('some value', $actual);
        $actual = env('DOES_EXIST', 'default');
        $this->assertEquals('some value', $actual);
        $_ENV['EMPTY_VALUE'] = '';
        $actual = env('EMPTY_VALUE');
        $this->assertEquals('', $actual);
        $actuaal = env('EMPTY_VALUE', 'default');
        $this->assertEquals('', $actual);
        $_ENV['ZERO'] = '0';
        $actual = env('ZERO');
        $this->assertEquals('0', $actual);
        $actual = env('ZERO', '1');
        $this->assertEquals('0', $actual);
    }
}
