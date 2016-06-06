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
namespace Cake\Test\TestCase\Validation;

use Cake\TestSuite\TestCase;
use Cake\Validation\RulesProvider;

/**
 * Tests RulesProvider class
 *
 */
class RulesProviderTest extends TestCase
{

    /**
     * Tests that RulesProvider proxies the method correctly and removes the
     * extra arguments that are passed according to the signature of validation
     * methods.
     *
     * @return void
     */
    public function testProxyToValidation()
    {
        $provider = new RulesProvider;
        $this->assertTrue($provider->extension('foo.jpg', compact('provider')));
        $this->assertFalse($provider->extension('foo.jpg', ['png'], compact('provider')));
    }

    /**
     * Tests that it is possible to use a custom object as the provider to
     * be decorated
     *
     * @return void
     */
    public function testCustomObject()
    {
        $mock = $this->getMockBuilder('\Cake\Validation\Validator')
            ->setMethods(['field'])
            ->getMock();
        $mock->expects($this->once())
            ->method('field')
            ->with('first', null)
            ->will($this->returnValue(true));

        $provider = new RulesProvider($mock);
        $provider->field('first', compact('provider'));
    }
}
