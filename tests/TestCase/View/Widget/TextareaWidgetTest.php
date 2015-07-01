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
namespace Cake\Test\TestCase\View\Widget;

use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Cake\View\Widget\TextareaWidget;

/**
 * Textarea input test.
 */
class TextareaWidgetTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $templates = [
            'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
        ];
        $this->context = $this->getMock('Cake\View\Form\ContextInterface');
        $this->templates = new StringTemplate($templates);
    }

    /**
     * Test render in a simple case.
     *
     * @return void
     */
    public function testRenderSimple()
    {
        $input = new TextareaWidget($this->templates);
        $result = $input->render(['name' => 'comment'], $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5],
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with a value
     *
     * @return void
     */
    public function testRenderWithValue()
    {
        $input = new TextareaWidget($this->templates);
        $data = ['name' => 'comment', 'data-foo' => '<val>', 'val' => 'some <html>'];
        $result = $input->render($data, $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5, 'data-foo' => '&lt;val&gt;'],
            'some &lt;html&gt;',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);

        $data['escape'] = false;
        $result = $input->render($data, $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5, 'data-foo' => '<val>'],
            'some <html>',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure templateVars option is hooked up.
     *
     * @return void
     */
    public function testRenderTemplateVars()
    {
        $this->templates->add([
            'textarea' => '<textarea custom="{{custom}}" name="{{name}}"{{attrs}}>{{value}}</textarea>',
        ]);

        $input = new TextareaWidget($this->templates);
        $data = [
            'templateVars' => ['custom' => 'value'],
            'name' => 'comment',
            'val' => 'body'
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5, 'custom' => 'value'],
            'body',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }
}
