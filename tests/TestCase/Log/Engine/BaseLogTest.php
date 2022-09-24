<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         t.b.d.
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use ArrayObject;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use TestApp\Log\Engine\TestBaseLog;
use TestApp\Log\Formatter\InvalidFormatter;
use TestApp\Log\Formatter\ValidFormatter;

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

    private function assertUnescapedUnicode(array $needles, string $haystack): void
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
    public function testLogUnicodeString(): void
    {
        $this->logger->log(LogLevel::INFO, implode($this->testData));

        $this->assertUnescapedUnicode($this->testData, $this->logger->getMessage());
    }

    public function testPlaceHoldersInMessage(): void
    {
        $context = [
            'no-placholder' => 'no-placholder',
            'string' => 'a-string',
            'bool' => true,
            'json' => new Entity(['foo' => 'bar']),
            'array' => ['arr'],
            'array-obj' => new ArrayObject(['x' => 'y']),
            'debug-info' => ConnectionManager::get('test'),
            'obj' => function (): void {
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

    /**
     * Test setting custom formatter option.
     */
    public function testCustomFormatter(): void
    {
        $log = new TestBaseLog(['formatter' => ValidFormatter::class]);
        $this->assertNotNull($log);

        $log = new TestBaseLog(['formatter' => new ValidFormatter()]);
        $this->assertNotNull($log);
    }

    /**
     * Test creating log engine with invalid formatter.
     */
    public function testInvalidFormatter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TestBaseLog(['formatter' => InvalidFormatter::class]);
    }
}
