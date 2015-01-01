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
use Cake\View\Widget\CheckboxWidget;

/**
 * Checkbox test case
 */
class CheckboxWidgetTest extends TestCase
{

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $templates = [
            'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = $this->getMock('Cake\View\Form\ContextInterface');
    }

    /**
     * Test rendering simple checkboxes.
     *
     * @return void
     */
    public function testRenderSimple()
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
            ]
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
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering disabled checkboxes.
     *
     * @return void
     */
    public function testRenderDisabled()
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
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering checked checkboxes.
     *
     * @return void
     */
    public function testRenderChecked()
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
            ]
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
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Data provider for checkbox values
     *
     * @return array
     */
    public static function checkedProvider()
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
     * @return void
     */
    public function testRenderCheckedValue($checked)
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
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Data provider for checkbox values
     *
     * @return array
     */
    public static function uncheckedProvider()
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
     * @return void
     */
    public function testRenderUnCheckedValue($checked)
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
            ]
        ];
        $this->assertHtml($expected, $result);
    }
}
