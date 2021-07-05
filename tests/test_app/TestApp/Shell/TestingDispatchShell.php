<?php
declare(strict_types=1);

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
    protected function _welcome(): void
    {
        $this->out('<info>Welcome to CakePHP Console</info>');
    }

    /**
     * @inheritDoc
     */
    public function out($message = null, int $newlines = 1, int $level = Shell::NORMAL): ?int
    {
        echo $message . "\n";

        return 1;
    }

    public function testTask(): void
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell('testing_dispatch dispatch_test_task');
    }

    public function testTaskDispatchArray(): void
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell('testing_dispatch', 'dispatch_test_task');
    }

    public function testTaskDispatchCommandString(): void
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell(['command' => 'testing_dispatch dispatch_test_task']);
    }

    public function testTaskDispatchCommandArray(): void
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell(['command' => ['testing_dispatch', 'dispatch_test_task']]);
    }

    public function testTaskDispatchWithParam(): void
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell([
            'command' => ['testing_dispatch', 'dispatch_test_task_param'],
            'extra' => [
                'foo' => 'bar',
            ],
        ]);
    }

    public function testTaskDispatchWithMultipleParams(): void
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell([
            'command' => 'testing_dispatch dispatch_test_task_params',
            'extra' => [
                'foo' => 'bar',
                'fooz' => 'baz',
            ],
        ]);
    }

    public function testTaskDispatchWithRequestedOff(): void
    {
        $this->out('I am a test task, I dispatch another Shell');
        TestCase::setAppNamespace();
        $this->dispatchShell([
            'command' => ['testing_dispatch', 'dispatch_test_task'],
            'extra' => [
                'requested' => false,
            ],
        ]);
    }

    public function dispatchTestTask(): void
    {
        $this->out('I am a dispatched Shell');
    }

    public function dispatchTestTaskParam(): void
    {
        $this->out('I am a dispatched Shell. My param `foo` has the value `' . $this->param('foo') . '`');
    }

    public function dispatchTestTaskParams(): void
    {
        $this->out('I am a dispatched Shell. My param `foo` has the value `' . $this->param('foo') . '`');
        $this->out('My param `fooz` has the value `' . $this->param('fooz') . '`');
    }
}
