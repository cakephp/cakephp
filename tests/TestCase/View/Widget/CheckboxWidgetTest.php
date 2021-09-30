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
use Cake\View\Widget\CheckboxWidget;

/**
 * Checkbox test case
 */
class CheckboxWidgetTest extends TestCase
{
    /**
     * @var \Cake\View\Form\NullContext
     */
    protected $context;

    /**
     * @var \Cake\View\StringTemplate
     */
    protected $templates;

    /**
     * setup method.
     */
    public function setUp(): void
    {
        parent::setUp();
        $templates = [
            'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = new NullContext([]);
    }

    /**
     * Test rendering simple checkboxes.
     */
    public function testRenderSimple(): void
    {
        $checkbox = new CheckboxWidget($this->templates);
        $data = [
            'name' => 'Comment[spam]',
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Comment[spam]',
                'value' => 1,
            ],
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Comment[spam]',
            'value' => 99,
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Comment[spam]',
                'value' => 99,
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering disabled checkboxes.
     */
    public function testRenderDisabled(): void
    {
        $checkbox = new CheckboxWidget($this->templates);
        $data = [
            'name' => 'Comment[spam]',
            'disabled' => true,
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Comment[spam]',
                'value' => 1,
                'disabled' => 'disabled',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering checked checkboxes.
     */
    public function testRenderChecked(): void
    {
        $checkbox = new CheckboxWidget($this->templates);
        $data = [
            'name' => 'Comment[spam]',
            'value' => 1,
            'checked' => 1,
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Comment[spam]',
                'value' => 1,
                'checked' => 'checked',
            ],
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Comment[spam]',
            'value' => 1,
            'val' => 1,
        ];
        $result = $checkbox->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['val'] = '1';
        $result = $checkbox->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Comment[spam]',
            'value' => 1,
            'val' => '1x',
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Comment[spam]',
                'value' => 1,
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Data provider for checkbox values
     *
     * @return array
     */
    public static function checkedProvider(): array
    {
        return [
            ['checked'],
            ['1'],
            [1],
            [true],
        ];
    }

    /**
     * Test rendering checked checkboxes with value.
     *
     * @dataProvider checkedProvider
     * @param mixed $checked
     */
    public function testRenderCheckedValue($checked): void
    {
        $checkbox = new CheckboxWidget($this->templates);
        $data = [
            'name' => 'Comment[spam]',
            'value' => 1,
            'checked' => $checked,
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Comment[spam]',
                'value' => 1,
                'checked' => 'checked',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Data provider for checkbox values
     *
     * @return array
     */
    public static function uncheckedProvider(): array
    {
        return [
            [''],
            ['0'],
            [0],
            [false],
            [null],
        ];
    }

    /**
     * Test rendering unchecked checkboxes
     *
     * @dataProvider uncheckedProvider
     * @param mixed $checked
     */
    public function testRenderUnCheckedValue($checked): void
    {
        $checkbox = new CheckboxWidget($this->templates);
        $data = [
            'name' => 'Comment[spam]',
            'value' => 1,
            'val' => 1,
            'checked' => $checked,
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Comment[spam]',
                'value' => 1,
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure templateVars option is hooked up.
     */
    public function testRenderTemplateVars(): void
    {
        $this->templates->add([
            'checkbox' => '<input type="checkbox" custom="{{custom}}" name="{{name}}" value="{{value}}"{{attrs}}>',
        ]);

        $checkbox = new CheckboxWidget($this->templates);
        $data = [
            'templateVars' => ['custom' => 'value'],
            'name' => 'Comment[spam]',
            'value' => 1,
        ];
        $result = $checkbox->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'custom' => 'value',
                'name' => 'Comment[spam]',
                'value' => 1,
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRenderCustomAttributes method
     *
     * Test render with custom attributes.
     */
    public function testRenderCustomAttributes(): void
    {
        $checkbox = new CheckboxWidget($this->templates);

        $result = $checkbox->render([
            'name' => 'Model[field]',
            'class' => 'my-class',
            'data-ref' => 'custom-attr',
            'value' => 1,

        ], $this->context);

        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'Model[field]',
                'value' => '1',
                'class' => 'my-class',
                'data-ref' => 'custom-attr',
            ],
        ];
        $this->assertHtml($expected, $result);
    }
}
