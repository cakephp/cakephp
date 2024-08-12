<?php
declare(strict_types=1);

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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\Container;
use Cake\TestSuite\TestCase;
use LogicException;
use TestApp\ServiceProvider\EmptyServiceProvider;
use TestApp\ServiceProvider\PersonServiceProvider;

/**
 * ServiceProviderTest
 */
class ServiceProviderTest extends TestCase
{
    public function testBootstrapHook(): void
    {
        $container = new Container();
        $container->addServiceProvider(new PersonServiceProvider());

        $this->assertTrue(
            $container->has('boot'),
            'Should have service defined in bootstrap.'
        );
        $this->assertSame('boot', $container->get('boot')->name);
    }

    public function testServicesHook(): void
    {
        $container = new Container();
        $container->addServiceProvider(new PersonServiceProvider());

        $this->assertTrue($container->has('sally'), 'Should have service');
        $this->assertSame('sally', $container->get('sally')->name);
    }

    public function testEmptyProvides(): void
    {
        $this->expectException(LogicException::class);

        $provider = new EmptyServiceProvider();
        $provider->provides('sally');
    }
}
