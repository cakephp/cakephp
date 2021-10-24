<?php
declare(strict_types=1);

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
    public function tearDown(): void
    {
        parent::tearDown();
        Log::drop('trait_test');
    }

    /**
     * Test log method.
     */
    public function testLog(): void
    {
        $mock = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
        $mock->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['error', 'Testing'],
                ['debug', 'message']
            );

        Log::setConfig('trait_test', ['engine' => $mock]);
        $subject = $this->getObjectForTrait('Cake\Log\LogTrait');

        $subject->log('Testing');
        $subject->log('message', 'debug');
    }
}
