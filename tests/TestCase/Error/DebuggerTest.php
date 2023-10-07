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
use Cake\Error\Debug\ConsoleFormatter;
use Cake\Error\Debug\HtmlFormatter;
use Cake\Error\Debug\NodeInterface;
use Cake\Error\Debug\ScalarNode;
use Cake\Error\Debug\SpecialNode;
use Cake\Error\Debug\TextFormatter;
use Cake\Error\Debugger;
use Cake\Form\Form;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use MyClass;
use RuntimeException;
use SplFixedArray;
use stdClass;
use TestApp\Error\TestDebugger;
use TestApp\Error\Thing\DebuggableThing;
use TestApp\Error\Thing\SecurityThing;
use TestApp\Utility\ThrowsDebugInfo;

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
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('debug', true);
        Log::drop('stderr');
        Log::drop('stdout');
        Debugger::configInstance('exportFormatter', TextFormatter::class);
    }

    /**
     * tearDown method
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
     */
    public function testDocRef(): void
    {
        ini_set('docref_root', '');
        $this->assertEquals(ini_get('docref_root'), '');
        // Force a new instance.
        Debugger::getInstance(TestDebugger::class);
        Debugger::getInstance(Debugger::class);

        $this->assertEquals(ini_get('docref_root'), 'https://secure.php.net/');
    }

    /**
     * test Excerpt writing
     */
    public function testExcerpt(): void
    {
        $result = Debugger::excerpt(__FILE__, __LINE__ - 1, 2);
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertMatchesRegularExpression('/function(.+)testExcerpt/', $result[1]);

        $result = Debugger::excerpt(__FILE__, 2, 2);
        $this->assertIsArray($result);
        $this->assertCount(4, $result);

        $this->skipIf(defined('HHVM_VERSION'), 'HHVM does not highlight php code');
        $pattern = '/<code.*?>.*?<span style="color: #[0-9A-F]+">.*?&lt;\?php/';
        $this->assertMatchesRegularExpression($pattern, $result[0]);

        $result = Debugger::excerpt(__FILE__, 11, 2);
        $this->assertCount(5, $result);

        $pattern = '/<span style\="color\: \#\d{6}">.*?<\/span>/';
        $this->assertMatchesRegularExpression($pattern, $result[0]);

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
     * testTrimPath method
     */
    public function testTrimPath(): void
    {
        $this->assertSame('APP/', Debugger::trimPath(APP));
        $this->assertSame('CORE' . DS . 'src' . DS, Debugger::trimPath(CAKE));
        $this->assertSame('Some/Other/Path', Debugger::trimPath('Some/Other/Path'));
    }

    /**
     * testExportVar method
     */
    public function testExportVar(): void
    {
        $std = new stdClass();
        $std->int = 2;
        $std->float = 1.333;
        $std->string = '  ';

        $result = Debugger::exportVar($std);
        $expected = <<<TEXT
object(stdClass) id:0 {
  int => (int) 2
  float => (float) 1.333
  string => '  '
}
TEXT;
        $this->assertTextEquals($expected, $result);

        $Controller = new Controller(new ServerRequest());
        $Controller->viewBuilder()->setHelpers(['Html', 'Form']);
        $View = $Controller->createView();

        $result = Debugger::exportVar($View);
        $expected = <<<TEXT
object(Cake\View\View) id:0 {
  [protected] _helpers => object(Cake\View\HelperRegistry) id:1 {}
  [protected] Blocks => object(Cake\View\ViewBlock) id:2 {}
  [protected] plugin => null
  [protected] name => ''
  [protected] helpers => [
    'Html' => [
      '' => [maximum depth reached]
    ],
    'Form' => [
      '' => [maximum depth reached]
    ]
  ]
  [protected] templatePath => ''
  [protected] template => ''
  [protected] layout => 'default'
  [protected] layoutPath => ''
  [protected] autoLayout => true
  [protected] viewVars => []
  [protected] _ext => '.php'
  [protected] subDir => ''
  [protected] theme => null
  [protected] request => object(Cake\Http\ServerRequest) id:3 {}
  [protected] response => object(Cake\Http\Response) id:4 {}
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
  [protected] _current => ''
  [protected] _currentType => ''
  [protected] _stack => []
  [protected] _viewBlockClass => 'Cake\View\ViewBlock'
  [protected] _eventManager => object(Cake\Event\EventManager) id:5 {}
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
        $this->assertStringContainsString('(resource (closed)) Resource id #', $result);
    }

    public function testExportVarTypedProperty(): void
    {
        // This is gross but was simpler than adding a fixture file.
        // phpcs:ignore
        eval('class MyClass { private string $field; }');
        /** @phpstan-ignore-next-line */
        $obj = new MyClass();
        $out = Debugger::exportVar($obj);
        $this->assertTextContains('field => [uninitialized]', $out);
    }

    /**
     * Test exporting various kinds of false.
     */
    public function testExportVarZero(): void
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
     */
    public function testExportVarCyclicRef(): void
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
     * test exportVar with array objects
     */
    public function testExportVarSplFixedArray(): void
    {
        $subject = new SplFixedArray(2);
        $subject[0] = 'red';
        $subject[1] = 'blue';

        $result = Debugger::exportVar($subject, 6);
        $this->assertStringContainsString('object(SplFixedArray) id:0 {', $result);
    }

    /**
     * Tests plain text variable export.
     */
    public function testExportVarAsPlainText(): void
    {
        Debugger::configInstance('exportFormatter', null);
        $result = Debugger::exportVarAsPlainText(123);
        $this->assertSame('(int) 123', $result);

        Debugger::configInstance('exportFormatter', ConsoleFormatter::class);
        $result = Debugger::exportVarAsPlainText(123);
        $this->assertSame('(int) 123', $result);
    }

    /**
     * test exportVar with cyclic objects.
     */
    public function testExportVarDebugInfo(): void
    {
        $form = new Form();

        $result = Debugger::exportVar($form, 6);
        $this->assertStringContainsString("'_schema' => [", $result, 'Has debuginfo keys');
        $this->assertStringContainsString("'_validator' => [", $result);
    }

    /**
     * Test exportVar with an exception during __debugInfo()
     */
    public function testExportVarInvalidDebugInfo(): void
    {
        $this->skipIf(extension_loaded('xdebug'), 'Throwing exceptions inside __debugInfo breaks with xDebug');
        $result = Debugger::exportVar(new ThrowsDebugInfo());
        $expected = '(unable to export object: from __debugInfo)';
        $this->assertTextEquals($expected, $result);
    }

    /**
     * Test exportVar with a mock
     */
    public function testExportVarMockObject(): void
    {
        $result = Debugger::exportVar($this->getMockBuilder(Table::class)->getMock());
        $this->assertStringStartsWith('object(Mock', $result);
        $this->assertStringContainsString('_Table_', $result);
    }

    /**
     * Text exportVarAsNodes()
     */
    public function testExportVarAsNodes(): void
    {
        $data = [
            1 => 'Index one',
            5 => 'Index five',
        ];
        $result = Debugger::exportVarAsNodes($data);
        $this->assertInstanceOf(NodeInterface::class, $result);
        $this->assertCount(2, $result->getChildren());

        /** @var \Cake\Error\Debug\ArrayItemNode $item */
        $item = $result->getChildren()[0];
        $key = new ScalarNode('int', 1);
        $this->assertEquals($key, $item->getKey());
        $value = new ScalarNode('string', 'Index one');
        $this->assertEquals($value, $item->getValue());

        $data = [
            'key' => [
                'value',
            ],
        ];
        $result = Debugger::exportVarAsNodes($data, 1);

        $item = $result->getChildren()[0];
        $nestedItem = $item->getValue()->getChildren()[0];
        $expected = new SpecialNode('[maximum depth reached]');
        $this->assertEquals($expected, $nestedItem->getValue());
    }

    /**
     * testLog method
     */
    public function testLog(): void
    {
        Log::setConfig('test', [
            'className' => 'Array',
        ]);
        Debugger::log('cool');
        Debugger::log(['whatever', 'here']);

        $messages = Log::engine('test')->read();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('DebuggerTest->testLog', $messages[0]);
        $this->assertStringContainsString('cool', $messages[0]);

        $this->assertStringContainsString('DebuggerTest->testLog', $messages[1]);
        $this->assertStringContainsString('[main]', $messages[1]);
        $this->assertStringContainsString("'whatever'", $messages[1]);
        $this->assertStringContainsString("'here'", $messages[1]);

        Log::drop('test');
    }

    /**
     * Tests that logging does not apply formatting.
     */
    public function testLogShouldNotApplyFormatting(): void
    {
        Log::setConfig('test', [
            'className' => 'Array',
        ]);

        Debugger::configInstance('exportFormatter', null);
        Debugger::log(123);
        $messages = implode('', Log::engine('test')->read());
        Log::engine('test')->clear();
        $this->assertStringContainsString('(int) 123', $messages);
        $this->assertStringNotContainsString("\033[0m", $messages);

        Debugger::configInstance('exportFormatter', HtmlFormatter::class);
        Debugger::log(123);
        $messages = implode('', Log::engine('test')->read());
        Log::engine('test')->clear();
        $this->assertStringContainsString('(int) 123', $messages);
        $this->assertStringNotContainsString('<style', $messages);

        Debugger::configInstance('exportFormatter', ConsoleFormatter::class);
        Debugger::log(123);
        $messages = implode('', Log::engine('test')->read());
        Log::engine('test')->clear();
        $this->assertStringContainsString('(int) 123', $messages);
        $this->assertStringNotContainsString("\033[0m", $messages);

        Log::drop('test');
    }

    /**
     * test log() depth
     */
    public function testLogDepth(): void
    {
        Log::setConfig('test', [
            'className' => 'Array',
        ]);
        $veryRandomName = [
            'test' => ['key' => 'val'],
        ];
        Debugger::log($veryRandomName, 'debug', 0);

        $messages = Log::engine('test')->read();
        $this->assertStringContainsString('DebuggerTest->testLogDepth', $messages[0]);
        $this->assertStringContainsString('test', $messages[0]);
        $this->assertStringNotContainsString('veryRandomName', $messages[0]);
    }

    /**
     * testDump method
     */
    public function testDump(): void
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
     */
    public function testGetInstance(): void
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
     * Test that exportVar() will stop traversing recursive arrays.
     */
    public function testExportVarRecursion(): void
    {
        $array = [];
        $array['foo'] = &$array;

        $output = Debugger::exportVar($array);
        $this->assertMatchesRegularExpression("/'foo' => \[\s+'' \=\> \[maximum depth reached\]/", $output);
    }

    /**
     * test trace exclude
     */
    public function testTraceExclude(): void
    {
        $result = Debugger::trace();
        $this->assertMatchesRegularExpression('/^Cake\\\Test\\\TestCase\\\Error\\\DebuggerTest..testTraceExclude/m', $result);

        $result = Debugger::trace([
            'exclude' => ['Cake\Test\TestCase\Error\DebuggerTest->testTraceExclude'],
        ]);
        $this->assertDoesNotMatchRegularExpression('/^Cake\\\Test\\\TestCase\\\Error\\\DebuggerTest..testTraceExclude/m', $result);
    }

    protected function _makeException()
    {
        return new RuntimeException('testing');
    }

    /**
     * Test stack frame comparisons.
     */
    public function testGetUniqueFrames()
    {
        $parent = new RuntimeException('parent');
        $child = $this->_makeException();

        $result = Debugger::getUniqueFrames($child, $parent);
        $this->assertCount(1, $result);
        $this->assertEquals(__LINE__ - 4, $result[0]['line']);

        $result = Debugger::getUniqueFrames($child, null);
        $this->assertGreaterThan(1, count($result));
    }

    /**
     * Tests that __debugInfo is used when available
     */
    public function testDebugInfo(): void
    {
        $object = new DebuggableThing();
        $result = Debugger::exportVar($object, 2);
        $expected = <<<eos
object(TestApp\Error\Thing\DebuggableThing) id:0 {
  'foo' => 'bar'
  'inner' => object(TestApp\Error\Thing\DebuggableThing) id:1 {}
}
eos;
        $this->assertSame($expected, $result);
    }

    /**
     * Tests reading the output mask settings.
     */
    public function testSetOutputMask(): void
    {
        Debugger::setOutputMask(['password' => '[**********]']);
        $this->assertEquals(['password' => '[**********]'], Debugger::outputMask());
        Debugger::setOutputMask(['serial' => 'XXXXXX']);
        $this->assertEquals(['password' => '[**********]', 'serial' => 'XXXXXX'], Debugger::outputMask());
        Debugger::setOutputMask([], false);
        $this->assertSame([], Debugger::outputMask());
    }

    /**
     * Test configure based output mask configuration
     */
    public function testConfigureOutputMask(): void
    {
        Configure::write('Debugger.outputMask', ['wow' => 'xxx']);
        Debugger::getInstance(TestDebugger::class);
        Debugger::getInstance(Debugger::class);

        $result = Debugger::exportVar(['wow' => 'pass1234']);
        $this->assertStringContainsString('xxx', $result);
        $this->assertStringNotContainsString('pass1234', $result);
    }

    /**
     * Tests the masking of an array key.
     */
    public function testMaskArray(): void
    {
        Debugger::setOutputMask(['password' => '[**********]']);
        $result = Debugger::exportVar(['password' => 'pass1234']);
        $expected = "['password'=>'[**********]']";
        $this->assertSame($expected, preg_replace('/\s+/', '', $result));
    }

    /**
     * Tests the masking of an array key.
     */
    public function testMaskObject(): void
    {
        Debugger::setOutputMask(['password' => '[**********]']);
        $object = new SecurityThing();
        $result = Debugger::exportVar($object);
        $expected = "object(TestApp\\Error\\Thing\\SecurityThing)id:0{password=>'[**********]'}";
        $this->assertSame($expected, preg_replace('/\s+/', '', $result));
    }

    /**
     * test testPrintVar()
     */
    public function testPrintVar(): void
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

        $this->assertSame($expected, $result);

        ob_start();
        $value = '<div>this-is-a-test</div>';
        Debugger::printVar($value, ['file' => __FILE__, 'line' => __LINE__], true);
        $result = ob_get_clean();
        $this->assertStringContainsString('&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;', $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>', ['file' => __FILE__, 'line' => __LINE__], true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output cake-debug" style="direction:ltr">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<div class="cake-debug"><span class="cake-debug-string">&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;</span></div>
</div>
EXPECTED;
        $expected = sprintf($expected, Debugger::trimPath(__FILE__), __LINE__ - 8);
        $this->assertSame($expected, $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>', [], true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output cake-debug" style="direction:ltr">

<div class="cake-debug"><span class="cake-debug-string">&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;</span></div>
</div>
EXPECTED;
        $this->assertSame($expected, $result);

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
        $this->assertSame($expected, $result);

        ob_start();
        Debugger::printVar('<div>this-is-a-test</div>');
        $result = ob_get_clean();
        $expected = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
        $this->assertSame($expected, $result);
    }

    /**
     * test formatHtmlMessage
     */
    public function testFormatHtmlMessage(): void
    {
        $output = Debugger::formatHtmlMessage('Some `code` to `replace`');
        $this->assertSame('Some <code>`code`</code> to <code>`replace`</code>', $output);

        $output = Debugger::formatHtmlMessage("Some `co\nde` to `replace`\nmore");
        $this->assertSame("Some <code>`co<br />\nde`</code> to <code>`replace`</code><br />\nmore", $output);

        $output = Debugger::formatHtmlMessage("Some `code` to <script>alert(\"test\")</script>\nmore");
        $this->assertSame(
            "Some <code>`code`</code> to &lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;<br />\nmore",
            $output
        );
    }

    /**
     * test choosing an unknown editor
     */
    public function testSetEditorInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Debugger::setEditor('nope');
    }

    /**
     * test choosing a default editor
     */
    public function testSetEditorPredefined(): void
    {
        Debugger::setEditor('phpstorm');
        Debugger::setEditor('macvim');
        Debugger::setEditor('sublime');
        Debugger::setEditor('emacs');
        // No exceptions raised.
        $this->assertTrue(true);
    }

    /**
     * Test configure based editor setup
     */
    public function testConfigureEditor(): void
    {
        Configure::write('Debugger.editor', 'emacs');
        Debugger::getInstance(TestDebugger::class);
        Debugger::getInstance(Debugger::class);

        $result = Debugger::editorUrl('file.php', 123);
        $this->assertStringContainsString('emacs://', $result);
    }

    /**
     * test using a valid editor.
     */
    public function testEditorUrlValid(): void
    {
        Debugger::addEditor('open', 'open://{file}:{line}');
        Debugger::setEditor('open');
        $this->assertSame('open://test.php:123', Debugger::editorUrl('test.php', 123));
    }

    /**
     * test using a valid editor.
     */
    public function testEditorUrlClosure(): void
    {
        Debugger::addEditor('open', function (string $file, int $line) {
            return "{$file}/{$line}";
        });
        Debugger::setEditor('open');
        $this->assertSame('test.php/123', Debugger::editorUrl('test.php', 123));
    }
}
