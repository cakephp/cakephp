<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log;

use Cake\Log\Log;
use Cake\Log\LogInterface;
use Cake\TestSuite\TestCase;

/**
 * Test case for LogTrait
 *
 */
class LogTraitTest extends TestCase
{

    public function tearDown()
    {
        parent::tearDown();
        Log::drop('trait_test');
    }

    /**
     * Test log method.
     *
     * @return void
     */
    public function testLog()
    {
        $mock = $this->getMock('Psr\Log\LoggerInterface');
        $mock->expects($this->at(0))
            ->method('log')
            ->with('error', 'Testing');

        $mock->expects($this->at(1))
            ->method('log')
            ->with('debug', [1, 2]);

        Log::config('trait_test', ['engine' => $mock]);
        $subject = $this->getObjectForTrait('Cake\Log\LogTrait');

        $subject->log('Testing');
        $subject->log([1, 2], 'debug');
    }
}
