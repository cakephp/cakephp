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
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
            'link' => '<a href="{{url}}">{{text}}</a>'
        ];
        $this->template->add($templates);

        $result = $this->template->format('not there', []);
        $this->assertSame('', $result);

        $result = $this->template->format('link', [
            'url' => '/',
            'text' => 'example'
        ]);
        $this->assertEquals('<a href="/">example</a>', $result);
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
     * @expectedException \Cake\Core\Exception\Exception
     * @expectedExceptionMessage Could not load configuration file
     */
    public function testLoadErrorNoFile()
    {
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
        $attrs = ['name' => 'bruce', 'data-hero' => '<batman>'];
        $result = $this->template->formatAttributes($attrs);
        $this->assertEquals(
            ' name="bruce" data-hero="&lt;batman&gt;"',
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
}
