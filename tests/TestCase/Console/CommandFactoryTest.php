<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\CommandFactory;
use Cake\Console\CommandInterface;
use Cake\Core\Container;
use Cake\TestSuite\TestCase;
use stdClass;
use TestApp\Command\DemoCommand;
use TestApp\Command\DependencyCommand;

class CommandFactoryTest extends TestCase
{
    public function testCreateCommand(): void
    {
        $factory = new CommandFactory();

        $command = $factory->create(DemoCommand::class);
        $this->assertInstanceOf(DemoCommand::class, $command);
        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testCreateCommandDependencies(): void
    {
        $container = new Container();
        $container->add(stdClass::class, json_decode('{"key":"value"}'));
        $container->add(DependencyCommand::class)
            ->addArgument(stdClass::class);
        $factory = new CommandFactory($container);

        $command = $factory->create(DependencyCommand::class);
        $this->assertInstanceOf(DependencyCommand::class, $command);
        $this->assertInstanceOf(stdClass::class, $command->inject);
    }
}
