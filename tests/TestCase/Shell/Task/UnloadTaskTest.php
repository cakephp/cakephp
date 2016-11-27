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
use Cake\Filesystem\File;
use Cake\TestSuite\TestCase;

/**
 * UnloadTaskTest class
 */
class UnloadTaskTest extends TestCase
{
    /**
     * @var \Cake\Shell\Task\UnloadTask|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $Task;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->Task = $this->getMockBuilder('Cake\Shell\Task\UnloadTask')
            ->setMethods(['in', 'out', 'err', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

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

        $this->Task->params = [
            'cli' => false
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);
        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $this->assertNotContains($expected, $bootstrap->read());
        $expected = "Plugin::load('TestPluginSecond', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $this->assertContains($expected, $bootstrap->read());
    }

    /**
     * testRegularExpressions
     *
     * This method will tests multiple notations of plugin loading.
     */
    public function testRegularExpressions()
    {
        $bootstrap = new File($this->bootstrap, false);

        $this->Task->params = [
            'cli' => false
        ];

        //  Plugin::load('TestPlugin', [
        //      'boostrap' => false
        //  ]);
        $bootstrap->append("\nPlugin::load('TestPlugin', [\n\t'boostrap' => false\n]);\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load('TestPlugin', [\n\t'boostrap' => false\n]);", $bootstrap->read());
        $this->_clearBootstrap();

        //  Plugin::load(
        //      'TestPlugin',
        //      [ 'boostrap' => false]
        //  );
        $bootstrap->append("\nPlugin::load(\n\t'TestPlugin',\n\t[ 'boostrap' => false]\n);\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load(\n\t'TestPlugin',\n\t[ 'boostrap' => false]\n);", $bootstrap->read());
        $this->_clearBootstrap();

        //  Plugin::load(
        //      'Foo',
        //      [
        //          'boostrap' => false
        //      ]
        //  );
        $bootstrap->append("\nPlugin::load(\n\t'TestPlugin',\n\t[\n\t\t'boostrap' => false\n\t]\n);\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load(\n\t'TestPlugin',\n\t[\n\t\t'boostrap' => false\n\t]\n);", $bootstrap->read());
        $this->_clearBootstrap();

        //  Plugin::load('Test', [
        //      'autoload' => false,
        //      'bootstrap' => true,
        //      'routes' => true
        //  ]);
        $bootstrap->append("\nPlugin::load('TestPlugin', [\n\t'autoload' => false,\n\t'bootstrap' => true,\n\t'routes' => true\n]);\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load('TestPlugin', [\n\t'autoload' => false,\n\t'bootstrap' => true,\n\t'routes' => true\n]);", $bootstrap->read());
        $this->_clearBootstrap();

        //  Plugin::load('Test',
        //      [
        //          'bootstrap' => true,
        //          'routes' => true
        //      ]
        //  );
        $bootstrap->append("\nPlugin::load('TestPlugin',\n\t[\n\t\t'bootstrap' => true,\n\t\t'routes' => true\n\t]\n);\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load('TestPlugin',\n\t[\n\t\t'bootstrap' => true,\n\t\t'routes' => true\n\t]\n);", $bootstrap->read());
        $this->_clearBootstrap();

        //  Plugin::load('Test',
        //      [
        //
        //      ]
        //  );
        $bootstrap->append("\nPlugin::load('TestPlugin',\n\t[\n\t\n\t]\n);\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load('TestPlugin',\n\t[\n\t\n\t]\n);", $bootstrap->read());
        $this->_clearBootstrap();

        //  Plugin::load('Test');
        $bootstrap->append("\nPlugin::load('TestPlugin');\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load('TestPlugin');", $bootstrap->read());
        $this->_clearBootstrap();

        //  Plugin::load('Test', ['bootstrap' => true, 'route' => false]);
        $bootstrap->append("\nPlugin::load('TestPlugin', ['bootstrap' => true, 'route' => false]);\n");
        $this->Task->main('TestPlugin');
        $this->assertNotContains("Plugin::load('TestPlugin', ['bootstrap' => true, 'route' => false]);", $bootstrap->read());
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
        $bootstrap->append("\n\nPlugin::load('$name', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);\n");
    }

    /**
     * clearBootstrap
     *
     * Helper to clear the bootstrap file.
     *
     * @return void
     */
    protected function _clearBootstrap()
    {
        $bootstrap = new File($this->bootstrap, false);

        $bootstrap->write($this->originalBootstrapContent);
    }
}
