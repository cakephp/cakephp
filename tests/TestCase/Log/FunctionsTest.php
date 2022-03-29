<?php
declare(strict_types=1);

/**
 * CakePHP(tm) <https://book.cakephp.org/4/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log;

use Cake\Log\Log;
use Cake\TestSuite\TestCase;

class FunctionsTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Log::reset();
    }

    public function testLogError(): void
    {
        Log::setConfig('test', [
            'engine' => 'Array',
            'levels' => ['error'],
        ]);

        logError('%s %s', 'formatted', 'string');
        $this->assertSame(['error: formatted string'], Log::engine('test')->read());
    }

    public function testLogWarning(): void
    {
        Log::setConfig('test', [
            'engine' => 'Array',
            'levels' => ['warning'],
        ]);

        logWarning('%s %s', 'formatted', 'string');
        $this->assertSame(['warning: formatted string'], Log::engine('test')->read());
    }

    public function testLogInfo(): void
    {
        Log::setConfig('test', [
            'engine' => 'Array',
            'levels' => ['info'],
        ]);

        logInfo('%s %s', 'formatted', 'string');
        $this->assertSame(['info: formatted string'], Log::engine('test')->read());
    }

    public function testLogDebug(): void
    {
        Log::setConfig('test', [
            'engine' => 'Array',
            'levels' => ['debug'],
        ]);

        logDebug('%s %s', 'formatted', 'string');
        $this->assertSame(['debug: formatted string'], Log::engine('test')->read());
    }
}
