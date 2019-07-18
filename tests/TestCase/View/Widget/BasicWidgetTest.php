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
namespace Cake\Test\TestCase\View\Widget;

use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Cake\View\Widget\BasicWidget;

/**
 * Basic input test.
 */
class BasicWidgetTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $templates = [
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
    }

    /**
     * Test render in a simple case.
     *
     * @return void
     */
    public function testRenderSimple()
    {
        $text = new BasicWidget($this->templates);
        $result = $text->render(['name' => 'my_input'], $this->context);
        $expected = [
            'input' => ['type' => 'text', 'name' => 'my_input']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with custom type
     *
     * @return void
     */
    public function testRenderType()
    {
        $text = new BasicWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'email',
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => ['type' => 'email', 'name' => 'my_input']
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
        $text = new BasicWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'email',
            'val' => 'Some <value>'
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'email',
                'name' => 'my_input',
                'value' => 'Some &lt;value&gt;'
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with additional attributes.
     *
     * @return void
     */
    public function testRenderAttributes()
    {
        $text = new BasicWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'email',
            'class' => 'form-control',
            'required' => true
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'email',
                'name' => 'my_input',
                'class' => 'form-control',
                'required' => 'required',
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with template params.
     *
     * @return void
     */
    public function testRenderTemplateParams()
    {
        $text = new BasicWidget(new StringTemplate([
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}><span>{{help}}</span>',
        ]));
        $data = [
            'name' => 'my_input',
            'type' => 'email',
            'class' => 'form-control',
            'required' => true,
            'templateVars' => ['help' => 'SOS']
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'email',
                'name' => 'my_input',
                'class' => 'form-control',
                'required' => 'required',
            ],
            '<span', 'SOS', '/span'
        ];
        $this->assertHtml($expected, $result);
    }
}
