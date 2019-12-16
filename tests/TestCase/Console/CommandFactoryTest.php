<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\CommandFactory;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Command\DemoCommand;
use TestApp\Shell\SampleShell;

class CommandFactoryTest extends TestCase
{
    public function testCreateCommand()
    {
        $factory = new CommandFactory();

        $command = $factory->create(DemoCommand::class);
        $this->assertInstanceOf(DemoCommand::class, $command);
    }

    public function testCreateShell()
    {
        $factory = new CommandFactory();

        $shell = $factory->create(SampleShell::class);
        $this->assertInstanceOf(SampleShell::class, $shell);
    }

    public function testInvalid()
    {
        $factory = new CommandFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class `Cake\Test\TestCase\Console\CommandFactoryTest` must be an instance of `Cake\Console\Shell` or `Cake\Console\Command`.');

        $factory->create(static::class);
    }
}
