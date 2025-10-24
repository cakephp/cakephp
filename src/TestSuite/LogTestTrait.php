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
use PHPUnit\Framework\Attributes\After;

/**
 * Make assertions on logs
 */
trait LogTestTrait
{
    /**
     * Reset log configs
     *
     * @return void
     */
    #[After]
    public function cleanupLog(): void
    {
        Log::reset();
    }

    /**
     * @param array|string $levels The levels(s) which should receive a log message
     * @return void
     */
    public function setupLog(array|string $levels): void
    {
        Log::reset();

        $levels = (array)$levels;
        foreach ($levels as $levelName => $levelConfig) {
            if (is_int($levelName) && is_string($levelConfig)) {
                // string value = level name.
                Log::setConfig("test-{$levelConfig}", [
                    'className' => 'Array',
                    'levels' => [$levelConfig],
                ]);
            }
            if (is_array($levelConfig)) {
                $levelConfig['className'] = 'Array';
                $levelConfig['levels'] ??= $levelName;
                $name = $levelConfig['name'] ?? "test-{$levelName}";
                Log::setConfig($name, $levelConfig);
            }
        }
    }

    /**
     * Ensure that no log messages of a given level were captured by test loggers.
     *
     * @param string $level The level of the expected message
     * @param string $failMsg The error message if the message was not in the log engine
     * @return void
     */
    public function assertLogAbsent(string $level, string $failMsg = ''): void
    {
        foreach (Log::configured() as $engineName) {
            $engineObj = Log::engine($engineName);
            if (!$engineObj instanceof ArrayLog) {
                continue;
            }
            $levels = $engineObj->levels();
            if (in_array($level, $levels)) {
                $this->assertEquals(0, count($engineObj->read()), $failMsg);
            }
        }
    }

    /**
     * @param string $level The level of the expected message
     * @param string $expectedMessage The message which should be inside the log engine
     * @param string|null $scope The scope of the expected message. If a message has
     *   multiple scopes, the provided scope must be within the message's set.
     * @param string $failMsg The error message if the message was not in the log engine
     * @return void
     */
    public function assertLogMessage(
        string $level,
        string $expectedMessage,
        ?string $scope = null,
        string $failMsg = '',
    ): void {
        $this->_expectLogMessage($level, $expectedMessage, $scope, $failMsg);
    }

    /**
     * @param string $level The level which should receive a log message
     * @param string $expectedMessage The message which should be inside the log engine
     * @param string|null $scope The scope of the expected message. If a message has
     *   multiple scopes, the provided scope must be within the message's set.
     * @param string $failMsg The error message if the message was not in the log engine
     * @return void
     */
    public function assertLogMessageContains(
        string $level,
        string $expectedMessage,
        ?string $scope = null,
        string $failMsg = '',
    ): void {
        $this->_expectLogMessage($level, $expectedMessage, $scope, $failMsg, true);
    }

    /**
     * @param string $level The level which should receive a log message
     * @param string $expectedMessage The message which should be inside the log engine
     * @param string|null $scope The scope of the expected message. If a message has
     *   multiple scopes, the provided scope must be within the message's set.
     * @param string $failMsg The error message if the message was not in the log engine
     * @param bool $contains Flag to decide if the expectedMessage can only be part of the logged message
     * @return void
     */
    protected function _expectLogMessage(
        string $level,
        string $expectedMessage,
        ?string $scope,
        string $failMsg = '',
        bool $contains = false,
    ): void {
        $messageFound = false;
        $expectedMessage = sprintf('%s: %s', $level, $expectedMessage);
        foreach (Log::configured() as $engineName) {
            $engineObj = Log::engine($engineName);
            if (!$engineObj instanceof ArrayLog) {
                continue;
            }
            $messages = $engineObj->read();
            $engineScopes = $engineObj->scopes();
            // No overlapping scopes
            if ($scope !== null && !in_array($scope, $engineScopes, true)) {
                continue;
            }
            foreach ($messages as $message) {
                if ($contains && str_contains($message, $expectedMessage) || $message === $expectedMessage) {
                    $messageFound = true;
                    break;
                }
            }
        }
        if (!$messageFound) {
            $failMsg = "Could not find the message `{$expectedMessage}` in logs. " . $failMsg;
            $this->fail($failMsg);
        }
        $this->assertTrue(true);
    }
}
