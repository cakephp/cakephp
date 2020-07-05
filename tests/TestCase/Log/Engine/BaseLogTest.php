<?php
declare(strict_types=1);

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

use ArrayObject;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Psr\Log\LogLevel;
use TestApp\Log\Engine\TestBaseLog;

class BaseLogTest extends TestCase
{
    private $testData = ['ä', 'ö', 'ü'];

    /**
     * @var \TestApp\Log\Engine\TestBaseLog
     */
    private $logger;

    /**
     * Setting up the test case.
     * Creates a stub logger implementing the log() function missing from abstract class BaseLog.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new TestBaseLog();
    }

    private function assertUnescapedUnicode(array $needles, $haystack)
    {
        foreach ($needles as $needle) {
            $this->assertStringContainsString(
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
        $this->logger->log(LogLevel::INFO, implode($this->testData));

        $this->assertUnescapedUnicode($this->testData, $this->logger->getMessage());
    }

    public function testPlaceHoldersInMessage()
    {
        $context = [
            'no-placholder' => 'no-placholder',
            'string' => 'a-string',
            'bool' => true,
            'json' => new Entity(['foo' => 'bar']),
            'array' => ['arr'],
            'array-obj' => new ArrayObject(['x' => 'y']),
            'debug-info' => ConnectionManager::get('test'),
            'obj' => function () {
            },
            'to-string' => new Response(['body' => 'response body']),
            'to-array' => new TypeMap(['my-type']),
        ];
        $this->logger->log(
            LogLevel::INFO,
            '1: {string}, 2: {bool}, 3: {json}, 4: {not a placeholder}, 5: {array}, '
            . '6: {array-obj} 7: {obj}, 8: {debug-info} 9: {valid-ph-not-in-context}',
            $context
        );

        $message = $this->logger->getMessage();

        $this->assertStringContainsString('1: a-string', $message);
        $this->assertStringContainsString('2: 1', $message);
        $this->assertStringContainsString('3: {"foo":"bar"}', $message);
        $this->assertStringContainsString('4: {not a placeholder}', $message);
        $this->assertStringContainsString('5: ["arr"]', $message);
        $this->assertStringContainsString('6: {"x":"y"}', $message);
        $this->assertStringContainsString('7: [unhandled value of type Closure]', $message);
        $this->assertStringContainsString(
            '8: ' . json_encode(ConnectionManager::get('test')->__debugInfo(), JSON_UNESCAPED_UNICODE),
            $message
        );
        $this->assertStringContainsString('9: {valid-ph-not-in-context}', $message);

        $this->logger->log(
            LogLevel::INFO,
            '1: {to-string}',
            $context
        );
        $this->assertSame('1: response body', $this->logger->getMessage());

        $this->logger->log(
            LogLevel::INFO,
            'no placeholder holders',
            $context
        );
        $this->assertSame('no placeholder holders', $this->logger->getMessage());

        $this->logger->log(
            LogLevel::INFO,
            '{to-array}',
            $context
        );
        $this->assertSame('["my-type"]', $this->logger->getMessage());

        $this->logger->log(
            LogLevel::INFO,
            '\{string}',
            ['string' => 'a-string']
        );
        $this->assertSame('\{string}', $this->logger->getMessage());

        $this->logger->log(
            LogLevel::INFO,
            '1: {_ph1}, 2: {0ph2}',
            ['_ph1' => '1st-string', '0ph2' => '2nd-string']
        );
        $this->assertSame('1: 1st-string, 2: 2nd-string', $this->logger->getMessage());

        $this->logger->log(
            LogLevel::INFO,
            '{0}',
            ['val']
        );
        $this->assertSame('val', $this->logger->getMessage());
    }
}
