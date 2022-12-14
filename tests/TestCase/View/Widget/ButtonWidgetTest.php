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
namespace Cake\Test\TestCase\View\Widget;

use Cake\TestSuite\TestCase;
use Cake\View\Form\NullContext;
use Cake\View\StringTemplate;
use Cake\View\Widget\ButtonWidget;

/**
 * Basic input test.
 */
class ButtonWidgetTest extends TestCase
{
    /**
     * @var \Cake\View\Form\NullContext
     */
    protected $context;

    /**
     * @var \Cake\View\StringTemplate
     */
    protected $templates;

    public function setUp(): void
    {
        parent::setUp();
        $templates = [
            'button' => '<button{{attrs}}>{{text}}</button>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = new NullContext([]);
    }

    /**
     * Test render in a simple case.
     */
    public function testRenderSimple(): void
    {
        $button = new ButtonWidget($this->templates);
        $result = $button->render(['name' => 'my_input'], $this->context);
        $expected = [
            'button' => ['type' => 'submit', 'name' => 'my_input'],
            '/button',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with custom type
     */
    public function testRenderType(): void
    {
        $button = new ButtonWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'button',
            'text' => 'Some button',
        ];
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => ['type' => 'button', 'name' => 'my_input'],
            'Some button',
            '/button',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with a text
     */
    public function testRenderWithText(): void
    {
        $button = new ButtonWidget($this->templates);
        $data = [
            'text' => 'Some <value>',
            'onclick' => '<escape me>',
        ];
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => ['type' => 'submit', 'onclick' => '&lt;escape me&gt;'],
            'Some &lt;value&gt;',
            '/button',
        ];
        $this->assertHtml($expected, $result);

        $data['escapeTitle'] = false;
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => ['type' => 'submit', 'onclick' => '&lt;escape me&gt;'],
            'Some <value>',
            '/button',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with additional attributes.
     */
    public function testRenderAttributes(): void
    {
        $button = new ButtonWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'text' => 'Go',
            'class' => 'btn',
            'required' => true,
        ];
        $result = $button->render($data, $this->context);
        $expected = [
            'button' => [
                'type' => 'submit',
                'name' => 'my_input',
                'class' => 'btn',
                'required' => 'required',
            ],
            'Go',
            '/button',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure templateVars option is hooked up.
     */
    public function testRenderTemplateVars(): void
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
                'custom' => 'value',
            ],
            'Go',
            '/button',
        ];
        $this->assertHtml($expected, $result);
    }
}
