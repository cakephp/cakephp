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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\TaskRegistry;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * TaskRegistryTest
 */
class TaskRegistryTest extends TestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();
        $this->Tasks = new TaskRegistry($shell);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Tasks);
        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded tasks
     *
     * @return void
     */
    public function testLoad()
    {
        $result = $this->Tasks->load('Command');
        $this->assertInstanceOf('Cake\Shell\Task\CommandTask', $result);
        $this->assertInstanceOf('Cake\Shell\Task\CommandTask', $this->Tasks->Command);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Command'], $result, 'loaded() results are wrong.');
    }

    /**
     * test missingtask exception
     *
     * @expectedException \Cake\Console\Exception\MissingTaskException
     * @return void
     */
    public function testLoadMissingTask()
    {
        $this->Tasks->load('ThisTaskShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     *
     * @return void
     */
    public function testLoadPluginTask()
    {
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();
        Plugin::load('TestPlugin');
        $this->Tasks = new TaskRegistry($shell, $dispatcher);

        $result = $this->Tasks->load('TestPlugin.OtherTask');
        $this->assertInstanceOf('TestPlugin\Shell\Task\OtherTaskTask', $result, 'Task class is wrong.');
        $this->assertInstanceOf('TestPlugin\Shell\Task\OtherTaskTask', $this->Tasks->OtherTask, 'Class is wrong');
        Plugin::unload();
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias()
    {
        Plugin::load('TestPlugin');

        $result = $this->Tasks->load('CommandAliased', ['className' => 'Command']);
        $this->assertInstanceOf('Cake\Shell\Task\CommandTask', $result);
        $this->assertInstanceOf('Cake\Shell\Task\CommandTask', $this->Tasks->CommandAliased);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['CommandAliased'], $result, 'loaded() results are wrong.');

        $result = $this->Tasks->load('SomeTask', ['className' => 'TestPlugin.OtherTask']);
        $this->assertInstanceOf('TestPlugin\Shell\Task\OtherTaskTask', $result);
        $this->assertInstanceOf('TestPlugin\Shell\Task\OtherTaskTask', $this->Tasks->SomeTask);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['CommandAliased', 'SomeTask'], $result, 'loaded() results are wrong.');
    }
}
