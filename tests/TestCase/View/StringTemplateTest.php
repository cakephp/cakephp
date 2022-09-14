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
use RuntimeException;
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
    public function setUp(): void
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
            'The same instance should be returned'
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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot find template named \'missing\'');
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
            $result
        );

        $attrs = ['disabled' => false, 'selected' => 0, 'checked' => '0', 'multiple' => null];
        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            '',
            $result
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
            $result
        );

        $attrs = ['escape' => false, 'name' => 'bruce', 'data-hero' => '<batman>'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            ' name="bruce" data-hero="<batman>"',
            $result
        );

        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>'];
        $result = $this->template->formatAttributes($attrs, ['name']);
        $this->assertSame(
            ' data-hero="&lt;batman&gt;"',
            $result
        );

        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>', 'templateVars' => ['foo' => 'bar']];
        $result = $this->template->formatAttributes($attrs, ['name']);
        $this->assertSame(
            ' data-hero="&lt;batman&gt;"',
            $result
        );

        $evilKey = '><script>alert(1)</script>';
        $attrs = [$evilKey => 'some value'];

        $result = $this->template->formatAttributes($attrs);
        $this->assertSame(
            ' &gt;&lt;script&gt;alert(1)&lt;/script&gt;="some value"',
            $result
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
            $result
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
     * Test addClass method newClass parameter
     *
     * Tests null, string, array and false for `input`
     */
    public function testAddClassMethodNewClass(): void
    {
        $result = $this->template->addClass([], 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass([], ['new_class']);
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass([], false);
        $this->assertEquals($result, []);

        $result = $this->template->addClass([], null);
        $this->assertEquals($result, []);

        $result = $this->template->addClass(null, null);
        $this->assertNull($result);
    }

    /**
     * Test addClass method input (currentClass) parameter
     *
     * Tests null, string, array, false and object
     */
    public function testAddClassMethodCurrentClass(): void
    {
        $result = $this->template->addClass(['class' => ['current']], 'new_class');
        $this->assertEquals($result, ['class' => ['current', 'new_class']]);

        $result = $this->template->addClass('', 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass(null, 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass(false, 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass(new stdClass(), 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);
    }

    /**
     * Test addClass method string parameter, it should fallback to string
     */
    public function testAddClassMethodFallbackToString(): void
    {
        $result = $this->template->addClass('current', 'new_class');
        $this->assertEquals($result, ['class' => ['current', 'new_class']]);
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
     * Tests for useIndex being the default, 'my_class' and false
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
            'class'
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
            'my_class'
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
            'nonexistent'
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
