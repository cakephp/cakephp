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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Shell\PluginAssetsTask;
use Cake\TestSuite\TestCase;
use Cake\Filesystem\File;

/**
 * ExtractTaskTest class
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

        $this->original_bootstrap_content = $bootstrap->read();
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

        $bootstrap->write($this->original_bootstrap_content);
    }

    /**
     * testLoad
     *
     * @return void
     */
    public function testUnload()
    {

        Plugin::load('Company/TestPluginThree');

        $this->_addPluginToBootstrap("TestPlugin");

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertContains($expected, $bootstrap->read());

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $bootstrap = new File($this->bootstrap, false);
        debug($bootstrap->read());
        $this->assertNotContains($expected, $bootstrap->read());
    }

    /**
     * Quick method to add a plugin to the bootstrap file.
     * This is useful for the tests
     *
     * @param string $name
     */
    protected function _addPluginToBootstrap($name)
    {

        $bootstrap = new File($this->bootstrap, false);
        $bootstrap->append("Plugin::load('" . $name . "', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);");
    }

}
