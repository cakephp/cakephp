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

use Cake\Collection\Collection;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Cake\View\Widget\NestingLabelWidget;
use Cake\View\Widget\RadioWidget;

/**
 * Radio test case
 */
class RadioWidgetTest extends TestCase
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
            'radio' => '<input type="radio" name="{{name}}" value="{{value}}"{{attrs}}>',
            'nestingLabel' => '<label{{attrs}}>{{input}}{{text}}</label>',
            'radioWrapper' => '{{label}}',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
    }

    /**
     * Test rendering basic radio buttons without nested inputs
     *
     * @return void
     */
    public function testRenderSimpleNotNested()
    {
        $this->templates->add([
            'nestingLabel' => '<label{{attrs}}>{{text}}</label>',
            'radioWrapper' => '{{input}}{{label}}'
        ]);
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'label' => null,
            'options' => ['r' => 'Red', 'b' => 'Black']
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r'
            ]],
            ['label' => ['for' => 'crayons-color-r']],
            'Red',
            '/label',
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b'
            ]],
            ['label' => ['for' => 'crayons-color-b']],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Crayons[color]',
            'label' => false,
            'options' => ['r' => 'Red', 'b' => 'Black']
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r'
            ]],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b'
            ]],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering basic radio buttons.
     *
     * @return void
     */
    public function testRenderSimple()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'label' => null,
            'options' => ['r' => 'Red', 'b' => 'Black']
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'crayons-color-r']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r'
            ]],
            'Red',
            '/label',
            ['label' => ['for' => 'crayons-color-b']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b'
            ]],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Crayons[color]',
            'options' => new Collection(['r' => 'Red', 'b' => 'Black'])
        ];
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering inputs with the complex option form.
     *
     * @return void
     */
    public function testRenderComplex()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'options' => [
                ['value' => 'r', 'text' => 'Red', 'id' => 'my_id'],
                ['value' => 'b', 'text' => 'Black', 'id' => 'my_id_2', 'data-test' => 'test'],
            ]
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'my_id']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'my_id'
            ]],
            'Red',
            '/label',
            ['label' => ['for' => 'my_id_2']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'my_id_2',
                'data-test' => 'test'
            ]],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that id suffixes are generated to not collide
     *
     * @return void
     */
    public function testRenderIdSuffixGeneration()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Thing[value]',
            'options' => ['a>b' => 'First', 'a<b' => 'Second']
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'thing-value-a-b']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Thing[value]',
                'value' => 'a&gt;b',
                'id' => 'thing-value-a-b'
            ]],
            'First',
            '/label',
            ['label' => ['for' => 'thing-value-a-b1']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Thing[value]',
                'value' => 'a&lt;b',
                'id' => 'thing-value-a-b1',
            ]],
            'Second',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering checks the right option with booleanish values.
     *
     * @return void
     */
    public function testRenderBooleanishValues()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Model[field]',
            'options' => ['1' => 'Yes', '0' => 'No'],
            'val' => '0'
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'model-field-1']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '1', 'id' => 'model-field-1']],
            'Yes',
            '/label',
            ['label' => ['for' => 'model-field-0']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0', 'checked' => 'checked']],
            'No',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data['val'] = 0;
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['val'] = false;
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $expected = [
            ['label' => ['for' => 'model-field-1']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '1', 'id' => 'model-field-1']],
            'Yes',
            '/label',
            ['label' => ['for' => 'model-field-0']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0']],
            'No',
            '/label',
        ];
        $data['val'] = null;
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['val'] = '';
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $expected = [
            ['label' => ['for' => 'model-field-1']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '1', 'id' => 'model-field-1', 'checked' => 'checked']],
            'Yes',
            '/label',
            ['label' => ['for' => 'model-field-0']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0']],
            'No',
            '/label',
        ];
        $data['val'] = '1';
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['val'] = 1;
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['val'] = true;
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that render() works with the required attribute.
     *
     * @return void
     */
    public function testRenderRequiredAndFormAttribute()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'published',
            'options' => ['option A', 'option B'],
            'required' => true,
            'form' => 'my-form',
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'published-0']],
            ['input' => ['type' => 'radio', 'name' => 'published', 'value' => '0',
                'id' => 'published-0', 'required' => 'required', 'form' => 'my-form']],
            'option A',
            '/label',
            ['label' => ['for' => 'published-1']],
            ['input' => ['type' => 'radio', 'name' => 'published', 'value' => '1',
                'id' => 'published-1', 'required' => 'required', 'form' => 'my-form']],
            'option B',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering the empty option.
     *
     * @return void
     */
    public function testRenderEmptyOption()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'options' => ['r' => 'Red'],
            'empty' => true,
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'crayons-color']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => '',
                'id' => 'crayons-color'
            ]],
            'empty',
            '/label',
            ['label' => ['for' => 'crayons-color-r']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r'
            ]],
            'Red',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data['empty'] = 'Choose one';
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'crayons-color']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => '',
                'id' => 'crayons-color'
            ]],
            'Choose one',
            '/label',
            ['label' => ['for' => 'crayons-color-r']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r'
            ]],
            'Red',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering the input inside the label.
     *
     * @return void
     */
    public function testRenderInputInsideLabel()
    {
        $this->templates->add([
            'label' => '<label{{attrs}}>{{input}}{{text}}</label>',
            'radioWrapper' => '{{label}}',
        ]);
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'options' => ['r' => 'Red'],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'crayons-color-r']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r'
            ]],
            'Red',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test render() and selected inputs.
     *
     * @return void
     */
    public function testRenderSelected()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Versions[ver]',
            'val' => '1',
            'options' => [
                1 => 'one',
                '1x' => 'one x',
                '2' => 'two',
            ]
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'versions-ver-1']],
            ['input' => [
                'id' => 'versions-ver-1',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1',
                'checked' => 'checked'
            ]],
            'one',
            '/label',
            ['label' => ['for' => 'versions-ver-1x']],
            ['input' => [
                'id' => 'versions-ver-1x',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1x'
            ]],
            'one x',
            '/label',
            ['label' => ['for' => 'versions-ver-2']],
            ['input' => [
                'id' => 'versions-ver-2',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '2'
            ]],
            'two',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering with disable inputs
     *
     * @return void
     */
    public function testRenderDisabled()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Versions[ver]',
            'options' => [
                1 => 'one',
                '1x' => 'one x',
                '2' => 'two',
            ],
            'disabled' => true,
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'versions-ver-1']],
            ['input' => [
                'id' => 'versions-ver-1',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1',
                'disabled' => 'disabled'
            ]],
            'one',
            '/label',
            ['label' => ['for' => 'versions-ver-1x']],
            ['input' => [
                'id' => 'versions-ver-1x',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1x',
                'disabled' => 'disabled'
            ]],
            'one x',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data['disabled'] = 'a string';
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);

        $data['disabled'] = ['1'];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'versions-ver-1']],
            ['input' => [
                'id' => 'versions-ver-1',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1',
                'disabled' => 'disabled'
            ]],
            'one',
            '/label',
            ['label' => ['for' => 'versions-ver-1x']],
            ['input' => [
                'id' => 'versions-ver-1x',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1x',
            ]],
            'one x',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering with label options.
     *
     * @return void
     */
    public function testRenderLabelOptions()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Versions[ver]',
            'options' => [
                1 => 'one',
                '1x' => 'one x',
                '2' => 'two',
            ],
            'label' => false,
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['input' => [
                'id' => 'versions-ver-1',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1',
            ]],
            ['input' => [
                'id' => 'versions-ver-1x',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1x',
            ]],
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Versions[ver]',
            'options' => [
                1 => 'one',
                '1x' => 'one x',
                '2' => 'two',
            ],
            'label' => [
                'class' => 'my-class',
            ]
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['class' => 'my-class', 'for' => 'versions-ver-1']],
            ['input' => [
                'id' => 'versions-ver-1',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1',
            ]],
            'one',
            '/label',
            ['label' => ['class' => 'my-class', 'for' => 'versions-ver-1x']],
            ['input' => [
                'id' => 'versions-ver-1x',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1x',
            ]],
            'one x',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure that the input + label are composed with
     * a template.
     *
     * @return void
     */
    public function testRenderContainerTemplate()
    {
        $this->templates->add([
            'radioWrapper' => '<div class="radio">{{input}}{{label}}</div>'
        ]);
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Versions[ver]',
            'options' => [
                1 => 'one',
                '1x' => 'one x',
                '2' => 'two',
            ],
        ];
        $result = $radio->render($data, $this->context);
        $this->assertContains(
            '<div class="radio"><input type="radio"',
            $result
        );
        $this->assertContains(
            '</label></div>',
            $result
        );
    }

    /**
     * Ensure that template vars work.
     *
     * @return void
     */
    public function testRenderTemplateVars()
    {
        $this->templates->add([
            'radioWrapper' => '<div class="radio" data-var="{{wrapperVar}}">{{label}}</div>',
            'radio' => '<input type="radio" data-i="{{inputVar}}" name="{{name}}" value="{{value}}"{{attrs}}>',
            'nestingLabel' => '<label{{attrs}}>{{input}}{{text}} {{labelVar}} {{wrapperVar}}</label>',
        ]);
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Versions[ver]',
            'options' => [
                ['value' => '1x', 'text' => 'one x', 'templateVars' => ['labelVar' => 'l-var', 'inputVar' => 'i-var']],
                '2' => 'two',
            ],
            'templateVars' => [
                'wrapperVar' => 'wrap-var',
            ]
        ];
        $result = $radio->render($data, $this->context);
        $this->assertContains('data-var="wrap-var"><label', $result);
        $this->assertContains('type="radio" data-i="i-var"', $result);
        $this->assertContains('one x l-var wrap-var</label>', $result);
        $this->assertContains('two  wrap-var</label>', $result);
    }

    /**
     * testRenderCustomAttributes method
     *
     * Test render with custom attributes.
     *
     * @return void
     */
    public function testRenderCustomAttributes()
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $result = $radio->render([
            'name' => 'Model[field]',
            'label' => null,
            'options' => ['option A', 'option B'],
            'class' => 'my-class',
            'data-ref' => 'custom-attr'
        ], $this->context);
        $expected = [
            ['label' => ['for' => 'model-field-0']],
            [
                'input' => [
                    'type' => 'radio',
                    'name' => 'Model[field]',
                    'value' => '0',
                    'id' => 'model-field-0',
                    'class' => 'my-class',
                    'data-ref' => 'custom-attr'
                ]
            ],
            'option A',
            '/label',
            ['label' => ['for' => 'model-field-1']],
            [
                'input' => [
                    'type' => 'radio',
                    'name' => 'Model[field]',
                    'value' => '1',
                    'id' => 'model-field-1',
                    'class' => 'my-class',
                    'data-ref' => 'custom-attr'
                ]
            ],
            'option B',
            '/label'
        ];
        $this->assertHtml($expected, $result);
    }
}
