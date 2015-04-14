<?php
/**
 * Testing Dispatch Shell Shell file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;

/**
 * Class for testing dispatchShell functionality
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
        Configure::write('App.namespace', 'TestApp');
        $this->dispatchShell('testing_dispatch', 'dispatch_test_task');
    }

    public function dispatchTestTask()
    {
        $this->out('I am a dispatched Shell');
    }
}
