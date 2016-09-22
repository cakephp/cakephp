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
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\PasswordHasherFactory;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Test case for PasswordHasherFactory
 */
class PasswordHasherFactoryTest extends TestCase
{

    /**
     * test passwordhasher instance building
     *
     * @return void
     */
    public function testBuild()
    {
        $hasher = PasswordHasherFactory::build('Default');
        $this->assertInstanceof('Cake\Auth\DefaultPasswordHasher', $hasher);

        $hasher = PasswordHasherFactory::build([
            'className' => 'Default',
            'hashOptions' => ['foo' => 'bar']
        ]);
        $this->assertInstanceof('Cake\Auth\DefaultPasswordHasher', $hasher);
        $this->assertEquals(['foo' => 'bar'], $hasher->config('hashOptions'));

        Plugin::load('TestPlugin');
        $hasher = PasswordHasherFactory::build('TestPlugin.Legacy');
        $this->assertInstanceof('TestPlugin\Auth\LegacyPasswordHasher', $hasher);
    }

    /**
     * test build() throws exception for non existent hasher
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Password hasher class "FooBar" was not found.
     * @return void
     */
    public function testBuildException()
    {
        $hasher = PasswordHasherFactory::build('FooBar');
    }
}
