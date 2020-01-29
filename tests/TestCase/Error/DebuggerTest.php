<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Error\Debugger;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use stdClass;
use TestApp\Error\TestDebugger;
use TestApp\Error\Thing\DebuggableThing;
use TestApp\Error\Thing\SecurityThing;

/**
 * DebuggerTest class
 *
 * !!! Be careful with changing code below as it may
 * !!! change line numbers which are used in the tests
 */
class DebuggerTest extends TestCase
{
    /**
     * @var bool
     */
    protected $restoreError = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
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
    public function tearDown(): void
    {
        parent::tearDown();
        if ($this->restoreError) {
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
        $this->assertEquals(ini_get('docref_root'), 'https://secure.php.net/');
    }

    /**
     * test Excerpt writing
     *
     * @return void
     */
    public function testExcerpt()
    {
        $result = Debugger::excerpt(__FILE__, __LINE__ - 1, 2);
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertRegExp('/function(.+)testExcerpt/', $result[1]);

        $result = Debugger::excerpt(__FILE__, 2, 2);
        $this->assertIsArray($result);
        $this->assertCount(4, $result);

        $this->skipIf(defined('HHVM_VERSION'), 'HHVM does not highlight php code');
        $pattern = '/<code>.*?<span style\="color\: \#\d+">.*?&lt;\?php/';
        $this->assertRegExp($pattern, $result[0]);

        $result = Debugger::excerpt(__FILE__, 11, 2);
        $this->assertCount(5, $result);

        $pattern = '/<span style\="color\: \#\d{6}">.*?<\/span>/';
        $this->assertRegExp($pattern, $result[0]);

        $return = Debugger::excerpt('[internal]', 2, 2);
        $this->assertEmpty($return);

        $result = Debugger::excerpt(__FILE__, __LINE__, 5);
        $this->assertCount(11, $result);
        $this->assertStringContainsString('Debugger', $result[5]);
        $this->assertStringContainsString('excerpt', $result[5]);
        $this->assertStringContainsString('__FILE__', $result[5]);

        $result = Debugger::excerpt(__FILE__, 1, 2);
        $this->assertCount(3, $result);

        $lastLine = count(explode("\n", file_get_contents(__FILE__)));
        $result = Debugger::excerpt(__FILE__, $lastLine, 2);
        $this->assertCount(3, $result);
    }

    /**
     * Test that setOutputFormat works.
     *
     * @return void
     */
    public function testSetOutputFormat()
    {
        Debugger::setOutputFormat('html');
        $this->assertSame('html', Debugger::getOutputFormat());
    }

    /**
     * Test that getOutputFormat/setOutputFormat works.
     *
     * @return void
     */
    public function testGetSetOutputFormat()
    {
        Debugger::setOutputFormat('html');
        $this->assertSame('html', Debugger::getOutputFormat());
    }

    /**
     * Test that choosing a non-existent format causes an exception
     *
     * @return void
     */
    public function testSetOutputAsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        Debugger::setOutputFormat('Invalid junk');
    }

    /**
     * Test outputError with description encoding
     *
     * @return void
     */
    public function testOutputErrorDescriptionEncoding()
    {
        Debugger::setOutputFormat('html');

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
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Tests that the correct line is being highlighted.
     *
     * @return void
     */
    public function testOutputErrorLineHighlight()
    {
        Debugger::setOutputFormat('js');

        ob_start();
        $debugger = Debugger::getInstance();
        $data = [
            'level' => E_NOTICE,
            'code' => E_NOTICE,
            'file' => __FILE__,
            'line' => __LINE__,
            'description' => 'Error description',
            'start' => 1,
        ];
        $debugger->outputError($data);
        $result = ob_get_clean();

        $this->assertRegExp('#^\<span class\="code\-highlight"\>.*outputError.*\</span\>$#m', $result);
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
                '&line={:line}">{:path}</a>, line {:line}',
        ]);
        Debugger::setOutputFormat('js');

