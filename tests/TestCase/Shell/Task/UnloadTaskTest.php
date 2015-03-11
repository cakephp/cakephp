<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\Filesystem\File;

/**
 * UnloadTaskTest class
 *
 */
class UnloadTaskTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

        $this->Task = $this->getMock(
                'Cake\Shell\Task\UnloadTask', ['in', 'out', 'err', '_stop'], [$this->io]
        );

        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';

        $bootstrap = new File($this->bootstrap, false);

        $this->originalBootstrapContent = $bootstrap->read();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->shell);
        Plugin::unload();

        $bootstrap = new File($this->bootstrap, false);

        $bootstrap->write($this->originalBootstrapContent);
    }

    /**
     * testUnload
     *
     * @return void
     */
    public function testUnload()
    {
        $bootstrap = new File($this->bootstrap, false);

        $this->_addPluginToBootstrap("TestPlugin");

        $this->_addPluginToBootstrap("TestPluginSecond");

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $this->assertContains($expected, $bootstrap->read());

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);
        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $this->assertNotContains($expected, $bootstrap->read());
        $expected = "Plugin::load('TestPluginSecond', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $this->assertContains($expected, $bootstrap->read());
    }

    /**
     * _addPluginToBootstrap
     *
     * Quick method to add a plugin to the bootstrap file.
     * This is useful for the tests
     *
     * @param string $name
     */
    protected function _addPluginToBootstrap($name)
    {
        $bootstrap = new File($this->bootstrap, false);
        $bootstrap->append("\nPlugin::load('" . $name . "', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);");
    }
}
