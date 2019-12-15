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

use Cake\TestSuite\TestCase;
use Psr\Log\LogLevel;
use TestApp\Log\Engine\TestBaseLog;

class BaseLogTest extends TestCase
{
    private $testData = ['ä', 'ö', 'ü'];

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
        $logged = $this->logger->log(LogLevel::INFO, implode($this->testData));

        $this->assertUnescapedUnicode($this->testData, $logged);
    }
}
