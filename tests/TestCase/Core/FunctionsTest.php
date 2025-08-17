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
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Stringable;
use function Cake\Core\deprecationWarning;
use function Cake\Core\env;
use function Cake\Core\h;
use function Cake\Core\namespaceSplit;
use function Cake\Core\pathCombine;
use function Cake\Core\pluginSplit;
use function Cake\Core\toBool;
use function Cake\Core\toFloat;
use function Cake\Core\toInt;
use function Cake\Core\toString;
use function Cake\Core\triggerWarning;

/**
 * Test cases for functions in Core\functions.php
 */
class FunctionsTest extends TestCase
{
    public function testPathCombine(): void
    {
        $this->assertSame('', pathCombine([]));
        $this->assertSame('', pathCombine(['']));
        $this->assertSame('', pathCombine(['', '']));
        $this->assertSame('/', pathCombine(['/', '/']));

        $this->assertSame('path/to/file', pathCombine(['path', 'to', 'file']));
        $this->assertSame('path/to/file', pathCombine(['path/', 'to', 'file']));
        $this->assertSame('path/to/file', pathCombine(['path', 'to/', 'file']));
        $this->assertSame('path/to/file', pathCombine(['path/', 'to/', 'file']));
        $this->assertSame('path/to/file', pathCombine(['path/', '/to/', 'file']));

        $this->assertSame('/path/to/file', pathCombine(['/', 'path', 'to', 'file']));
        $this->assertSame('/path/to/file', pathCombine(['/', '/path', 'to', 'file']));

        $this->assertSame('/path/to/file/', pathCombine(['/path', 'to', 'file/']));
        $this->assertSame('/path/to/file/', pathCombine(['/path', 'to', 'file', '/']));
        $this->assertSame('/path/to/file/', pathCombine(['/path', 'to', 'file/', '/']));

        // Test adding trailing slash
        $this->assertSame('/', pathCombine([], trailing: true));
        $this->assertSame('/', pathCombine([''], trailing: true));
        $this->assertSame('/', pathCombine(['/'], trailing: true));
        $this->assertSame('/path/to/file/', pathCombine(['/path', 'to', 'file/'], trailing: true));
        $this->assertSame('/path/to/file/', pathCombine(['/path', 'to', 'file/', '/'], trailing: true));

        // Test removing trailing slash
        $this->assertSame('', pathCombine([''], trailing: false));
        $this->assertSame('', pathCombine(['/'], trailing: false));
        $this->assertSame('/path/to/file', pathCombine(['/path', 'to', 'file/'], trailing: false));
        $this->assertSame('/path/to/file', pathCombine(['/path', 'to', 'file/', '/'], trailing: false));

        // Test Windows-style backslashes
        $this->assertSame('/path/to\\file', pathCombine(['/', '\\path', 'to', '\\file']));
        $this->assertSame('/path\\to\\file/', pathCombine(['/', 'path', '\\to\\', 'file'], trailing: true));
        $this->assertSame('/path\\to\\file\\', pathCombine(['/', 'path', '\\to\\', 'file', '\\'], trailing: true));
        $this->assertSame('/path\\to\\file', pathCombine(['/', 'path', '\\to\\', 'file'], trailing: false));
        $this->assertSame('/path\\to\\file', pathCombine(['/', 'path', '\\to\\', 'file', '\\'], trailing: false));
    }

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

        $_ENV['ZERO'] = 0;
        $this->assertSame(0, env('ZERO'));
        $this->assertSame(0, env('ZERO', 1));

        $_ENV['ZERO'] = 0.0;
        $this->assertSame(0.0, env('ZERO'));
        $this->assertSame(0.0, env('ZERO', 1));