        $result = Debugger::trace();
        $this->assertRegExp('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

        Debugger::addFormat('xml', [
            'error' => '<error><code>{:code}</code><file>{:file}</file><line>{:line}</line>' .
                '{:description}</error>',
        ]);
        Debugger::setOutputFormat('xml');

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
            '/error',
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
        Debugger::setOutputFormat('callback');

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
        $this->assertStringContainsString('Notice: I eated an error', $result);
        $this->assertStringContainsString('DebuggerTest.php', $result);

        Debugger::setOutputFormat('js');
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
        $this->assertSame('APP/', Debugger::trimPath(APP));
        $this->assertSame('CORE' . DS . 'src' . DS, Debugger::trimPath(CAKE));
        $this->assertSame('Some/Other/Path', Debugger::trimPath('Some/Other/Path'));
    }

    /**
     * testExportVar method
     *
     * @return void
     */
    public function testExportVar()
    {
        $Controller = new Controller();
        $Controller->viewBuilder()->setHelpers(['Html', 'Form']);
        $View = $Controller->createView();
        $View->int = 2;
        $View->float = 1.333;
        $View->string = '  ';

        $result = Debugger::exportVar($View);
        $expected = <<<TEXT
object(Cake\View\View) id:0 {
  Html => object(Cake\View\Helper\HtmlHelper) id:1 {}
  Form => object(Cake\View\Helper\FormHelper) id:2 {}
  int => (int) 2
  float => (float) 1.333
  string => '  '
  [protected] _helpers => object(Cake\View\HelperRegistry) id:3 {}
  [protected] Blocks => object(Cake\View\ViewBlock) id:4 {}
  [protected] plugin => null
  [protected] name => ''
  [protected] helpers => [
    (int) 0 => 'Html',
    (int) 1 => 'Form'
  ]
  [protected] templatePath => null
  [protected] template => null
  [protected] layout => 'default'
  [protected] layoutPath => ''
  [protected] autoLayout => true
  [protected] viewVars => []
  [protected] _ext => '.php'
  [protected] subDir => ''
  [protected] theme => null
  [protected] request => object(Cake\Http\ServerRequest) id:5 {}
  [protected] response => object(Cake\Http\Response) id:6 {}
  [protected] elementCache => 'default'
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
    (int) 9 => 'plugin'
  ]
  [protected] _defaultConfig => []
  [protected] _paths => []
  [protected] _pathsForPlugin => []
  [protected] _parents => []
  [protected] _current => null
  [protected] _currentType => ''
  [protected] _stack => []
  [protected] _viewBlockClass => 'Cake\View\ViewBlock'
  [protected] _eventManager => object(Cake\Event\EventManager) id:7 {}
  [protected] _eventClass => 'Cake\Event\Event'
  [protected] _config => []
  [protected] _configInitialized => true
}
TEXT;
        $this->assertTextEquals($expected, $result);

        $data = [
            1 => 'Index one',
            5 => 'Index five',
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
                'value',
            ],
        ];
        $result = Debugger::exportVar($data, 1);
        $expected = <<<TEXT
