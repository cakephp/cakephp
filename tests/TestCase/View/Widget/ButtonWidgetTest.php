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
use Cake\View\Widget\ButtonWidget;

/**
 * Basic input test.
 */
class ButtonWidgetTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $templates = [
            'button' => '<button{{attrs}}>{{text}}</button>',
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
        $button = new ButtonWidget($this->templates);
        $result = $button->render(['name' => 'my_input'], $this->context);
        $expected = [
            'button' => ['type' => 'submit', 'name' => 'my_input'],
            '/button'
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
        $button = new ButtonWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'button',
            'text' => 'Some button'
        ];
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => ['type' => 'button', 'name' => 'my_input'],
            'Some button',
            '/button'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with a text
     *
     * @return void
     */
    public function testRenderWithText()
    {
        $button = new ButtonWidget($this->templates);
        $data = [
            'text' => 'Some <value>'
        ];
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => ['type' => 'submit'],
            'Some <value>',
            '/button'
        ];
        $this->assertHtml($expected, $result);

        $data['escape'] = true;
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => ['type' => 'submit'],
            'Some &lt;value&gt;',
            '/button'
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
        $button = new ButtonWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'text' => 'Go',
            'class' => 'btn',
            'required' => true
        ];
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => [
                'type' => 'submit',
                'name' => 'my_input',
                'class' => 'btn',
                'required' => 'required'
            ],
            'Go',
            '/button'
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
            'button' => '<button {{attrs}} custom="{{custom}}">{{text}}</button>',
        ]);

        $button = new ButtonWidget($this->templates);
        $data = [
            'templateVars' => ['custom' => 'value'],
            'text' => 'Go',
        ];
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => [
                'type' => 'submit',
                'custom' => 'value'
            ],
            'Go',
            '/button'
        ];
        $this->assertHtml($expected, $result);
    }
}
