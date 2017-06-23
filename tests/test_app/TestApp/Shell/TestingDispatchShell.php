<?php
/**
 * Testing Dispatch Shell Shell file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Shell;

use Cake\Console\Shell;
use Cake\TestSuite\TestCase;

/**
 * for testing dispatchShell functionality
 */
class TestingDispatchShell extends Shell
{

    protected function _welcome()
    {
        $this->out('<info>Welcome to CakePHP Console</info>');
    }

    public function out($message = null, $newlines = 1, $level = Shell::NORMAL)
    {
        echo $message . "\n";
    }

    public function testTask()
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell('testing_dispatch dispatch_test_task');
    }

    public function testTaskDispatchArray()
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell('testing_dispatch', 'dispatch_test_task');
    }

    public function testTaskDispatchCommandString()
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell(['command' => 'testing_dispatch dispatch_test_task']);
    }

    public function testTaskDispatchCommandArray()
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell(['command' => ['testing_dispatch', 'dispatch_test_task']]);
    }

    public function testTaskDispatchWithParam()
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell([
            'command' => ['testing_dispatch', 'dispatch_test_task_param'],
            'extra' => [
                'foo' => 'bar'
            ]
        ]);
    }

    public function testTaskDispatchWithMultipleParams()
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell([
            'command' => 'testing_dispatch dispatch_test_task_params',
            'extra' => [
                'foo' => 'bar',
                'fooz' => 'baz'
            ]
        ]);
    }

    public function testTaskDispatchWithRequestedOff()
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell([
            'command' => ['testing_dispatch', 'dispatch_test_task'],
            'extra' => [
                'requested' => false
            ]
        ]);
    }

    public function dispatchTestTask()
    {
        $this->out('I am a dispatched Shell');
    }

    public function dispatchTestTaskParam()
    {
        $this->out('I am a dispatched Shell. My param `foo` has the value `' . $this->param('foo') . '`');
    }

    public function dispatchTestTaskParams()
    {
        $this->out('I am a dispatched Shell. My param `foo` has the value `' . $this->param('foo') . '`');
        $this->out('My param `fooz` has the value `' . $this->param('fooz') . '`');
    }
}
