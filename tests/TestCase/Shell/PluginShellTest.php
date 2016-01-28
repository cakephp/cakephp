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
 * @since         3.1.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
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
        $this->io = $this->getMock('Cake\Console\ConsoleIo');
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
}
