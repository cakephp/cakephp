<?php
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

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;

class StringTemplateTest extends TestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->template = new StringTemplate();
    }

    /**
     * Test adding templates through the constructor.
     *
     * @return void
     */
    public function testConstructorAdd()
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>'
        ];
        $template = new StringTemplate($templates);
        $this->assertEquals($templates['link'], $template->get('link'));
    }

    /**
     * test adding templates.
     *
     * @return void
     */
    public function testAdd()
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>'
        ];
        $result = $this->template->add($templates);
        $this->assertSame(
            $this->template,
            $result,
            'The same instance should be returned'
        );

        $this->assertEquals($templates['link'], $this->template->get('link'));
    }

    /**
     * Test remove.
     *
     * @return void
     */
    public function testRemove()
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>'
        ];
        $this->template->add($templates);
        $this->assertNull($this->template->remove('link'), 'No return');
        $this->assertNull($this->template->get('link'), 'Template should be gone.');
    }

    /**
     * Test formatting strings.
     *
     * @return void
     */
    public function testFormat()
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>',
            'text' => '{{text}}',
            'custom' => '<custom {{standard}} v1="{{var1}}" v2="{{var2}}" />'
        ];
        $this->template->add($templates);

        $result = $this->template->format('text', ['text' => '']);
        $this->assertSame('', $result);

        $result = $this->template->format('text', []);
        $this->assertSame('', $result);

        $result = $this->template->format('link', [
            'url' => '/',
            'text' => 'example'
        ]);
        $this->assertEquals('<a href="/">example</a>', $result);

        $result = $this->template->format('custom', [
            'standard' => 'default',
            'templateVars' => ['var1' => 'foo']
        ]);
        $this->assertEquals('<custom default v1="foo" v2="" />', $result);
    }

    /**
     * Test formatting strings with URL encoding
     *
     * @return void
     */
    public function testFormatUrlEncoding()
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
     *
     * @return void
     */
    public function testFormatArrayData()
    {
        $templates = [
            'link' => '<a href="{{url}}">{{text}}</a>'
        ];
        $this->template->add($templates);

        $result = $this->template->format('link', [
            'url' => '/',
            'text' => ['example', 'text']
        ]);
        $this->assertEquals('<a href="/">exampletext</a>', $result);

        $result = $this->template->format('link', [
            'url' => '/',
            'text' => ['key' => 'example', 'text']
        ]);
        $this->assertEquals('<a href="/">exampletext</a>', $result);
    }

    /**
     * Test formatting a missing template.
     *
     * @return void
     */
    public function testFormatMissingTemplate()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot find template named \'missing\'');
        $templates = [
            'text' => '{{text}}',
        ];
        $this->template->add($templates);
        $this->template->format('missing', ['text' => 'missing']);
    }

    /**
     * Test loading templates files in the app.
     *
     * @return void
     */
    public function testLoad()
    {
        $this->template->remove('attribute');
        $this->template->remove('compactAttribute');
        $this->assertEquals([], $this->template->get());
        $this->assertNull($this->template->load('test_templates'));
        $this->assertEquals('<a href="{{url}}">{{text}}</a>', $this->template->get('link'));
    }

    /**
     * Test loading templates files from a plugin
     *
     * @return void
     */
    public function testLoadPlugin()
    {
        Plugin::load('TestPlugin');
        $this->assertNull($this->template->load('TestPlugin.test_templates'));
        $this->assertEquals('<em>{{text}}</em>', $this->template->get('italic'));
    }

    /**
     * Test that loading non-existing templates causes errors.
     *
     */
    public function testLoadErrorNoFile()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->expectExceptionMessage('Could not load configuration file');
        $this->template->load('no_such_file');
    }

    /**
     * Test formatting compact attributes.
     *
     * @return void
     */
    public function testFormatAttributesCompact()
    {
        $attrs = ['disabled' => true, 'selected' => 1, 'checked' => '1', 'multiple' => 'multiple'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertEquals(
            ' disabled="disabled" selected="selected" checked="checked" multiple="multiple"',
            $result
        );

        $attrs = ['disabled' => false, 'selected' => 0, 'checked' => '0', 'multiple' => null];
        $result = $this->template->formatAttributes($attrs);
        $this->assertEquals(
            '',
            $result
        );
    }

    /**
     * Test formatting normal attributes.
     *
     * @return void
     */
    public function testFormatAttributes()
    {
        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>', 'spellcheck' => 'true'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertEquals(
            ' name="bruce" data-hero="&lt;batman&gt;" spellcheck="true"',
            $result
        );

        $attrs = ['escape' => false, 'name' => 'bruce', 'data-hero' => '<batman>'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertEquals(
            ' name="bruce" data-hero="<batman>"',
            $result
        );

        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>'];
        $result = $this->template->formatAttributes($attrs, ['name']);
        $this->assertEquals(
            ' data-hero="&lt;batman&gt;"',
            $result
        );

        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>', 'templateVars' => ['foo' => 'bar']];
        $result = $this->template->formatAttributes($attrs, ['name']);
        $this->assertEquals(
            ' data-hero="&lt;batman&gt;"',
            $result
        );

        $evilKey = "><script>alert(1)</script>";
        $attrs = [$evilKey => 'some value'];

        $result = $this->template->formatAttributes($attrs);
        $this->assertEquals(
            ' &gt;&lt;script&gt;alert(1)&lt;/script&gt;="some value"',
            $result
        );
    }

    /**
     * Test formatting array attributes.
     *
     * @return void
     */
    public function testFormatAttributesArray()
    {
        $attrs = ['name' => ['bruce', 'wayne']];
        $result = $this->template->formatAttributes($attrs);
        $this->assertEquals(
            ' name="bruce wayne"',
            $result
        );
    }

    /**
     * test push/pop templates.
     *
     * @return void
     */
    public function testPushPopTemplates()
    {
        $this->template->add(['name' => '{{name}} is my name']);
        $this->assertNull($this->template->push());

        $this->template->add(['name' => 'my name']);
        $this->assertEquals('my name', $this->template->get('name'));

        $this->assertNull($this->template->pop());
        $this->assertEquals('{{name}} is my name', $this->template->get('name'));

        $this->assertNull($this->template->pop());
        $this->assertNull($this->template->pop());
    }

    /**
     * Test addClass method newClass parameter
     *
     * Tests null, string, array and false for `input`
     *
     * @return void
     */
    public function testAddClassMethodNewClass()
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
     *
     * @return void
     */
    public function testAddClassMethodCurrentClass()
    {
        $result = $this->template->addClass(['class' => ['current']], 'new_class');
        $this->assertEquals($result, ['class' => ['current', 'new_class']]);

        $result = $this->template->addClass('', 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass(null, 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass(false, 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);

        $result = $this->template->addClass(new \StdClass(), 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);
    }

    /**
     * Test addClass method string parameter, it should fallback to string
     *
     * @return void
     */
    public function testAddClassMethodFallbackToString()
    {
        $result = $this->template->addClass('current', 'new_class');
        $this->assertEquals($result, ['class' => ['current', 'new_class']]);
    }

    /**
     * Test addClass method to make sure the returned array is unique
     *
     * @return void
     */
    public function testAddClassMethodUnique()
    {
        $result = $this->template->addClass(['class' => ['new_class']], 'new_class');
        $this->assertEquals($result, ['class' => ['new_class']]);
    }

    /**
     * Test addClass method useIndex param
     *
     * Tests for useIndex being the default, 'my_class' and false
     *
     * @return void
     */
    public function testAddClassMethodUseIndex()
    {
        $result = $this->template->addClass(
            [
                'class' => 'current_class',
                'other_index1' => false,
                'type' => 'text'
            ],
            'new_class',
            'class'
        );
        $this->assertEquals($result, [
            'class' => ['current_class', 'new_class'],
            'other_index1' => false,
            'type' => 'text'
        ]);

        $result = $this->template->addClass(
            [
                'my_class' => 'current_class',
                'other_index1' => false,
                'type' => 'text'
            ],
            'new_class',
            'my_class'
        );
        $this->assertEquals($result, [
            'other_index1' => false,
            'type' => 'text',
            'my_class' => ['current_class', 'new_class']
        ]);

        $result = $this->template->addClass(
            [
                'class' => [
                    'current_class',
                    'text'
                ]
            ],
            'new_class',
            'non-existent'
        );
        $this->assertEquals($result, [
            'class' => [
                'current_class',
                'text'
            ],
            'non-existent' => ['new_class']
        ]);
    }
}