[
  'key' => [
    '' => [maximum depth reached]
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
        $this->assertTextEquals('(unknown)', $result);
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
            'zero' => 0,
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
     * test exportVar with cyclic objects.
     *
     * @return void
     */
    public function testExportVarCyclicRef()
    {
        $parent = new stdClass();
        $parent->name = 'cake';
        $middle = new stdClass();
        $parent->child = $middle;

        $middle->name = 'php';
        $middle->child = $parent;

        $result = Debugger::exportVar($parent, 6);
        $expected = <<<TEXT
object(stdClass) id:0 {
  name => 'cake'
  child => object(stdClass) id:1 {
    name => 'php'
    child => object(stdClass) id:0 {}
  }
}
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
        Log::setConfig('test', [
            'className' => 'Array',
        ]);
        Debugger::log('cool');
        Debugger::log(['whatever', 'here']);

        $messages = Log::engine('test')->read();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('DebuggerTest::testLog', $messages[0]);
        $this->assertStringContainsString('cool', $messages[0]);

        $this->assertStringContainsString('DebuggerTest::testLog', $messages[1]);
        $this->assertStringContainsString('[main]', $messages[1]);
        $this->assertStringContainsString("'whatever'", $messages[1]);
        $this->assertStringContainsString("'here'", $messages[1]);

        Log::drop('test');
    }

    /**
     * test log() depth
     *
     * @return void
     */
    public function testLogDepth()
    {
        Log::setConfig('test', [
            'className' => 'Array',
        ]);
        $val = [
            'test' => ['key' => 'val'],
        ];
        Debugger::log($val, 'debug', 0);

        $messages = Log::engine('test')->read();
        $this->assertStringContainsString('DebuggerTest::testLogDepth', $messages[0]);
        $this->assertStringContainsString('test', $messages[0]);
        $this->assertStringNotContainsString('val', $messages[0]);
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
                'hair_color' => 'brown',
            ],
            [
                'name' => 'Shaft',
                'coat' => 'black',
                'hair' => 'black',
            ],
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
    '' => [maximum depth reached]
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
        $exporter = $result->getConfig('exportFormatter');

        $this->assertInstanceOf(Debugger::class, $result);

        $result = Debugger::getInstance(TestDebugger::class);
        $this->assertInstanceOf(TestDebugger::class, $result);

        $result = Debugger::getInstance();
        $this->assertInstanceOf(TestDebugger::class, $result);

        $result = Debugger::getInstance(Debugger::class);
        $this->assertInstanceOf(Debugger::class, $result);
        $result->setConfig('exportFormatter', $exporter);
    }

    /**
     * Test that exportVar() will stop traversing recursive arrays like GLOBALS.
     *
     * @return void
     */
    public function testExportVarRecursion()
    {
        $output = Debugger::exportVar($GLOBALS);
        $this->assertRegExp("/'GLOBALS' => \[\s+'' \=\> \[maximum depth reached\]/", $output);
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
            'exclude' => ['Cake\Test\TestCase\Error\DebuggerTest::testTraceExclude'],
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
object(TestApp\Error\Thing\DebuggableThing) id:0 {
  'foo' => 'bar'
  'inner' => object(TestApp\Error\Thing\DebuggableThing) id:1 {}
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
        $expected = "['password'=>'[**********]']";
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
        $expected = "object(TestApp\\Error\\Thing\\SecurityThing)id:0{password=>'[**********]'}";
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
        $expected = sprintf($expectedText, Debugger::trimPath(__FILE__), __LINE__ - 9);

        $this->assertEquals($expected, $result);

        ob_start();
        $value = '<div>this-is-a-test</div>';
        Debugger::printVar($value, ['file' => __FILE__, 'line' => __LINE__], true);
        $result = ob_get_clean();
        $expectedHtml = <<<EXPECTED
<div class="cake-debug-output cake-debug" style="direction:ltr">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<div class="cake-dbg"><span class="cake-dbg-string">&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;</span></div>
</div>
EXPECTED;
        $expected = sprintf($expectedHtml, Debugger::trimPath(__FILE__), __LINE__ - 8);
        $this->assertEquals($expected, $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>', ['file' => __FILE__, 'line' => __LINE__], true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output cake-debug" style="direction:ltr">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<div class="cake-dbg"><span class="cake-dbg-string">&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;</span></div>
</div>
EXPECTED;
        $expected = sprintf($expected, Debugger::trimPath(__FILE__), __LINE__ - 8);
        $this->assertEquals($expected, $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>', [], true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output cake-debug" style="direction:ltr">

<div class="cake-dbg"><span class="cake-dbg-string">&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;</span></div>
</div>
EXPECTED;
        $expected = sprintf($expected, Debugger::trimPath(__FILE__), __LINE__ - 8);
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
        $expected = sprintf($expected, Debugger::trimPath(__FILE__), __LINE__ - 9);
        $this->assertEquals($expected, $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>');
        $result = ob_get_clean();
        $expected = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $expected = sprintf($expected, Debugger::trimPath(__FILE__), __LINE__ - 8);
        $this->assertEquals($expected, $result);
    }

    /**
     * test formatHtmlMessage
     *
     * @return void
     */
    public function testFormatHtmlMessage()
    {
        $output = Debugger::formatHtmlMessage('Some `code` to `replace`');
        $this->assertSame('Some <code>code</code> to <code>replace</code>', $output);

        $output = Debugger::formatHtmlMessage("Some `co\nde` to `replace`\nmore");
        $this->assertSame("Some <code>co<br />\nde</code> to <code>replace</code><br />\nmore", $output);

        $output = Debugger::formatHtmlMessage("Some `code` to <script>alert(\"test\")</script>\nmore");
        $this->assertSame(
            "Some <code>code</code> to &lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;<br />\nmore",
            $output
        );
    }
}
