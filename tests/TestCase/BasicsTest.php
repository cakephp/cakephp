<?php
/**
 * BasicsTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\Log\Log;
use Cake\Network\Response;
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
        $this->assertEquals($expected, $result);

        $one = ['one' => ['value', 'value-two'], 'two' => 2, 'three' => 3];
        $two = ['two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['one' => ['value', 'value-two'], 'three' => 3];
        $this->assertEquals($expected, $result);

        $one = ['one' => null, 'two' => 2, 'three' => '', 'four' => 0];
        $two = ['two' => 'two'];
        $result = array_diff_key($one, $two);
        $expected = ['one' => null, 'three' => '', 'four' => 0];
        $this->assertEquals($expected, $result);

        $one = ['minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true];
        $two = ['minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true];
        $result = array_diff_key($one, $two);
        $this->assertSame([], $result);
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
        $this->assertEquals(env('SCRIPT_NAME'), '/a/test/test.php');

        $_SERVER = $_ENV = [];

        $_ENV['CGI_MODE'] = 'BINARY';
        $_ENV['SCRIPT_URL'] = '/a/test/test.php';
        $this->assertEquals(env('SCRIPT_NAME'), '/a/test/test.php');

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
        $this->assertEquals(env('TEST_ME'), 'a');

        $_SERVER['TEST_ME'] = 'b';
        $this->assertEquals(env('TEST_ME'), 'b');

        unset($_ENV['TEST_ME']);
        $this->assertEquals(env('TEST_ME'), 'b');

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
        $this->assertEquals('&lt;foo&gt;', $result);

        $in = ['this & that', '<p>Which one</p>'];
        $result = h($in);
        $expected = ['this &amp; that', '&lt;p&gt;Which one&lt;/p&gt;'];
        $this->assertEquals($expected, $result);

        $string = '<foo> & &nbsp;';
        $result = h($string);
        $this->assertEquals('&lt;foo&gt; &amp; &amp;nbsp;', $result);

        $string = '<foo> & &nbsp;';
        $result = h($string, false);
        $this->assertEquals('&lt;foo&gt; &amp; &nbsp;', $result);

        $string = '<foo> & &nbsp;';
        $result = h($string, 'UTF-8');
        $this->assertEquals('&lt;foo&gt; &amp; &amp;nbsp;', $result);

        $string = "An invalid\x80string";
        $result = h($string);
        $this->assertContains('string', $result);

        $arr = ['<foo>', '&nbsp;'];
        $result = h($arr);
        $expected = [
            '&lt;foo&gt;',
            '&amp;nbsp;'
        ];
        $this->assertEquals($expected, $result);

        $arr = ['<foo>', '&nbsp;'];
        $result = h($arr, false);
        $expected = [
            '&lt;foo&gt;',
            '&nbsp;'
        ];
        $this->assertEquals($expected, $result);

        $arr = ['f' => '<foo>', 'n' => '&nbsp;'];
        $result = h($arr, false);
        $expected = [
            'f' => '&lt;foo&gt;',
            'n' => '&nbsp;'
        ];
        $this->assertEquals($expected, $result);

        $arr = ['invalid' => "\x99An invalid\x80string", 'good' => 'Good string'];
        $result = h($arr);
        $this->assertContains('An invalid', $result['invalid']);
        $this->assertEquals('Good string', $result['good']);

        // Test that boolean values are not converted to strings
        $result = h(false);
        $this->assertFalse($result);

        $arr = ['foo' => false, 'bar' => true];
        $result = h($arr);
        $this->assertFalse($result['foo']);
        $this->assertTrue($result['bar']);

        $obj = new \stdClass();
        $result = h($obj);
        $this->assertEquals('(object)stdClass', $result);

        $obj = new Response(['body' => 'Body content']);
        $result = h($obj);
        $this->assertEquals('Body content', $result);
    }

    /**
     * test debug()
     *
     * @return void
     */
    public function testDebug()
    {
        ob_start();
        debug('this-is-a-test', false);
        $result = ob_get_clean();
        $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'this-is-a-test'
###########################

EXPECTED;
        $expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);

        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true);
        $result = ob_get_clean();
        $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 10);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 10);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output">

