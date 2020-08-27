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

/**
 * Test cases for functions in Core\functions.php
 */
class FunctionsTest extends TestCase
{
    /**
     * Test cases for env()
     */
    public function testEnv()
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
     * @return void
     * @dataProvider hInputProvider
     */
    public function testH($value, $expected)
    {
        $result = h($value);
        $this->assertSame($expected, $result);
    }

    public function hInputProvider()
    {
        return [
            ['i am clean', 'i am clean'],
            ['i "need" escaping', 'i &quot;need&quot; escaping'],
            [null, null],
            [1, 1],
            [1.1, 1.1],
            [new \stdClass(), '(object)stdClass'],
            [new Response(), ''],
            [['clean', '"clean-me'], ['clean', '&quot;clean-me']],
        ];
    }

    /**
     * Test error messages coming out when deprecated level is on, manually setting the stack frame
     */
    public function testDeprecationWarningEnabled()
    {
        $this->expectDeprecation();
        $this->expectDeprecationMessageMatches('/This is going away - (.*?)[\/\\\]FunctionsTest.php, line\: \d+/');

        $this->withErrorReporting(E_ALL, function () {
            deprecationWarning('This is going away', 2);
        });
    }

    /**
     * Test error messages coming out when deprecated level is on, not setting the stack frame manually
     */
    public function testDeprecationWarningEnabledDefaultFrame()
    {
        $this->expectDeprecation();
        $this->expectDeprecationMessageMatches('/This is going away - (.*?)[\/\\\]TestCase.php, line\: \d+/');

        $this->withErrorReporting(E_ALL, function () {
            deprecationWarning('This is going away');
        });
    }

    /**
     * Test no error when deprecation matches ignore paths.
     *
     * @return void
     */
    public function testDeprecationWarningPathDisabled()
    {
        $this->expectNotToPerformAssertions();

        Configure::write('Error.ignoredDeprecationPaths', ['src/TestSuite/*']);
        $this->withErrorReporting(E_ALL, function () {
            deprecationWarning('This is going away');
        });
    }

    /**
     * Test no error when deprecated level is off.
     *
     * @return void
     */
    public function testDeprecationWarningLevelDisabled()
    {
        $this->expectNotToPerformAssertions();

        $this->withErrorReporting(E_ALL ^ E_USER_DEPRECATED, function () {
            deprecationWarning('This is going away');
        });
    }

    /**
     * Test error messages coming out when warning level is on.
     */
    public function testTriggerWarningEnabled()
    {
        $this->expectWarning();
        $this->expectWarningMessageMatches('/This is going away - (.*?)[\/\\\]TestCase.php, line\: \d+/');

        $this->withErrorReporting(E_ALL, function () {
            triggerWarning('This is going away');
            $this->assertTrue(true);
        });
    }

    /**
     * Test no error when warning level is off.
     *
     * @return void
     */
    public function testTriggerWarningLevelDisabled()
    {
        $this->withErrorReporting(E_ALL ^ E_USER_WARNING, function () {
            triggerWarning('This is going away');
            $this->assertTrue(true);
        });
    }

    /**
     * testing getTypeName()
     *
     * @return void
     */
    public function testgetTypeName()
    {
        $this->assertSame('stdClass', getTypeName(new \stdClass()));
        $this->assertSame('array', getTypeName([]));
        $this->assertSame('string', getTypeName(''));
    }
}
