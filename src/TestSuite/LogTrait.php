<?php
declare(strict_types=1);

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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use InvalidArgumentException;

/**
 * Make assertions on logs
 */
trait LogTrait
{
    /**
     * Reset log configs
     *
     * @after
     * @return void
     */
    public function cleanupLog(): void
    {
        Log::reset();
    }

    /**
     * @param array|string $engines The engine(s) which should receive a log message
     * @return void
     */
    public function setupLog(array|string $engines): void
    {
        Log::reset();
        $engines = (array)$engines;
        foreach ($engines as $engine) {
            Log::setConfig($engine, ['className' => 'Array']);
        }
    }

    /**
     * @param string $engine The engine which should receive a log message
     * @param string $expectedMessage The message which should be inside the log engine
     * @param string $failMsg The error message if the message was not in the log engine
     * @return void
     */
    public function assertLogMessage(string $engine, string $expectedMessage, string $failMsg = ''): void
    {
        $this->_expectLogMessage($engine, $expectedMessage, $failMsg);
    }

    /**
     * @param string $engine The engine which should receive a log message
     * @param string $expectedMessage The message which should be inside the log engine
     * @param string $failMsg The error message if the message was not in the log engine
     * @return void
     */
    public function assertLogMessageContains(string $engine, string $expectedMessage, string $failMsg = ''): void
    {
        $this->_expectLogMessage($engine, $expectedMessage, $failMsg, true);
    }

    /**
     * @param string $engine The engine which should receive a log message
     * @param string $expectedMessage The message which should be inside the log engine
     * @param string $failMsg The error message if the message was not in the log engine
     * @param bool $contains Flag to decide if the expectedMessage can only be part of the logged message
     * @return void
     */
    protected function _expectLogMessage(
        string $engine,
        string $expectedMessage,
        string $failMsg = '',
        bool $contains = false
    ): void {
        $engineObj = Log::engine($engine);

        if (!$engineObj instanceof ArrayLog) {
            $msg = sprintf('`%s` is not of type ArrayLog. ' .
                'Make sure to call `setupLog(\'%s\')` before expecting a log message.', $engine, $engine);
            throw new InvalidArgumentException($msg);
        }

        $messageFound = false;
        $messages = $engineObj->read();

        $expectedMessage = sprintf('%s: %s', $engine, $expectedMessage);
        foreach ($messages as $message) {
            if ($contains && str_contains($message, $expectedMessage) || $message === $expectedMessage) {
                $messageFound = true;
                break;
            }
        }
        $this->assertTrue($messageFound, $failMsg);
    }
}