<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 10);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', null);
        $result = ob_get_clean();
        $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        if (PHP_SAPI === 'cli') {
            $expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 18);
        } else {
            $expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 19);
        }
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', null, false);
        $result = ob_get_clean();
        $expectedHtml = <<<EXPECTED
<div class="cake-debug-output">

<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
        $expectedText = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        if (PHP_SAPI === 'cli') {
            $expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 18);
        } else {
            $expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 19);
        }
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', false, true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', false, false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        debug(false, false, false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED

########## DEBUG ##########
false
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);
    }

    /**
     * test pr()
     *
     * @return void
     */
    public function testPr()
    {
        ob_start();
        pr(true);
        $result = ob_get_clean();
        $expected = "\n1\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pr(false);
        $result = ob_get_clean();
        $expected = "\n\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pr(null);
        $result = ob_get_clean();
        $expected = "\n\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pr(123);
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pr('123');
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pr('this is a test');
        $result = ob_get_clean();
        $expected = "\nthis is a test\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pr(['this' => 'is', 'a' => 'test', 123 => 456]);
        $result = ob_get_clean();
        $expected = "\nArray\n(\n    [this] => is\n    [a] => test\n    [123] => 456\n)\n\n";
        $this->assertEquals($expected, $result);
    }

    /**
     * test pj()
     *
     * @return void
     */
    public function testPj()
    {
        ob_start();
        pj(true);
        $result = ob_get_clean();
        $expected = "\ntrue\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pj(false);
        $result = ob_get_clean();
        $expected = "\nfalse\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pj(null);
        $result = ob_get_clean();
        $expected = "\nnull\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pj(123);
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pj('123');
        $result = ob_get_clean();
        $expected = "\n\"123\"\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pj('this is a test');
        $result = ob_get_clean();
        $expected = "\n\"this is a test\"\n\n";
        $this->assertEquals($expected, $result);

        ob_start();
        pj(['this' => 'is', 'a' => 'test', 123 => 456]);
        $result = ob_get_clean();
        $expected = "\n{\n    \"this\": \"is\",\n    \"a\": \"test\",\n    \"123\": 456\n}\n\n";
        $this->assertEquals($expected, $result);
    }

    /**
     * Test splitting plugin names.
     *
     * @return void
     */
    public function testPluginSplit()
    {
        $result = pluginSplit('Something.else');
        $this->assertEquals(['Something', 'else'], $result);

        $result = pluginSplit('Something.else.more.dots');
        $this->assertEquals(['Something', 'else.more.dots'], $result);

        $result = pluginSplit('Somethingelse');
        $this->assertEquals([null, 'Somethingelse'], $result);

        $result = pluginSplit('Something.else', true);
        $this->assertEquals(['Something.', 'else'], $result);

        $result = pluginSplit('Something.else.more.dots', true);
        $this->assertEquals(['Something.', 'else.more.dots'], $result);

        $result = pluginSplit('Post', false, 'Blog');
        $this->assertEquals(['Blog', 'Post'], $result);

        $result = pluginSplit('Blog.Post', false, 'Ultimate');
        $this->assertEquals(['Blog', 'Post'], $result);
    }

    /**
     * test namespaceSplit
     *
     * @return void
     */
    public function testNamespaceSplit()
    {
        $result = namespaceSplit('Something');
        $this->assertEquals(['', 'Something'], $result);

        $result = namespaceSplit('\Something');
        $this->assertEquals(['', 'Something'], $result);

        $result = namespaceSplit('Cake\Something');
        $this->assertEquals(['Cake', 'Something'], $result);

        $result = namespaceSplit('Cake\Test\Something');
        $this->assertEquals(['Cake\Test', 'Something'], $result);
    }

    /**
     * Tests that the stackTrace() method is a shortcut for Debugger::trace()
     *
     * @return void
     */
    public function testStackTrace()
    {
        ob_start();
        list($r, $expected) = [stackTrace(), \Cake\Error\Debugger::trace()];
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

        $opts = ['args' => true];
        ob_start();
        list($r, $expected) = [stackTrace($opts), \Cake\Error\Debugger::trace($opts)];
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);
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
        $this->assertInstanceOf('Cake\Collection\Collection', $collection);
        $this->assertSame($items, $collection->toArray());
    }
}
