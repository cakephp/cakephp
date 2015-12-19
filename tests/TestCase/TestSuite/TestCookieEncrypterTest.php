<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestCookieEncrypter;

/**
 * Cookie encrypter test case
 */
class TestCookieEncrypterTest extends TestCase
{
    public function testEncryptedAsSameSpecAsCookieComponent()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|TestCookieEncrypter $TestCookieEncrypter
         */
        $TestCookieEncrypter = $this->getMock('Cake\\TestSuite\\TestCookieEncrypter', ['_encrypt']);
        $TestCookieEncrypter->expects($this->once())
            ->method('_encrypt')
            ->with('Arg1', 'Arg2')
            ->will($this->returnValue('ReturnValue'));

        $actual = $TestCookieEncrypter->encrypt('Arg1', 'Arg2');
        $this->assertEquals('ReturnValue', $actual);
    }
}
