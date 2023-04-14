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
use function Cake\Core\deprecationWarning;
use function Cake\Core\env;
use function Cake\Core\getTypeName;
use function Cake\Core\h;
use function Cake\Core\namespaceSplit;
use function Cake\Core\pluginSplit;
use function Cake\Core\triggerWarning;

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

    public function testEnv2(): void
    {
        $this->skipIf(!function_exists('ini_get') || ini_get('safe_mode') === '1', 'Safe mode is on.');

        $server = $_SERVER;
        $env = $_ENV;
        $_SERVER = $_ENV = [];

        $_SERVER['SCRIPT_NAME'] = '/a/test/test.php';
        $this->assertSame(env('SCRIPT_NAME'), '/a/test/test.php');

        $_SERVER = $_ENV = [];

        $_ENV['CGI_MODE'] = 'BINARY';
        $_ENV['SCRIPT_URL'] = '/a/test/test.php';
        $this->assertSame(env('SCRIPT_NAME'), '/a/test/test.php');

        $_SERVER = $_ENV = [];

        $this->assertFalse(env('HTTPS'));

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = '1';
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = 'I am not empty';
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = 1;
        $this->assertTrue(env('HTTPS'));

        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse(env('HTTPS'));

        $_SERVER['HTTPS'] = false;
        $this->assertFalse(env('HTTPS'));

        $_SERVER['HTTPS'] = '';
        $this->assertFalse(env('HTTPS'));

        $_SERVER = [];

        $_ENV['SCRIPT_URI'] = 'https://domain.test/a/test.php';
        $this->assertTrue(env('HTTPS'));

        $_ENV['SCRIPT_URI'] = 'http://domain.test/a/test.php';
        $this->assertFalse(env('HTTPS'));

        $_SERVER = $_ENV = [];

        $this->assertNull(env('TEST_ME'));

        $_ENV['TEST_ME'] = 'a';
        $this->assertSame(env('TEST_ME'), 'a');

        $_SERVER['TEST_ME'] = 'b';
        $this->assertSame(env('TEST_ME'), 'b');

        unset($_ENV['TEST_ME']);
        $this->assertSame(env('TEST_ME'), 'b');

        $_SERVER = $server;
        $_ENV = $env;
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

    public function testH2(): void
    {
        $string = '<foo>';
        $result = h($string);
        $this->assertSame('&lt;foo&gt;', $result);

        $in = ['this & that', '<p>Which one</p>'];
        $result = h($in);
        $expected = ['this &amp; that', '&lt;p&gt;Which one&lt;/p&gt;'];
        $this->assertSame($expected, $result);

        $string = '<foo> & &nbsp;';
        $result = h($string);
        $this->assertSame('&lt;foo&gt; &amp; &amp;nbsp;', $result);

        $string = '<foo> & &nbsp;';
        $result = h($string, false);
        $this->assertSame('&lt;foo&gt; &amp; &nbsp;', $result);

        $string = "An invalid\x80string";
        $result = h($string);
        $this->assertStringContainsString('string', $result);

        $arr = ['<foo>', '&nbsp;'];
        $result = h($arr);
        $expected = [
            '&lt;foo&gt;',
            '&amp;nbsp;',
        ];
        $this->assertSame($expected, $result);

        $arr = ['<foo>', '&nbsp;'];
        $result = h($arr, false);
        $expected = [
            '&lt;foo&gt;',
            '&nbsp;',
        ];
        $this->assertSame($expected, $result);

        $arr = ['f' => '<foo>', 'n' => '&nbsp;'];
        $result = h($arr, false);
        $expected = [
            'f' => '&lt;foo&gt;',
            'n' => '&nbsp;',
        ];
        $this->assertSame($expected, $result);

        $arr = ['invalid' => "\x99An invalid\x80string", 'good' => 'Good string'];
        $result = h($arr);
        $this->assertStringContainsString('An invalid', $result['invalid']);
        $this->assertSame('Good string', $result['good']);

        // Test that boolean values are not converted to strings
        $result = h(false);
        $this->assertFalse($result);

        $arr = ['foo' => false, 'bar' => true];
        $result = h($arr);
        $this->assertFalse($result['foo']);
        $this->assertTrue($result['bar']);

        $obj = new stdClass();
        $result = h($obj);
        $this->assertSame('(object)stdClass', $result);

        $obj = new Response(['body' => 'Body content']);
        $result = h($obj);
        $this->assertSame('Body content', $result);
    }

    /**
     * Test splitting plugin names.
     */
    public function testPluginSplit(): void
    {
        $result = pluginSplit('Something.else');
        $this->assertSame(['Something', 'else'], $result);

        $result = pluginSplit('Something.else.more.dots');
        $this->assertSame(['Something', 'else.more.dots'], $result);

        $result = pluginSplit('Somethingelse');
        $this->assertSame([null, 'Somethingelse'], $result);

        $result = pluginSplit('Something.else', true);
        $this->assertSame(['Something.', 'else'], $result);

        $result = pluginSplit('Something.else.more.dots', true);
        $this->assertSame(['Something.', 'else.more.dots'], $result);

        $result = pluginSplit('Post', false, 'Blog');
        $this->assertSame(['Blog', 'Post'], $result);

        $result = pluginSplit('Blog.Post', false, 'Ultimate');
        $this->assertSame(['Blog', 'Post'], $result);
    }

    /**
     * test namespaceSplit
     */
    public function testNamespaceSplit(): void
    {
        $result = namespaceSplit('Something');
        $this->assertSame(['', 'Something'], $result);

        $result = namespaceSplit('\Something');
        $this->assertSame(['', 'Something'], $result);

        $result = namespaceSplit('Cake\Something');
        $this->assertSame(['Cake', 'Something'], $result);

        $result = namespaceSplit('Cake\Test\Something');
        $this->assertSame(['Cake\Test', 'Something'], $result);
    }

    /**
     * Test error messages coming out when deprecated level is on, manually setting the stack frame
     */
    public function testDeprecationWarningEnabled(): void
    {
        $error = $this->captureError(E_ALL, function (): void {
            deprecationWarning('This is going away', 2);
        });
        $this->assertMatchesRegularExpression(
            '/This is going away\n(.*?)[\/\\\]FunctionsTest.php, line\: \d+/',
            $error->getMessage()
        );
    }

    /**
     * Test error messages coming out when deprecated level is on, not setting the stack frame manually
     */
    public function testDeprecationWarningEnabledDefaultFrame(): void
    {
        $error = $this->captureError(E_ALL, function (): void {
            deprecationWarning('This is going away too');
        });
        $this->assertMatchesRegularExpression(
            '/This is going away too\n(.*?)[\/\\\]TestCase.php, line\: \d+/',
            $error->getMessage()
        );
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
        $error = $this->captureError(E_ALL, function (): void {
            triggerWarning('This will be gone one day ' . uniqid());
        });
        $this->assertMatchesRegularExpression(
            '/This will be gone one day \w+ - (.*?)[\/\\\]TestCase.php, line\: \d+/',
            $error->getMessage()
        );
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
