<?php
declare(strict_types=1);

/**
 * BasicsTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Collection\Collection;
use Cake\Error\Debugger;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;

require_once CAKE . 'basics.php';

/**
 * BasicsTest class
 */
class BasicsTest extends TestCase
{
    /**
     * test the array_diff_key compatibility function.
     *
     * @return void
     */
    public function testArrayDiffKey()
    {
        $one = ['one' => 1, 'two' => 2, 'three' => 3];
        $two = ['one' => 'one', 'two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['three' => 3];
        $this->assertSame($expected, $result);

        $one = ['one' => ['value', 'value-two'], 'two' => 2, 'three' => 3];
        $two = ['two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['one' => ['value', 'value-two'], 'three' => 3];
        $this->assertSame($expected, $result);

        $one = ['one' => null, 'two' => 2, 'three' => '', 'four' => 0];
        $two = ['two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['one' => null, 'three' => '', 'four' => 0];
        $this->assertSame($expected, $result);

        $one = ['minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true];
        $two = ['minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true];
        $result = array_diff_key($one, $two);
        $this->assertSame([], $result);

        $one = ['minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true];
        $two = [];
        $result = array_diff_key($one, $two);
        $this->assertSame($one, $result);
    }

    /**
     * testHttpBase method
     *
     * @return void
     */
    public function testEnv()
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
     * Test h()
     *
     * @return void
     */
    public function testH()
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

        $obj = new \stdClass();
        $result = h($obj);
        $this->assertSame('(object)stdClass', $result);

        $obj = new Response(['body' => 'Body content']);
        $result = h($obj);
        $this->assertSame('Body content', $result);
    }

    /**
     * test debug()
     *
     * @return void
     */
    public function testDebug()
    {
        ob_start();
        $this->assertSame('this-is-a-test', debug('this-is-a-test', false));
        $result = ob_get_clean();
        $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'this-is-a-test'
###########################

EXPECTED;
        $expected = sprintf($expectedText, Debugger::trimPath(__FILE__), __LINE__ - 9);

        $this->assertSame($expected, $result);

        ob_start();
        $value = '<div>this-is-a-test</div>';
        $this->assertSame($value, debug($value, true));
        $result = ob_get_clean();
        $this->assertStringContainsString('<div class="cake-debug-output', $result);
        $this->assertStringContainsString('this-is-a-test', $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output cake-debug" style="direction:ltr">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
EXPECTED;
        $expected = sprintf($expected, Debugger::trimPath(__FILE__), __LINE__ - 6);
        $this->assertStringContainsString($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, false);
        $result = ob_get_clean();
        $this->assertStringNotContainsString('(line', $result);
    }

    /**
     * test pr()
     *
     * @return void
     */
    public function testPr()
    {
        ob_start();
        $this->assertTrue(pr(true));
        $result = ob_get_clean();
        $expected = "\n1\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertFalse(pr(false));
        $result = ob_get_clean();
        $expected = "\n\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertNull(pr(null));
        $result = ob_get_clean();
        $expected = "\n\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertSame(123, pr(123));
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pr('123');
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pr('this is a test');
        $result = ob_get_clean();
        $expected = "\nthis is a test\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pr(['this' => 'is', 'a' => 'test', 123 => 456]);
        $result = ob_get_clean();
        $expected = "\nArray\n(\n    [this] => is\n    [a] => test\n    [123] => 456\n)\n\n";
        $this->assertSame($expected, $result);
    }

    /**
     * test pj()
     *
     * @return void
     */
    public function testPj()
    {
        ob_start();
        $this->assertTrue(pj(true));
        $result = ob_get_clean();
        $expected = "\ntrue\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertFalse(pj(false));
        $result = ob_get_clean();
        $expected = "\nfalse\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertNull(pj(null));
        $result = ob_get_clean();
        $expected = "\nnull\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertSame(123, pj(123));
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pj('123');
        $result = ob_get_clean();
        $expected = "\n\"123\"\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pj('this is a test');
        $result = ob_get_clean();
        $expected = "\n\"this is a test\"\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $value = ['this' => 'is', 'a' => 'test', 123 => 456];
        $this->assertSame($value, pj($value));
        $result = ob_get_clean();
        $expected = "\n{\n    \"this\": \"is\",\n    \"a\": \"test\",\n    \"123\": 456\n}\n\n";
        $this->assertSame($expected, $result);
    }

    /**
     * Test splitting plugin names.
     *
     * @return void
     */
    public function testPluginSplit()
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
     *
     * @return void
     */
    public function testNamespaceSplit()
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
     * Tests that the stackTrace() method is a shortcut for Debugger::trace()
     *
     * @return void
     */
    public function testStackTrace()
    {
        ob_start();
        // phpcs:ignore
        stackTrace(); $expected = Debugger::trace();
        $result = ob_get_clean();
        $this->assertSame($expected, $result);

        $opts = ['args' => true];
        ob_start();
        // phpcs:ignore
        stackTrace($opts); $expected = Debugger::trace($opts);
        $result = ob_get_clean();
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that the collection() method is a shortcut for new Collection
     *
     * @return void
     */
    public function testCollection()
    {
        $items = [1, 2, 3];
        $collection = collection($items);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame($items, $collection->toArray());
    }

    /**
     * Test that works in tandem with testEventManagerReset2 to
     * test the EventManager reset.
     *
     * The return value is passed to testEventManagerReset2 as
     * an arguments.
     *
     * @return \Cake\Event\EventManager
     */
    public function testEventManagerReset1()
    {
        $eventManager = EventManager::instance();
        $this->assertInstanceOf(EventManager::class, $eventManager);

        return $eventManager;
    }

    /**
     * Test if the EventManager is reset between tests.
     *
     * @depends testEventManagerReset1
     * @return void
     */
    public function testEventManagerReset2($prevEventManager)
    {
        $this->assertInstanceOf(EventManager::class, $prevEventManager);
        $this->assertNotSame($prevEventManager, EventManager::instance());
    }
}
