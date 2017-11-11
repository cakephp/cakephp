<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log;

use Cake\Log\Log;
use Cake\TestSuite\TestCase;

/**
 * Test case for LogTrait
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
        $mock = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
        $mock->expects($this->at(0))
            ->method('log')
            ->with('error', 'Testing');

        $mock->expects($this->at(1))
            ->method('log')
            ->with('debug', [1, 2]);

        Log::setConfig('trait_test', ['engine' => $mock]);
        $subject = $this->getObjectForTrait('Cake\Log\LogTrait');

        $subject->log('Testing');
        $subject->log([1, 2], 'debug');
    }
}
