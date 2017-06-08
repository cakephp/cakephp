<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         t.b.d.
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use Cake\Log\Engine\BaseLog;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Psr\Log\LogLevel;

class BaseLogTest extends TestCase
{

    private $testData = ['ä', 'ö', 'ü'];

    private $logger;

    /**
     * Setting up the test case.
     * Creates a stub logger implementing the log() function missing from abstract class BaseLog.
     */
    public function setUp()
    {
        parent::setUp();

        $this->logger = new class() extends BaseLog {

            /**
             * Logs with an arbitrary level.
             *
             * @param mixed $level
             * @param mixed $message
             * @param array $context
             *
             * @return mixed
             */
            public function log($level, $message, array $context = array())
            {
                return $this->_format($message, $context);
            }
        };
    }

    private function assertUnescapedUnicode(array $needles, string $haystack)
    {
        foreach ($needles as $needle) {
            $this->assertContains(
                $needle,
                $haystack,
                'Formatted log message does not contain unescaped unicode character.'
            );
        }
    }

    /**
     * Tests the logging output of a single string containing unicode characters.
     */
    public function testLogUnicodeString()
    {
        $logged = $this->logger->log(LogLevel::INFO, implode($this->testData));

        $this->assertUnescapedUnicode($this->testData, $logged);
    }

    /**
     * Tests the logging output of an array containing unicode characters.
     */
    public function testLogUnicodeArray()
    {
        $logged = $this->logger->log(LogLevel::INFO, $this->testData);

        $this->assertUnescapedUnicode($this->testData, $logged);
    }

    /**
     * Tests the logging output of an object implementing __toString().
     * Note: __toString() will return a single string containing unicode characters.
     */
    public function testLogUnicodeObjectToString()
    {
        $stub = $this->createMock(Entity::class);
        $stub->method('__toString')
            ->willReturn(implode($this->testData));

        $logged = $this->logger->log(LogLevel::INFO, $stub);

        $this->assertUnescapedUnicode($this->testData, $logged);
    }

    /**
     * Tests the logging output of an object implementing jsonSerializable().
     * Note: jsonSerializable() will return an array containing unicode characters.
     */
    public function testLogUnicodeObjectJsonSerializable()
    {
        $stub = $this->createMock(\JsonSerializable::class);
        $stub->method('jsonSerialize')
            ->willReturn($this->testData);

        $logged = $this->logger->log(LogLevel::INFO, $stub);

        $this->assertUnescapedUnicode($this->testData, $logged);
    }
}
