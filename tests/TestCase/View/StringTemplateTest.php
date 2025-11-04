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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use InvalidArgumentException;
use stdClass;

class StringTemplateTest extends TestCase
{
    /**
     * @var \Cake\View\StringTemplate
     */
    protected $template;

    /**
     * setUp
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->template = new StringTemplate();
    }

    /**
     * Test adding templates through the constructor.
     */
    public function testConstructorAdd(): void
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>',
        ];
        $template = new StringTemplate($templates);
        $this->assertSame($templates['link'], $template->get('link'));
    }

    /**
     * test adding templates.
     */
    public function testAdd(): void
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>',
        ];
        $result = $this->template->add($templates);
        $this->assertSame(
            $this->template,
            $result,
            'The same instance should be returned',
        );

        $this->assertSame($templates['link'], $this->template->get('link'));
    }

    /**
     * test adding a template config with a null value
     */
    public function testAddWithInvalidTemplate(): void
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>',
            'invalid' => null,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->template->add($templates);
    }

    /**
     * Test remove.
     */
    public function testRemove(): void
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>',
        ];
        $this->template->add($templates);
        $this->template->remove('link');
        $this->assertNull($this->template->get('link'), 'Template should be gone.');
    }

    /**
     * Test formatting strings.
     */
    public function testFormat(): void
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>',
            'text' => '{{text}}',
            'custom' => '<custom {{standard}} v1="{{var1}}" v2="{{var2}}" />',
        ];
        $this->template->add($templates);

        $result = $this->template->format('text', ['text' => '']);
        $this->assertSame('', $result);

        $result = $this->template->format('text', []);
        $this->assertSame('', $result);

        $result = $this->template->format('link', [
            'url' => '/',
            'text' => 'example',
        ]);
        $this->assertSame('<a href="/">example</a>', $result);

        $result = $this->template->format('custom', [
            'standard' => 'default',
            'templateVars' => ['var1' => 'foo'],
        ]);
        $this->assertSame('<custom default v1="foo" v2="" />', $result);
    }

    /**
     * Test formatting strings with URL encoding
     */
    public function testFormatUrlEncoding(): void
    {
        $templates = [
            'test' => '<img src="/img/foo%20bar.jpg">{{text}}',
        ];
        $this->template->add($templates);

        $result = $this->template->format('test', ['text' => 'stuff!']);
        $this->assertSame('<img src="/img/foo%20bar.jpg">stuff!', $result);
    }

    /**
     * Formatting array data should not trigger errors.
     */
    public function testFormatArrayData(): void
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>',
        ];
        $this->template->add($templates);

        $result = $this->template->format('link', [
            'url' => '/',
            'text' => ['example', 'text'],
        ]);
        $this->assertSame('<a href="/">exampletext</a>', $result);

        $result = $this->template->format('link', [
            'url' => '/',
            'text' => ['key' => 'example', 'text'],
        ]);
        $this->assertSame('<a href="/">exampletext</a>', $result);
    }

    /**
     * Test formatting a missing template.
     */
    public function testFormatMissingTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find template named `missing`.');
        $templates = [
            'text' => '{{text}}',
        ];
        $this->template->add($templates);
        $this->template->format('missing', ['text' => 'missing']);
    }

    /**
     * Test loading templates files in the app.
     */
    public function testLoad(): void
    {
        $this->template->remove('attribute');
        $this->template->remove('compactAttribute');
        $this->assertEquals([], $this->template->get());
        $this->template->load('test_templates');
        $this->assertSame('<a href="{{url}}">{{text}}</a>', $this->template->get('link'));
    }

    /**
     * Test loading templates files from a plugin
     */
    public function testLoadPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $this->template->load('TestPlugin.test_templates');
        $this->assertSame('<em>{{text}}</em>', $this->template->get('italic'));
        $this->clearPlugins();
    }

    /**
     * Test that loading nonexistent templates causes errors.
     */
    public function testLoadErrorNoFile(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Could not load configuration file');
        $this->template->load('no_such_file');
    }

    /**
     * Test formatting compact attributes.
     */
    public function testFormatAttributesCompact(): void
    {
        $attrs = ['disabled' => true, 'selected' => 1, 'checked' => '1', 'multiple' => 'multiple'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            ' disabled="disabled" selected="selected" checked="checked" multiple="multiple"',
            $result,
        );

        $attrs = ['disabled' => false, 'selected' => 0, 'checked' => '0', 'multiple' => null];
        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            '',
            $result,
        );
    }

    /**
     * Test formatting normal attributes.
     */
    public function testFormatAttributes(): void
    {
        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>', 'spellcheck' => 'true'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            ' name="bruce" data-hero="&lt;batman&gt;" spellcheck="true"',
            $result,
        );

        $attrs = ['escape' => false, 'name' => 'bruce', 'data-hero' => '<batman>'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            ' name="bruce" data-hero="<batman>"',
            $result,
        );

        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>'];
        $result = $this->template->formatAttributes($attrs, ['name']);
        $this->assertSame(
            ' data-hero="&lt;batman&gt;"',
            $result,
        );

        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>', 'templateVars' => ['foo' => 'bar']];
        $result = $this->template->formatAttributes($attrs, ['name']);
        $this->assertSame(
            ' data-hero="&lt;batman&gt;"',
            $result,
        );

        $evilKey = '><script>alert(1)</script>';
        $attrs = [$evilKey => 'some value'];

        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            ' &gt;&lt;script&gt;alert(1)&lt;/script&gt;="some value"',
            $result,
        );
    }

    /**
     * Test formatting array attributes.
     */
    public function testFormatAttributesArray(): void
    {
        $attrs = ['name' => ['bruce', 'wayne']];
        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            ' name="bruce wayne"',
            $result,
        );
    }

    /**
     * test push/pop templates.
     */
    public function testPushPopTemplates(): void
    {
        $this->template->add(['name' => '{{name}} is my name']);
        $this->template->push();

        $this->template->add(['name' => 'my name']);
        $this->assertSame('my name', $this->template->get('name'));

        $this->template->pop();
        $this->assertSame('{{name}} is my name', $this->template->get('name'));

        $this->template->pop();
        $this->template->pop();
    }

    /**
     * Test addClass method with array input (new behavior).
     */
    public function testAddClassWithArray(): void
    {
        // Test adding a single class to empty array
        $result = $this->template->addClass([], 'new-class');
        $this->assertSame(['class' => ['new-class']], $result);

        // Test adding array of classes to empty array
        $result = $this->template->addClass([], ['class-one', 'class-two']);
        $this->assertSame(['class' => ['class-one', 'class-two']], $result);

        // Test adding to existing classes
        $result = $this->template->addClass(['class' => ['existing']], 'new-class');
        $this->assertSame(['class' => ['existing', 'new-class']], $result);

        // Test with custom key
        $result = $this->template->addClass([], 'custom-class', 'custom-key');
        $this->assertSame(['custom-key' => ['custom-class']], $result);

        // Test preserves other attributes
        $attrs = ['id' => 'test', 'class' => ['current']];
        $result = $this->template->addClass($attrs, 'new-class');
        $this->assertSame(['id' => 'test', 'class' => ['current', 'new-class']], $result);

        // Test with string existing classes
        $attrs = ['class' => 'foo bar'];
        $result = $this->template->addClass($attrs, 'baz');
        $this->assertSame(['class' => ['foo', 'bar', 'baz']], $result);

        // Test uniqueness
        $attrs = ['class' => ['duplicate']];
        $result = $this->template->addClass($attrs, 'duplicate');
        $this->assertSame(['class' => ['duplicate']], $result);
    }

    /**
     * Test addClassNames method with various input types.
     */
    public function testAddClassNames(): void
    {
        // Test merging two arrays
        $result = $this->template->addClassNames(['existing'], ['new']);
        $this->assertSame(['existing', 'new'], $result);

        // Test merging string with array
        $result = $this->template->addClassNames('existing', ['new']);
        $this->assertSame(['existing', 'new'], $result);

        // Test merging array with string
        $result = $this->template->addClassNames(['existing'], 'new');
        $this->assertSame(['existing', 'new'], $result);

        // Test merging two strings
        $result = $this->template->addClassNames('existing', 'new');
        $this->assertSame(['existing', 'new'], $result);

        // Test merging space-separated string classes
        $result = $this->template->addClassNames('class-one class-two', 'class-three class-four');
        $this->assertSame(['class-one', 'class-two', 'class-three', 'class-four'], $result);

        // Test uniqueness - duplicates should be removed
        $result = $this->template->addClassNames(['duplicate'], ['duplicate']);
        $this->assertSame(['duplicate'], $result);

        // Test empty strings
        $result = $this->template->addClassNames('', 'new');
        $this->assertSame(['new'], $result);

        $result = $this->template->addClassNames('existing', '');
        $this->assertSame(['existing'], $result);

        // Test both empty
        $result = $this->template->addClassNames('', '');
        $this->assertSame([], $result);

        // Test empty arrays
        $result = $this->template->addClassNames([], []);
        $this->assertSame([], $result);

        // Test maintains numeric indexing
        $result = $this->template->addClassNames(['one', 'two'], ['three']);
        $this->assertSame([0, 1, 2], array_keys($result));
    }

    /**
     * Test addClassNames method with various input types.
     */
    public function testAddClassNamesMap(): void
    {
        // Falsey values remove, truthy values add.
        $result = $this->template->addClassNames(['existing'], ['new' => true, 'no' => false, 'nullish' => null]);
        $this->assertSame(['existing', 'new'], $result);

        $result = $this->template->addClassNames(['clear', 'one'], ['new' => true, 'clear' => false]);
        $this->assertSame(['one', 'new'], $result);

        $result = $this->template->addClassNames(['clear', 'one'], ['new' => true, 'clear' => null]);
        $this->assertSame(['one', 'new'], $result);

        $result = $this->template->addClassNames(['clear', 'one'], ['new' => true, 'clear' => 0]);
        $this->assertSame(['one', 'new'], $result);

        $result = $this->template->addClassNames('one', ['new' => 1]);
        $this->assertSame(['one', 'new'], $result);

        $result = $this->template->addClassNames('one', ['new' => 'hotdog']);
        $this->assertSame(['one', 'new'], $result);
    }

    /**
     * Test addClass method with deprecated string input
     *
     * @deprecated Tests deprecated addClass method with string input
     */
    public function testAddClassMethodDeprecatedStringInput(): void
    {
        $this->expectDeprecationMessageMatches(
            '/Passing a non-array as first argument to `StringTemplate::addClass\(\)` is deprecated/',
            function (): void {
                $result = $this->template->addClass(null, null);
                $this->assertNull($result);
            },
        );
    }

    /**
     * Test addClass method with deprecated non-array inputs
     *
     * Tests null, string, false and object as first parameter
     *
     * @deprecated Tests deprecated addClass method with non-array input
     */
    public function testAddClassMethodCurrentClass(): void
    {
        $this->expectDeprecationMessageMatches(
            '/Passing a non-array as first argument to `StringTemplate::addClass\(\)` is deprecated/',
            function (): void {
                $result = $this->template->addClass('', 'new_class');
                $this->assertEquals($result, ['class' => ['new_class']]);

                $result = $this->template->addClass(null, 'new_class');
                $this->assertEquals($result, ['class' => ['new_class']]);

                $result = $this->template->addClass(false, 'new_class');
                $this->assertEquals($result, ['class' => ['new_class']]);

                $result = $this->template->addClass(new stdClass(), 'new_class');
                $this->assertEquals($result, ['class' => ['new_class']]);
            },
        );
    }

    /**
     * Test addClass method string parameter, it should fallback to string
     *
     * @deprecated Tests deprecated addClass method with string input
     */
    public function testAddClassMethodFallbackToString(): void
    {
        $this->expectDeprecationMessageMatches(
            '/Passing a non-array as first argument to `StringTemplate::addClass\(\)` is deprecated/',
            function (): void {
                $result = $this->template->addClass('current', 'new_class');
                $this->assertEquals($result, ['class' => ['current', 'new_class']]);
            },
        );
    }

    /**
     * Test addClass method to make sure the returned array is unique
     */
    public function testAddClassMethodUnique(): void
    {
        $result = $this->template->addClass(['class' => ['new_class']], 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);
    }

    /**
     * Test addClass method useIndex param
     *
     * Tests for useIndex being the default and 'my_class'
     */
    public function testAddClassMethodUseIndex(): void
    {
        $result = $this->template->addClass(
            [
                'class' => 'current_class',
                'other_index1' => false,
                'type' => 'text',
            ],
            'new_class',
            'class',
        );
        $this->assertEquals($result, [
            'class' => ['current_class', 'new_class'],
            'other_index1' => false,
            'type' => 'text',
        ]);

        $result = $this->template->addClass(
            [
                'my_class' => 'current_class',
                'other_index1' => false,
                'type' => 'text',
            ],
            'new_class',
            'my_class',
        );
        $this->assertEquals($result, [
            'other_index1' => false,
            'type' => 'text',
            'my_class' => ['current_class', 'new_class'],
        ]);

        $result = $this->template->addClass(
            [
                'class' => [
                    'current_class',
                    'text',
                ],
            ],
            'new_class',
            'nonexistent',
        );
        $this->assertEquals($result, [
            'class' => [
                'current_class',
                'text',
            ],
            'nonexistent' => ['new_class'],
        ]);
    }
}
