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
use Cake\View\Widget\LabelWidget;
use Cake\View\Widget\MultiCheckboxWidget;

/**
 * MultiCheckbox test case.
 */
class MultiCheckboxWidgetTest extends TestCase
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
            'label' => '<label{{attrs}}>{{text}}</label>',
            'checkboxWrapper' => '<div class="checkbox">{{input}}{{label}}</div>',
            'multicheckboxWrapper' => '<fieldset{{attrs}}>{{content}}</fieldset>',
            'multicheckboxTitle' => '<legend>{{text}}</legend>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
    }

    /**
     * Test render simple option sets.
     *
     * @return void
     */
    public function testRenderSimple()
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                1 => 'CakePHP',
                2 => 'Development',
            ]
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
            ]],
            ['label' => ['for' => 'tags-id-1']],
            'CakePHP',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 2,
                'id' => 'tags-id-2',
            ]],
            ['label' => ['for' => 'tags-id-2']],
            'Development',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render complex and additional attributes.
     *
     * @return void
     */
    public function testRenderComplex()
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'val' => 2,
            'disabled' => ['1'],
            'options' => [
                ['value' => '1', 'text' => 'CakePHP', 'data-test' => 'val'],
                ['value' => '2', 'text' => 'Development', 'class' => 'custom'],
            ]
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'disabled' => 'disabled',
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
                'data-test' => 'val',
            ]],
            ['label' => ['for' => 'tags-id-1']],
            'CakePHP',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'checked' => 'checked',
                'name' => 'Tags[id][]',
                'value' => 2,
                'id' => 'tags-id-2',
                'class' => 'custom',
            ]],
            ['label' => ['class' => 'selected', 'for' => 'tags-id-2']],
            'Development',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render escpaing options.
     *
     * @return void
     */
    public function testRenderEscaping()
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                '>' => '>>',
            ]
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => '&gt;',
                'id' => 'tags-id',
            ]],
            ['label' => ['for' => 'tags-id']],
            '&gt;&gt;',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render selected checkboxes.
     *
     * @return void
     */
    public function testRenderSelected()
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                1 => 'CakePHP',
                '1x' => 'Development',
            ],
            'val' => [1],
            'disabled' => false
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
                'checked' => 'checked'
            ]],
            ['label' => ['class' => 'selected', 'for' => 'tags-id-1']],
            'CakePHP',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => '1x',
                'id' => 'tags-id-1x',
            ]],
            ['label' => ['for' => 'tags-id-1x']],
            'Development',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $data['val'] = 1;
        $result = $input->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['val'] = '1';
        $result = $input->render($data, $this->context);
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render disabled checkboxes.
     *
     * @return void
     */
    public function testRenderDisabled()
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                1 => 'CakePHP',
                '1x' => 'Development',
            ],
            'disabled' => true,
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
                'disabled' => 'disabled'
            ]],
            ['label' => ['for' => 'tags-id-1']],
            'CakePHP',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => '1x',
                'id' => 'tags-id-1x',
                'disabled' => 'disabled'
            ]],
            ['label' => ['for' => 'tags-id-1x']],
            'Development',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $data['disabled'] = 'a string';
        $result = $input->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['disabled'] = ['1', '1x'];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Tags[id]',
            'options' => [
                1 => 'CakePHP',
                '1x' => 'Development',
            ],
            'disabled' => [1]
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
                'disabled' => 'disabled'
            ]],
            ['label' => ['for' => 'tags-id-1']],
            'CakePHP',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => '1x',
                'id' => 'tags-id-1x',
            ]],
            ['label' => ['for' => 'tags-id-1x']],
            'Development',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render templateVars
     *
     * @return void
     */
    public function testRenderTemplateVars()
    {
        $templates = [
            'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}" data-var="{{inputVar}}" {{attrs}}>',
            'label' => '<label{{attrs}}>{{text}} {{inputVar}}</label>',
            'checkboxWrapper' => '<div class="checkbox" data-wrap="{{wrapVar}}">{{input}}{{label}}</div>',
        ];
        $this->templates->add($templates);

        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                ['value' => '1', 'text' => 'CakePHP', 'templateVars' => ['inputVar' => 'i-var']],
                '1x' => 'Development',
            ],
            'templateVars' => ['inputVar' => 'default', 'wrapVar' => 'val'],
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox', 'data-wrap' => 'val']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
                'data-var' => 'i-var',
            ]],
            ['label' => ['for' => 'tags-id-1']],
            'CakePHP i-var',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox', 'data-wrap' => 'val']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => '1x',
                'id' => 'tags-id-1x',
                'data-var' => 'default'
            ]],
            ['label' => ['for' => 'tags-id-1x']],
            'Development default',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with groupings.
     *
     * @return void
     */
    public function testRenderGrouped()
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                'Group 1' => [
                    1 => 'CakePHP',
                ],
                'Group 2' => [
                    2 => 'Development',
                ]
            ]
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            '<fieldset',
            '<legend', 'Group 1', '/legend',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
            ]],
            ['label' => ['for' => 'tags-id-1']],
            'CakePHP',
            '/label',
            '/div',
            '/fieldset',

            '<fieldset',
            '<legend', 'Group 2', '/legend',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 2,
                'id' => 'tags-id-2',
            ]],
            ['label' => ['for' => 'tags-id-2']],
            'Development',
            '/label',
            '/div',
            '/fieldset',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with partial groupings.
     *
     * @return void
     */
    public function testRenderPartialGrouped()
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                1 => 'PHP',
                'Group 1' => [
                    2 => 'CakePHP',
                ],
                3 => 'Development',
            ]
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
            ]],
            ['label' => ['for' => 'tags-id-1']],
            'PHP',
            '/label',
            '/div',

            '<fieldset',
            '<legend', 'Group 1', '/legend',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 2,
                'id' => 'tags-id-2',
            ]],
            ['label' => ['for' => 'tags-id-2']],
            'CakePHP',
            '/label',
            '/div',
            '/fieldset',

            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 3,
                'id' => 'tags-id-3',
            ]],
            ['label' => ['for' => 'tags-id-3']],
            'Development',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }
}
