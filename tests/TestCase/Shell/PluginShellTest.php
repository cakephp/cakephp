<?php
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
 * @since         3.1.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Core\Plugin;
use Cake\Shell\PluginShell;
use Cake\TestSuite\TestCase;

/**
 * PluginShell test.
 */
class PluginShellTest extends TestCase
{
    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $this->shell = new PluginShell($this->io);
    }

    /**
     * Test that the option parser is shaped right.
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $this->shell->loadTasks();
        $parser = $this->shell->getOptionParser();
        $commands = $parser->subcommands();
        $this->assertArrayHasKey('unload', $commands);
        $this->assertArrayHasKey('load', $commands);
        $this->assertArrayHasKey('assets', $commands);
    }

    /**
     * Tests that list of loaded plugins is shown with loaded command.
     *
     * @return void
     */
    public function testLoaded()
    {
        $array = Plugin::loaded();

        $this->io->expects($this->at(0))
            ->method('out')
            ->with($array);

        $this->shell->loaded();
    }
}