        $this->assertSame('', env('DOCUMENT_ROOT'));
        $this->assertStringContainsString('phpunit', env('PHP_SELF'));
    }

    public function testEnv2(): void
    {
        $this->skipIf(!function_exists('ini_get') || ini_get('safe_mode') === '1', 'Safe mode is on.');

        $server = $_SERVER;
        $env = $_ENV;
        $_SERVER = [];
        $_ENV = [];

        $_SERVER['SCRIPT_NAME'] = '/a/test/test.php';
        $this->assertSame(env('SCRIPT_NAME'), '/a/test/test.php');
        $_SERVER = [];
        $_ENV = [];

        $_ENV['CGI_MODE'] = 'BINARY';
        $_ENV['SCRIPT_URL'] = '/a/test/test.php';
        $this->assertSame(env('SCRIPT_NAME'), '/a/test/test.php');
        $_SERVER = [];
        $_ENV = [];

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
        $_SERVER = [];
        $_ENV = [];

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
     * @param mixed $value
     * @param mixed $expected
     */
    #[DataProvider('hInputProvider')]
    public function testH($value, $expected): void
    {
        $result = h($value);
        $this->assertSame($expected, $result);
    }

    public static function hInputProvider(): array
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
        $this->expectDeprecationMessageMatches('/Since 5.0.0: This is going away\n(.*?)[\/\\\]FunctionsTest.php, line\: \d+/', function (): void {
            $this->withErrorReporting(E_ALL, function (): void {
                deprecationWarning('5.0.0', 'This is going away', 2);
            });
        });
    }

    /**
     * Test error messages coming out when deprecated level is on, not setting the stack frame manually
     */
    public function testDeprecationWarningEnabledDefaultFrame(): void
    {
        $this->expectDeprecationMessageMatches('/Since 5.0.0: This is going away too\n(.*?)[\/\\\]TestCase.php, line\: \d+/', function (): void {
            $this->withErrorReporting(E_ALL, function (): void {
                deprecationWarning('5.0.0', 'This is going away too');
            });
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
            deprecationWarning('5.0.0', 'This will be gone soon');
        });
    }

    /**
     * Test no error when deprecated level is off.
     */
    public function testDeprecationWarningLevelDisabled(): void
    {
        $this->expectNotToPerformAssertions();

        $this->withErrorReporting(E_ALL ^ E_USER_DEPRECATED, function (): void {
            deprecationWarning('5.0.0', 'This is leaving');
        });
    }

    /**
     * Test error messages coming out when warning level is on.
     */
    public function testTriggerWarningEnabled(): void
    {
        $this->expectWarningMessageMatches('/This will be gone one day/', function (): void {
            $this->withErrorReporting(E_ALL, function (): void {
                triggerWarning('This will be gone one day');
                $this->assertTrue(true);
            });
        });
    }

    #[DataProvider('toStringProvider')]
    public function testToString(mixed $rawValue, ?string $expected): void
    {
        $this->assertSame($expected, toString($rawValue));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toStringProvider(): array
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        };

        return [
            // input like string
            '(string) empty' => ['', ''],
            '(string) space' => [' ', ' '],
            '(string) dash' => ['-', '-'],
            '(string) zero' => ['0', '0'],
            '(string) number' => ['55', '55'],
            '(string) partially2 number' => ['5x', '5x'],
            // input like int
            '(int) number' => [55, '55'],
            '(int) negative number' => [-5, '-5'],
            '(int) PHP_INT_MAX + 2' => [9223372036854775809, '9223372036854775808'], //is float: see IEEE 754
            '(int) PHP_INT_MAX + 1' => [9223372036854775808, '9223372036854775808'], //is float: see IEEE 754
            '(int) PHP_INT_MAX + 0' => [9223372036854775807, '9223372036854775807'],
            '(int) PHP_INT_MAX - 1' => [9223372036854775806, '9223372036854775806'],
            '(int) PHP_INT_MIN + 1' => [-9223372036854775807, '-9223372036854775807'],
            '(int) PHP_INT_MIN + 0' => [-9223372036854775808, '-9223372036854775808'],
            '(int) PHP_INT_MIN - 1' => [-9223372036854775809, '-9223372036854775808'], //is float: see IEEE 754
            '(int) PHP_INT_MIN - 2' => [-9223372036854775810, '-9223372036854775808'], //is float: see IEEE 754
            // input like float
            '(float) zero' => [0.0, '0'],
            '(float) positive' => [5.5, '5.5'],
            '(float) round' => [5.0, '5'],
            '(float) negative' => [-5.5, '-5.5'],
            '(float) round negative' => [-5.0, '-5'],
            '(float) small' => [0.000000000003, '0.000000000003'],
            '(float) small2' => [64321.0000003, '64321.0000003'],
            '(float) fractions' => [-9223372036778.2233, '-9223372036778.223'], //is float: see IEEE 754
            '(float) NaN' => [acos(8), null],
            '(float) INF' => [INF, null],
            '(float) -INF' => [-INF, null],
            // boolean input types
            '(bool) true' => [true, '1'],
            '(bool) false' => [false, '0'],
            // other input types
            '(other) null' => [null, null],
            '(other) empty-array' => [[], null],
            '(other) int-array' => [[5], null],
            '(other) string-array' => [['5'], null],
            '(other) simple object' => [new stdClass(), null],
            '(other) Stringable object' => [$stringable, 'stringable'],
        ];
    }

    #[DataProvider('toIntProvider')]
    public function testToInt(mixed $rawValue, null|int $expected): void
    {
        $this->assertSame($expected, toInt($rawValue));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toIntProvider(): array
    {
        return [
            // string input types
            '(string) empty' => ['', null],
            '(string) space' => [' ', null],
            '(string) null' => ['null', null],
            '(string) dash' => ['-', null],
            '(string) ctz' => ['čťž', null],
            '(string) hex' => ['0x539', null],
            '(string) binary' => ['0b10100111001', null],
            '(string) scientific e' => ['1.2e+2', null],
            '(string) scientific E' => ['1.2E+2', null],
            '(string) octal old' => ['0123', 123],
            '(string) octal new' => ['0o123', null],
            '(string) decimal php74' => ['1_234_567', null],
            '(string) zero' => ['0', 0],
            '(string) number' => ['55', 55],
            '(string) number_space_before' => [' 55', 55],
            '(string) number_space_after' => ['55 ', 55],
            '(string) padded number' => ['00055', 55],
            '(string) padded number_space_before' => [' 00055', 55],
            '(string) padded number_space_after' => ['00055 ', 55],
            '(string) negative number' => ['-5', -5],
            '(string) float round' => ['5.0', null],
            '(string) float round negative' => ['-5.0', null],
            '(string) float real' => ['5.1', null],
            '(string) float round slovak' => ['5,0', null],
            '(string) padded float round' => ['0005.0', null],
            '(string) padded float real' => ['0005.1', null],
            '(string) padded float round slovak' => ['0005,0', null],
            '(string) money' => ['5 €', null],
            '(string) PHP_INT_MAX + 1' => ['9223372036854775808', null],
            '(string) PHP_INT_MAX + 0' => ['9223372036854775807', 9223372036854775807],
            '(string) PHP_INT_MAX - 1' => ['9223372036854775806', 9223372036854775806],
            '(string) PHP_INT_MIN + 1' => ['-9223372036854775807', -9223372036854775807],
            '(string) PHP_INT_MIN + 0' => ['-9223372036854775808', null],
            '(string) PHP_INT_MIN - 1' => ['-9223372036854775809', null],
            '(string) string' => ['f', null],
            '(string) partially1 number' => ['5 5', null],
            '(string) partially2 number' => ['5x', null],
            '(string) partially3 number' => ['x4', null],
            '(string) double dot' => ['5.1.0', null],
            // int input types
            '(int) number' => [55, 55],
            '(int) negative number' => [-5, -5],
            '(int) PHP_INT_MAX + 1' => [9223372036854775808, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(int) PHP_INT_MAX + 0' => [9223372036854775807, 9223372036854775807],
            '(int) PHP_INT_MAX - 1' => [9223372036854775806, 9223372036854775806],
            '(int) PHP_INT_MIN + 1' => [-9223372036854775807, -9223372036854775807],
            // PHP_INT_MIN is float -> PHP inconsistency https://bugs.php.net/bug.php?id=53934
            '(int) PHP_INT_MIN + 0' => [-9223372036854775808, -9223372036854775807 - 1], // ¯\_(ツ)_/¯,
            '(int) PHP_INT_MIN - 1' => [-9223372036854775809, -9223372036854775807 - 1], // ¯\_(ツ)_/¯,
            // float input types
            '(float) zero' => [0.0, 0],
            '(float) positive' => [5.5, 5],
            '(float) round' => [5.0, 5],
            '(float) negative' => [-5.5, -5],
            '(float) round negative' => [-5.0, -5],
            '(float) PHP_INT_MAX + 1' => [9223372036854775808.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) PHP_INT_MAX + 0' => [9223372036854775807.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) PHP_INT_MAX - 1' => [9223372036854775806.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) PHP_INT_MIN + 1' => [-9223372036854775807.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) PHP_INT_MIN + 0' => [-9223372036854775808.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) PHP_INT_MIN - 1' => [-9223372036854775809.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) 2^53 + 2' => [9007199254740994.0, 9007199254740994],
            '(float) 2^53 + 1' => [9007199254740993.0, 9007199254740992], // see IEEE 754
            '(float) 2^53 + 0' => [9007199254740992.0, 9007199254740992],
            '(float) 2^53 - 1' => [9007199254740991.0, 9007199254740991],
            '(float) 2^53 - 2' => [9007199254740990.0, 9007199254740990],
            '(float) -(2^53) + 2' => [-9007199254740990.0, -9007199254740990],
            '(float) -(2^53) + 1' => [-9007199254740991.0, -9007199254740991],
            '(float) -(2^53) + 0' => [-9007199254740992.0, -9007199254740992],
            '(float) -(2^53) - 1' => [-9007199254740993.0, -9007199254740992], // see IEEE 754
            '(float) -(2^53) - 2' => [-9007199254740994.0, -9007199254740994],
            '(float) NaN' => [acos(8), null],
            '(float) INF' => [INF, null],
            '(float) -INF' => [-INF, null],
            // boolean input types
            '(bool) true' => [true, 1],
            '(bool) false' => [false, 0],
            // other input types
            '(other) null' => [null, null],
            '(other) empty-array' => [[], null],
            '(other) int-array' => [[5], null],
            '(other) string-array' => [['5'], null],
            '(other) simple object' => [new stdClass(), null],
        ];
    }

    #[DataProvider('toFloatProvider')]
    public function testToFloat(mixed $rawValue, null|float $expected): void
    {
        $this->assertSame($expected, toFloat($rawValue));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toFloatProvider(): array
    {
        return [
            // string input types
            '(string) empty' => ['', null],
            '(string) space' => [' ', null],
            '(string) null' => ['null', null],
            '(string) dash' => ['-', null],
            '(string) ctz' => ['čťž', null],
            '(string) hex' => ['0x539', null],
            '(string) binary' => ['0b10100111001', null],
            '(string) scientific e' => ['1.2e+2', 120.0],
            '(string) scientific E' => ['1.2E+2', 120.],
            '(string) octal old' => ['0123', 123.0],
            '(string) octal new' => ['0o123', null],
            '(string) decimal php74' => ['1_234_567', null],
            '(string) zero' => ['0', 0.0],
            '(string) number' => ['55', 55.0],
            '(string) number_space_before' => [' 55', 55.0],
            '(string) number_space_after' => ['55 ', 55.0],
            '(string) padded number' => ['00055', 55.0],
            '(string) padded number_space_before' => [' 00055', 55.0],
            '(string) padded number_space_after' => ['00055 ', 55.0],
            '(string) negative number' => ['-5', -5.0],
            '(string) float round' => ['5.0', 5.0],
            '(string) float round negative' => ['-5.0', -5.0],
            '(string) float real' => ['5.1', 5.1],
            '(string) float round slovak' => ['5,0', null],
            '(string) padded float round' => ['0005.0', 5.0],
            '(string) padded float real' => ['0005.1', 5.1],
            '(string) padded float round slovak' => ['0005,0', null],
            '(string) money' => ['5 €', null],
            '(string) PHP_INT_MAX + 1' => ['9223372036854775808', PHP_INT_MAX],
            '(string) PHP_INT_MAX + 0' => ['9223372036854775807', 9223372036854775807],
            '(string) PHP_INT_MAX - 1' => ['9223372036854775806', 9223372036854775806],
            '(string) PHP_INT_MIN + 1' => ['-9223372036854775807', -9223372036854775807],
            '(string) PHP_INT_MIN + 0' => ['-9223372036854775808', -9223372036854775807],
            '(string) PHP_INT_MIN - 1' => ['-9223372036854775809', -9223372036854775807],
            '(string) string' => ['f', null],
            '(string) partially1 number' => ['5 5', null],
            '(string) partially2 number' => ['5x', null],
            '(string) partially3 number' => ['x4', null],
            '(string) double dot' => ['5.1.0', null],
            // int input types
            '(int) number' => [55, 55.0],
            '(int) negative number' => [-5, -5.0],
            '(int) PHP_INT_MAX + 1' => [9223372036854775808, 9223372036854775807 - 1],
            '(int) PHP_INT_MAX + 0' => [9223372036854775807, 9223372036854775807],
            '(int) PHP_INT_MAX - 1' => [9223372036854775806, 9223372036854775806],
            '(int) PHP_INT_MIN + 1' => [-9223372036854775807, -9223372036854775807],
            // PHP_INT_MIN is float -> PHP inconsistency https://bugs.php.net/bug.php?id=53934
            '(int) PHP_INT_MIN + 0' => [-9223372036854775808, -9223372036854775807 - 1], // ¯\_(ツ)_/¯,
            '(int) PHP_INT_MIN - 1' => [-9223372036854775809, -9223372036854775807 - 1], // ¯\_(ツ)_/¯,
            // float input types
            '(float) zero' => [0.0, 0.0],
            '(float) positive' => [5.5, 5.5],
            '(float) round' => [5.0, 5.0],
            '(float) negative' => [-5.5, -5.5],
            '(float) round negative' => [-5.0, -5.0],
            '(float) PHP_INT_MAX + 1' => [9223372036854775808.0, 9223372036854775807 - 1],
            '(float) PHP_INT_MAX + 0' => [9223372036854775807.0, 9223372036854775807 - 1],
            '(float) PHP_INT_MAX - 1' => [9223372036854775806.0, 9223372036854775807 - 1],
            '(float) PHP_INT_MIN + 1' => [-9223372036854775807.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) PHP_INT_MIN + 0' => [-9223372036854775808.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) PHP_INT_MIN - 1' => [-9223372036854775809.0, -9223372036854775807 - 1], // ¯\_(ツ)_/¯
            '(float) 2^53 + 2' => [9007199254740994.0, 9007199254740994],
            '(float) 2^53 + 1' => [9007199254740993.0, 9007199254740992], // see IEEE 754
            '(float) 2^53 + 0' => [9007199254740992.0, 9007199254740992],
            '(float) 2^53 - 1' => [9007199254740991.0, 9007199254740991],
            '(float) 2^53 - 2' => [9007199254740990.0, 9007199254740990],
            '(float) -(2^53) + 2' => [-9007199254740990.0, -9007199254740990],
            '(float) -(2^53) + 1' => [-9007199254740991.0, -9007199254740991],
            '(float) -(2^53) + 0' => [-9007199254740992.0, -9007199254740992],
            '(float) -(2^53) - 1' => [-9007199254740993.0, -9007199254740992], // see IEEE 754
            '(float) -(2^53) - 2' => [-9007199254740994.0, -9007199254740994],
            '(float) NaN' => [acos(8), null],
            '(float) INF' => [INF, null],
            '(float) -INF' => [-INF, null],
            // boolean input types
            '(bool) true' => [true, 1.0],
            '(bool) false' => [false, 0.0],
            // other input types
            '(other) null' => [null, null],
            '(other) empty-array' => [[], null],
            '(other) int-array' => [[5], null],
            '(other) string-array' => [['5'], null],
            '(other) simple object' => [new stdClass(), null],
        ];
    }

    #[DataProvider('toBoolProvider')]
    public function testToBool(mixed $rawValue, ?bool $expected): void
    {
        $this->assertSame($expected, toBool($rawValue));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toBoolProvider(): array
    {
        return [
            // string input types
            '(string) empty string' => ['', null],
            '(string) space' => [' ', null],
            '(string) some word' => ['abc', null],
            '(string) double 0' => ['00', null],
            '(string) single 0' => ['0', false],
            '(string) false' => ['false', null],
            '(string) double 1' => ['11', null],
            '(string) single 1' => ['1', true],
            '(string) true-string' => ['true', null],
            // int input types
            '(int) 0' => [0, false],
            '(int) 1' => [1, true],
            '(int) -1' => [-1, null],
            '(int) 55' => [55, null],
            '(int) negative number' => [-5, null],
            // float input types
            '(float) positive' => [5.5, null],
            '(float) round' => [5.0, null],
            '(float) 0.0' => [0.0, false],
            '(float) 1.0' => [1.0, true],
            '(float) NaN' => [acos(8), null],
            '(float) INF' => [INF, null],
            '(float) -INF' => [-INF, null],
            // boolean input types
            '(bool) true' => [true, true],
            '(bool) false' => [false, false],
            // other input types
            '(other) null' => [null, null],
            '(other) empty-array' => [[], null],
            '(other) int-array' => [[5], null],
            '(other) string-array' => [['5'], null],
            '(other) simple object' => [new stdClass(), null],
        ];
    }
}
