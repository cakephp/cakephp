<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;
use stdClass;

/**
 * Test cases for functions in Core\functions.php
 */
class FunctionsTest extends TestCase
{
    /**
     * Test cases for env()
     */
    public function testEnv(): void
    {
        $_ENV['DOES_NOT_EXIST'] = null;
        $this->assertNull(env('DOES_NOT_EXIST'));
        $this->assertSame('default', env('DOES_NOT_EXIST', 'default'));

        $_ENV['DOES_EXIST'] = 'some value';
        $this->assertSame('some value', env('DOES_EXIST'));
        $this->assertSame('some value', env('DOES_EXIST', 'default'));

        $_ENV['EMPTY_VALUE'] = '';
        $this->assertSame('', env('EMPTY_VALUE'));
        $this->assertSame('', env('EMPTY_VALUE', 'default'));

        $_ENV['ZERO'] = '0';
        $this->assertSame('0', env('ZERO'));
        $this->assertSame('0', env('ZERO', '1'));

        $this->assertSame('', env('DOCUMENT_ROOT'));
        $this->assertStringContainsString('phpunit', env('PHP_SELF'));
    }

    /**
     * Test cases for h()
     *
     * @dataProvider hInputProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testH($value, $expected): void
    {
        $result = h($value);
        $this->assertSame($expected, $result);
    }

    public function hInputProvider(): array
    {
        return [
            ['i am clean', 'i am clean'],
            ['i "need" escaping', 'i &quot;need&quot; escaping'],
            [null, null],
            [1, 1],
            [1.1, 1.1],
            [new stdClass(), '(object)stdClass'],
            [new Response(), ''],
            [['clean', '"clean-me'], ['clean', '&quot;clean-me']],
        ];
    }

    /**
     * Test error messages coming out when deprecated level is on, manually setting the stack frame
     */
    public function testDeprecationWarningEnabled(): void
    {
        $this->expectDeprecation();
        $this->expectDeprecationMessageMatches('/This is going away\n(.*?)[\/\\\]FunctionsTest.php, line\: \d+/');

        $this->withErrorReporting(E_ALL, function (): void {
            deprecationWarning('This is going away', 2);
        });
    }

    /**
     * Test deprecation warnings trigger only once
     */
    public function testDeprecationWarningTriggerOnlyOnce(): void
    {
        $message = 'Test deprecation warnings trigger only once';
        try {
            $this->withErrorReporting(E_ALL, function () use ($message): void {
                deprecationWarning($message);
            });
            $this->fail();
        } catch (\Exception $e) {
            $this->assertStringContainsString($message, $e->getMessage());
            $this->assertStringContainsString('TestCase.php', $e->getMessage());
        }

        $this->withErrorReporting(E_ALL, function () use ($message): void {
            deprecationWarning($message);
        });
    }

    /**
     * Test error messages coming out when deprecated level is on, not setting the stack frame manually
     */
    public function testDeprecationWarningEnabledDefaultFrame(): void
    {
        $this->expectDeprecation();
        $this->expectDeprecationMessageMatches('/This is going away too\n(.*?)[\/\\\]TestCase.php, line\: \d+/');

        $this->withErrorReporting(E_ALL, function (): void {
            deprecationWarning('This is going away too');
        });
    }

    /**
     * Test no error when deprecation matches ignore paths.
     */
    public function testDeprecationWarningPathDisabled(): void
    {
        $this->expectNotToPerformAssertions();

        Configure::write('Error.ignoredDeprecationPaths', ['src/TestSuite/*']);
        $this->withErrorReporting(E_ALL, function (): void {
            deprecationWarning('This will be gone soon');
        });
    }

    /**
     * Test no error when deprecated level is off.
     */
    public function testDeprecationWarningLevelDisabled(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withErrorReporting(E_ALL ^ E_USER_DEPRECATED, function (): void {
            deprecationWarning('This is leaving');
        });
    }

    /**
     * Test error messages coming out when warning level is on.
     */
    public function testTriggerWarningEnabled(): void
    {
        $this->expectWarning();
        $this->expectWarningMessageMatches('/This will be gone one day - (.*?)[\/\\\]TestCase.php, line\: \d+/');

        $this->withErrorReporting(E_ALL, function (): void {
            triggerWarning('This will be gone one day');
            $this->assertTrue(true);
        });
    }

    /**
     * Test no error when warning level is off.
     */
    public function testTriggerWarningLevelDisabled(): void
    {
        $this->withErrorReporting(E_ALL ^ E_USER_WARNING, function (): void {
            triggerWarning('This was a mistake.');
            $this->assertTrue(true);
        });
    }

    /**
     * testing getTypeName()
     */
    public function testgetTypeName(): void
    {
        $this->assertSame('stdClass', getTypeName(new \stdClass()));
        $this->assertSame('array', getTypeName([]));
        $this->assertSame('string', getTypeName(''));
    }
}
