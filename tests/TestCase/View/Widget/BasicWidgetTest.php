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
use Cake\View\Widget\BasicWidget;

/**
 * Basic input test.
 */
class BasicWidgetTest extends TestCase
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
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = new NullContext([]);
    }

    /**
     * Test render in a simple case.
     */
    public function testRenderSimple(): void
    {
        $text = new BasicWidget($this->templates);
        $result = $text->render(['name' => 'my_input'], $this->context);
        $expected = [
            'input' => ['type' => 'text', 'name' => 'my_input'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with custom type
     */
    public function testRenderType(): void
    {
        $text = new BasicWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'email',
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => ['type' => 'email', 'name' => 'my_input'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with a value
     */
    public function testRenderWithValue(): void
    {
        $text = new BasicWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'email',
            'val' => 'Some <value>',
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'email',
                'name' => 'my_input',
                'value' => 'Some &lt;value&gt;',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with additional attributes.
     */
    public function testRenderAttributes(): void
    {
        $text = new BasicWidget($this->templates);
        $data = [
            'name' => 'my_input',
            'type' => 'email',
            'class' => 'form-control',
            'required' => true,
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'email',
                'name' => 'my_input',
                'class' => 'form-control',
                'required' => 'required',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with template params.
     */
    public function testRenderTemplateParams(): void
    {
        $text = new BasicWidget(new StringTemplate([
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}><span>{{help}}</span>',
        ]));
        $data = [
            'name' => 'my_input',
            'type' => 'email',
            'class' => 'form-control',
            'required' => true,
            'templateVars' => ['help' => 'SOS'],
        ];
        $result = $text->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'email',
                'name' => 'my_input',
                'class' => 'form-control',
                'required' => 'required',
            ],
            '<span', 'SOS', '/span',
        ];
        $this->assertHtml($expected, $result);
    }
}
