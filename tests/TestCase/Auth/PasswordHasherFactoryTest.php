<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\PasswordHasherFactory;
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
    public function testBuild(): void
    {
        $hasher = PasswordHasherFactory::build('Default');
        $this->assertInstanceof('Cake\Auth\DefaultPasswordHasher', $hasher);

        $hasher = PasswordHasherFactory::build([
            'className' => 'Default',
            'hashOptions' => ['foo' => 'bar'],
        ]);
        $this->assertInstanceof('Cake\Auth\DefaultPasswordHasher', $hasher);
        $this->assertEquals(['foo' => 'bar'], $hasher->getConfig('hashOptions'));

        $this->loadPlugins(['TestPlugin']);
        $hasher = PasswordHasherFactory::build('TestPlugin.Legacy');
        $this->assertInstanceof('TestPlugin\Auth\LegacyPasswordHasher', $hasher);
        $this->clearPlugins();
    }

    /**
     * test build() throws exception for nonexistent hasher
     *
     * @return void
     */
    public function testBuildException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Password hasher class "FooBar" was not found.');
        $hasher = PasswordHasherFactory::build('FooBar');
    }
}
