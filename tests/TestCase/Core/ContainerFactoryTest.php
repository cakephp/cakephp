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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Container\Container;
use Cake\Core\Configure;
use Cake\Core\ContainerFactory;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Core\CustomContainer;

class ContainerFactoryTest extends TestCase
{
    public function testCreateDefaultsToCakeContainer(): void
    {
        $container = ContainerFactory::create();
        $this->assertInstanceOf(Container::class, $container);
    }

    public function testCreateUsesConfiguredContainerClass(): void
    {
        Configure::write('App.container', CustomContainer::class);

        $container = ContainerFactory::create();
        $this->assertInstanceOf(CustomContainer::class, $container);
    }

    public function testCreateRejectsClassNotImplementingContainerInterface(): void
    {
        Configure::write('App.container', self::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`App.container` must be a class name implementing');
        ContainerFactory::create();
    }

    public function testCreateRejectsNonStringValue(): void
    {
        Configure::write('App.container', ['not', 'a', 'class', 'name']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`App.container` must be a class name implementing');
        ContainerFactory::create();
    }
}
