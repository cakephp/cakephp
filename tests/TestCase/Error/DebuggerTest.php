<?php
/**
* CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
* Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
*
* Licensed under The MIT License
* For full copyright and license information, please see the LICENSE.txt
* Redistributions of files must retain the above copyright notice.
*
* @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
* @link          http://cakephp.org CakePHP Project
* @since         1.2.0
* @license       http://www.opensource.org/licenses/mit-license.php MIT License
*/
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Error\Debugger;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

/**
* DebuggerTestCaseDebugger class
*/
class DebuggerTestCaseDebugger extends Debugger
{
}

class DebuggableThing
{

    public function __debugInfo()
    {
        return ['foo' => 'bar', 'inner' => new self()];
    }
}

class SecurityThing
{
    public $password = 'pass1234';
}

/**
 * DebuggerTest class
 *
 * !!! Be careful with changing code below as it may
 * !!! change line numbers which are used in the tests
 */
class DebuggerTest extends TestCase
{

    protected $_restoreError = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('debug', true);
        Log::drop('stderr');
        Log::drop('stdout');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        if ($this->_restoreError) {
            restore_error_handler();
        }
    }

    /**
     * testDocRef method
     *
     * @return void
     */
    public function testDocRef()
    {
        $this->skipIf(
            defined('HHVM_VERSION'),
            'HHVM does not output doc references'
        );
        ini_set('docref_root', '');
        $this->assertEquals(ini_get('docref_root'), '');
        new Debugger();
        $this->assertEquals(ini_get('docref_root'), 'http://php.net/');
    }

    /**
     * test Excerpt writing
     *
     * @return void
     */
    public function testExcerpt()
    {
        $result = Debugger::excerpt(__FILE__, __LINE__ - 1, 2);
        $this->assertTrue(is_array($result));
        $this->assertCount(5, $result);
        $this->assertRegExp('/function(.+)testExcerpt/', $result[1]);

        $result = Debugger::excerpt(__FILE__, 2, 2);
        $this->assertTrue(is_array($result));
        $this->assertCount(4, $result);

        $this->skipIf(defined('HHVM_VERSION'), 'HHVM does not highlight php code');
        $pattern = '/<code>.*?<span style\="color\: \#\d+">.*?&lt;\?php/';
        $this->assertRegExp($pattern, $result[0]);

        $result = Debugger::excerpt(__FILE__, 11, 2);
        $this->assertCount(5, $result);

        $pattern = '/<span style\="color\: \#\d{6}">\*<\/span>/';
        $this->assertRegExp($pattern, $result[0]);

        $return = Debugger::excerpt('[internal]', 2, 2);
        $this->assertTrue(empty($return));

        $result = Debugger::excerpt(__FILE__, __LINE__, 5);
        $this->assertCount(11, $result);
        $this->assertContains('Debugger', $result[5]);
        $this->assertContains('excerpt', $result[5]);
        $this->assertContains('__FILE__', $result[5]);
    }

    /**
     * Test that outputAs works.
     *
     * @return void
     */
    public function testOutputAs()
    {
        Debugger::outputAs('html');
        $this->assertEquals('html', Debugger::outputAs());
    }

    /**
     * Test that choosing a non-existent format causes an exception
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testOutputAsException()
    {
        Debugger::outputAs('Invalid junk');
    }

    /**
     * Test outputError with description encoding
     *
     * @return void
     */
    public function testOutputErrorDescriptionEncoding()
    {
        Debugger::outputAs('html');

        ob_start();
        $debugger = Debugger::getInstance();
        $debugger->outputError([
            'error' => 'Notice',
            'code' => E_NOTICE,
            'level' => E_NOTICE,
            'description' => 'Undefined index <script>alert(1)</script>',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);
        $result = ob_get_clean();
        $this->assertContains('&lt;script&gt;', $result);
        $this->assertNotContains('<script>', $result);
    }

    /**
     * Tests that changes in output formats using Debugger::output() change the templates used.
     *
     * @return void
     */
    public function testAddFormat()
    {
        Debugger::addFormat('js', [
            'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
                '&line={:line}">{:path}</a>, line {:line}'
        ]);
        Debugger::outputAs('js');

        $result = Debugger::trace();
        $this->assertRegExp('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

        Debugger::addFormat('xml', [
            'error' => '<error><code>{:code}</code><file>{:file}</file><line>{:line}</line>' .
                '{:description}</error>',
        ]);
        Debugger::outputAs('xml');

        ob_start();
        $debugger = Debugger::getInstance();
        $debugger->outputError([
            'level' => E_NOTICE,
            'code' => E_NOTICE,
            'file' => __FILE__,
            'line' => __LINE__,
            'description' => 'Undefined variable: foo',
        ]);
        $result = ob_get_clean();

        $expected = [
            '<error',
            '<code', '8', '/code',
            '<file', 'preg:/[^<]+/', '/file',
            '<line', '' . ((int)__LINE__ - 9), '/line',
            'preg:/Undefined variable:\s+foo/',
            '/error'
        ];
        $this->assertHtml($expected, $result, true);
    }

    /**
     * Test adding a format that is handled by a callback.
     *
     * @return void
     */
    public function testAddFormatCallback()
    {
        Debugger::addFormat('callback', ['callback' => [$this, 'customFormat']]);
        Debugger::outputAs('callback');

        ob_start();
        $debugger = Debugger::getInstance();
        $debugger->outputError([
            'error' => 'Notice',
            'code' => E_NOTICE,
            'level' => E_NOTICE,
            'description' => 'Undefined variable $foo',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);
        $result = ob_get_clean();
        $this->assertContains('Notice: I eated an error', $result);
        $this->assertContains('DebuggerTest.php', $result);
    }

    /**
     * Test method for testing addFormat with callbacks.
     *
     * @return void
     */
    public function customFormat($error, $strings)
    {
        echo $error['error'] . ': I eated an error ' . $error['file'];
    }

    /**
     * testTrimPath method
     *
     * @return void
     */
    public function testTrimPath()
    {
        $this->assertEquals('APP/', Debugger::trimPath(APP));
        $this->assertEquals('CORE' . DS . 'src' . DS, Debugger::trimPath(CAKE));
        $this->assertEquals('Some/Other/Path', Debugger::trimPath('Some/Other/Path'));
    }

    /**
     * testExportVar method
     *
     * @return void
     */
    public function testExportVar()
    {
        $Controller = new Controller();
        $Controller->helpers = ['Html', 'Form'];
        $View = $Controller->createView();
        $View->int = 2;
        $View->float = 1.333;

        $result = Debugger::exportVar($View);
        $expected = <<<TEXT
object(Cake\View\View) {
	Blocks => object(Cake\View\ViewBlock) {}
	plugin => null
	name => ''
	passedArgs => []
	helpers => [
		(int) 0 => 'Html',
		(int) 1 => 'Form'
	]
	templatePath => null
	template => null
	layout => 'default'
	layoutPath => null
	autoLayout => true
	subDir => null
	theme => null
	hasRendered => false
	uuids => []
	request => object(Cake\Http\ServerRequest) {}
	response => object(Cake\Http\Response) {}
	elementCache => 'default'
	viewClass => null
	viewVars => []
	Html => object(Cake\View\Helper\HtmlHelper) {}
	Form => object(Cake\View\Helper\FormHelper) {}
	int => (int) 2
	float => (float) 1.333
	[protected] _helpers => object(Cake\View\HelperRegistry) {}
	[protected] _ext => '.ctp'
	[protected] _passedVars => [
		(int) 0 => 'viewVars',
		(int) 1 => 'autoLayout',
		(int) 2 => 'helpers',
		(int) 3 => 'template',
		(int) 4 => 'layout',
		(int) 5 => 'name',
		(int) 6 => 'theme',
		(int) 7 => 'layoutPath',
		(int) 8 => 'templatePath',
		(int) 9 => 'plugin',
		(int) 10 => 'passedArgs'
	]
	[protected] _paths => []
	[protected] _pathsForPlugin => []
	[protected] _parents => []
	[protected] _current => null
	[protected] _currentType => ''
	[protected] _stack => []
	[protected] _eventManager => object(Cake\Event\EventManager) {}
	[protected] _eventClass => '\Cake\Event\Event'
	[protected] _viewBuilder => null
}
TEXT;

        $this->assertTextEquals($expected, $result);

        $data = [
            1 => 'Index one',
            5 => 'Index five'
        ];
        $result = Debugger::exportVar($data);
        $expected = <<<TEXT
[
	(int) 1 => 'Index one',
	(int) 5 => 'Index five'
]
TEXT;
        $this->assertTextEquals($expected, $result);

        $data = [
            'key' => [
                'value'
            ]
        ];
        $result = Debugger::exportVar($data, 1);
        $expected = <<<TEXT
[
	'key' => [
		[maximum depth reached]
	]
]
TEXT;
        $this->assertTextEquals($expected, $result);

        $data = false;
        $result = Debugger::exportVar($data);
        $expected = <<<TEXT
false
TEXT;
        $this->assertTextEquals($expected, $result);

        $file = fopen('php://output', 'w');
        fclose($file);
        $result = Debugger::exportVar($file);
        $this->assertTextEquals('unknown', $result);
    }

    /**
     * Test exporting various kinds of false.
     *
     * @return void
     */
    public function testExportVarZero()
    {
        $data = [
            'nothing' => '',
            'null' => null,
            'false' => false,
            'szero' => '0',
            'zero' => 0
        ];
        $result = Debugger::exportVar($data);
        $expected = <<<TEXT
[
	'nothing' => '',
	'null' => null,
	'false' => false,
	'szero' => '0',
	'zero' => (int) 0
]
TEXT;
        $this->assertTextEquals($expected, $result);
    }

    /**
     * testLog method
     *
     * @return void
     */
    public function testLog()
    {
        $mock = $this->getMockBuilder('Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->getMock();
        Log::config('test', ['engine' => $mock]);

        $mock->expects($this->at(0))
            ->method('log')
            ->with('debug', $this->logicalAnd(
                $this->stringContains('DebuggerTest::testLog'),
                $this->stringContains('cool')
            ));

        $mock->expects($this->at(1))
            ->method('log')
            ->with('debug', $this->logicalAnd(
                $this->stringContains('DebuggerTest::testLog'),
                $this->stringContains('[main]'),
                $this->stringContains("'whatever',"),
                $this->stringContains("'here'")
            ));

        Debugger::log('cool');
        Debugger::log(['whatever', 'here']);

        Log::drop('test');
    }

    /**
     * test log() depth
     *
     * @return void
     */
    public function testLogDepth()
    {
        $mock = $this->getMockBuilder('Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->getMock();
        Log::config('test', ['engine' => $mock]);

        $mock->expects($this->at(0))
            ->method('log')
            ->with('debug', $this->logicalAnd(
                $this->stringContains('DebuggerTest::testLog'),
                $this->stringContains('test'),
                $this->logicalNot($this->stringContains('val'))
            ));

        $val = [
            'test' => ['key' => 'val']
        ];
        Debugger::log($val, 'debug', 0);
    }

    /**
     * testDump method
     *
     * @return void
     */
    public function testDump()
    {
        $var = ['People' => [
            [
                'name' => 'joeseph',
                'coat' => 'technicolor',
                'hair_color' => 'brown'
            ],
            [
                'name' => 'Shaft',
                'coat' => 'black',
                'hair' => 'black'
            ]
        ]];
        ob_start();
        Debugger::dump($var);
        $result = ob_get_clean();

        $open = "\n";
        $close = "\n\n";
        $expected = <<<TEXT
{$open}[
	'People' => [
		(int) 0 => [
			'name' => 'joeseph',
			'coat' => 'technicolor',
			'hair_color' => 'brown'
		],
		(int) 1 => [
			'name' => 'Shaft',
			'coat' => 'black',
			'hair' => 'black'
		]
	]
]{$close}
TEXT;
        $this->assertTextEquals($expected, $result);

        ob_start();
        Debugger::dump($var, 1);
        $result = ob_get_clean();

        $expected = <<<TEXT
{$open}[
	'People' => [
		[maximum depth reached]
	]
]{$close}
TEXT;
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test getInstance.
     *
     * @return void
     */
    public function testGetInstance()
    {
        $result = Debugger::getInstance();
        $this->assertInstanceOf('Cake\Error\Debugger', $result);

        $result = Debugger::getInstance(__NAMESPACE__ . '\DebuggerTestCaseDebugger');
        $this->assertInstanceOf(__NAMESPACE__ . '\DebuggerTestCaseDebugger', $result);

        $result = Debugger::getInstance();
        $this->assertInstanceOf(__NAMESPACE__ . '\DebuggerTestCaseDebugger', $result);

        $result = Debugger::getInstance('Cake\Error\Debugger');
        $this->assertInstanceOf('Cake\Error\Debugger', $result);
    }

    /**
     * Test that exportVar() doesn't loop through recursive structures.
     *
     * @return void
     */
    public function testExportVarRecursion()
    {
        $output = Debugger::exportVar($GLOBALS);
        $this->assertContains("'GLOBALS' => [recursion]", $output);
    }

    /**
     * test trace exclude
     *
     * @return void
     */
    public function testTraceExclude()
    {
        $result = Debugger::trace();
        $this->assertRegExp('/^Cake\\\Test\\\TestCase\\\Error\\\DebuggerTest::testTraceExclude/', $result);

        $result = Debugger::trace([
            'exclude' => ['Cake\Test\TestCase\Error\DebuggerTest::testTraceExclude']
        ]);
        $this->assertNotRegExp('/^Cake\\\Test\\\TestCase\\\Error\\\DebuggerTest::testTraceExclude/', $result);
    }

    /**
     * Tests that __debugInfo is used when available
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $object = new DebuggableThing();
        $result = Debugger::exportVar($object, 2);
        $expected = <<<eos
object(Cake\Test\TestCase\Error\DebuggableThing) {

	'foo' => 'bar',
	'inner' => object(Cake\Test\TestCase\Error\DebuggableThing) {}

}
eos;
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests reading the output mask settings.
     *
     * @return void
     */
    public function testSetOutputMask()
    {
        Debugger::setOutputMask(['password' => '[**********]']);
        $this->assertEquals(['password' => '[**********]'], Debugger::outputMask());
        Debugger::setOutputMask(['serial' => 'XXXXXX']);
        $this->assertEquals(['password' => '[**********]', 'serial' => 'XXXXXX'], Debugger::outputMask());
        Debugger::setOutputMask([], false);
        $this->assertEquals([], Debugger::outputMask());
    }

    /**
     * Tests the masking of an array key.
     *
     * @return void
     */
    public function testMaskArray()
    {
        Debugger::setOutputMask(['password' => '[**********]']);
        $result = Debugger::exportVar(['password' => 'pass1234']);
        $expected = "['password'=>[**********]]";
        $this->assertEquals($expected, preg_replace('/\s+/', '', $result));
    }

    /**
     * Tests the masking of an array key.
     *
     * @return void
     */
    public function testMaskObject()
    {
        Debugger::setOutputMask(['password' => '[**********]']);
        $object = new SecurityThing();
        $result = Debugger::exportVar($object);
        $expected = "object(Cake\\Test\\TestCase\\Error\\SecurityThing){password=>[**********]}";
        $this->assertEquals($expected, preg_replace('/\s+/', '', $result));
    }

    /**
     * test testPrintVar()
     *
     * @return void
     */
    public function testPrintVar()
    {
        ob_start();
        Debugger::printVar('this-is-a-test', ['file' => __FILE__, 'line' => __LINE__], false);
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
        $value = '<div>this-is-a-test</div>';
        Debugger::printVar($value, ['file' => __FILE__, 'line' => __LINE__], true);
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
        Debugger::printVar('<div>this-is-a-test</div>', ['file' => __FILE__, 'line' => __LINE__], true);
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
        Debugger::printVar('<div>this-is-a-test</div>', [], true);
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
        Debugger::printVar('<div>this-is-a-test</div>', ['file' => __FILE__, 'line' => __LINE__]);
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
        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
            $expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 18);
        } else {
            $expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 19);
        }
        $this->assertEquals($expected, $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>');
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
        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
            $expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 18);
        } else {
            $expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 19);
        }
        $this->assertEquals($expected, $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>', ['file' => __FILE__, 'line' => __LINE__], false);
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
        Debugger::printVar('<div>this-is-a-test</div>', ['file' => __FILE__, 'line' => __LINE__], false);
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
        Debugger::printVar('<div>this-is-a-test</div>', [], false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        Debugger::printVar(false, [], false);
        $result = ob_get_clean();
        $expected = <<<EXPECTED

########## DEBUG ##########
false
###########################

EXPECTED;
        $expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);
    }
}
