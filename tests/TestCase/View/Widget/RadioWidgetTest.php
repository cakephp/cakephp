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

use Cake\Collection\Collection;
use Cake\TestSuite\TestCase;
use Cake\View\Form\NullContext;
use Cake\View\StringTemplate;
use Cake\View\Widget\NestingLabelWidget;
use Cake\View\Widget\RadioWidget;

/**
 * Radio test case
 */
class RadioWidgetTest extends TestCase
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
            'radio' => '<input type="radio" name="{{name}}" value="{{value}}"{{attrs}}>',
            'nestingLabel' => '<label{{attrs}}>{{input}}{{text}}</label>',
            'radioWrapper' => '{{label}}',
            'selectedClass' => 'selected',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = new NullContext([]);
    }

    /**
     * Test rendering basic radio buttons without nested inputs
     */
    public function testRenderSimpleNotNested(): void
    {
        $this->templates->add([
            'nestingLabel' => '<label{{attrs}}>{{text}}</label>',
            'radioWrapper' => '{{input}}{{label}}',
        ]);
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'label' => null,
            'options' => ['r' => 'Red', 'b' => 'Black'],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r',
            ]],
            ['label' => ['for' => 'crayons-color-r']],
            'Red',
            '/label',
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b',
            ]],
            ['label' => ['for' => 'crayons-color-b']],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Crayons[color]',
            'label' => false,
            'options' => ['r' => 'Red', 'b' => 'Black'],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r',
            ]],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b',
            ]],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering basic radio buttons.
     */
    public function testRenderSimple(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'label' => null,
            'options' => ['r' => 'Red', 'b' => 'Black'],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'crayons-color-r']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r',
            ]],
            'Red',
            '/label',
            ['label' => ['for' => 'crayons-color-b']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b',
            ]],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'Crayons[color]',
            'options' => new Collection(['r' => 'Red', 'b' => 'Black']),
        ];
        $result = $radio->render($data, $this->context);
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering the activeClass template var
     */
    public function testRenderSimpleActiveTemplateVar(): void
    {
        $this->templates->add([
            'nestingLabel' => '<label class="{{activeClass}}"{{attrs}}>{{text}}</label>',
            'radioWrapper' => '{{input}}{{label}}',
        ]);
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'val' => 'r',
            'options' => ['r' => 'Red', 'b' => 'Black'],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r',
                'checked' => 'checked',
            ]],
            ['label' => ['class' => 'active', 'for' => 'crayons-color-r']],
            'Red',
            '/label',
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b',
            ]],
            ['label' => ['class' => '', 'for' => 'crayons-color-b']],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering inputs with the complex option form.
     */
    public function testRenderComplex(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'options' => [
                ['value' => 'r', 'text' => 'Red', 'id' => 'my_id'],
                ['value' => 'b', 'text' => 'Black', 'id' => 'my_id_2', 'data-test' => 'test'],
            ],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'my_id']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'my_id',
            ]],
            'Red',
            '/label',
            ['label' => ['for' => 'my_id_2']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'my_id_2',
                'data-test' => 'test',
            ]],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering inputs with label options
     */
    public function testRenderComplexLabelAttributes(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Crayons[color]',
            'options' => [
                ['value' => 'r', 'text' => 'Red', 'label' => ['style' => 'color:red']],
                ['value' => 'b', 'text' => 'Black', 'label' => ['data-test' => 'yes']],
            ],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'crayons-color-r', 'style' => 'color:red']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r',
            ]],
            'Red',
            '/label',
            ['label' => ['for' => 'crayons-color-b', 'data-test' => 'yes']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'b',
                'id' => 'crayons-color-b',
            ]],
            'Black',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that id suffixes are generated to not collide
     */
    public function testRenderIdSuffixGeneration(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Thing[value]',
            'options' => ['a>b' => 'First', 'a<b' => 'Second'],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'thing-value-a-b']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Thing[value]',
                'value' => 'a&gt;b',
                'id' => 'thing-value-a-b',
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
     */
    public function testRenderBooleanishValues(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'Model[field]',
            'options' => ['1' => 'Yes', '0' => 'No'],
            'val' => '0',
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
     */
    public function testRenderRequiredAndFormAttribute(): void
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
     */
    public function testRenderEmptyOption(): void
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
                'id' => 'crayons-color',
            ]],
            'empty',
            '/label',
            ['label' => ['for' => 'crayons-color-r']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r',
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
                'id' => 'crayons-color',
            ]],
            'Choose one',
            '/label',
            ['label' => ['for' => 'crayons-color-r']],
            ['input' => [
                'type' => 'radio',
                'name' => 'Crayons[color]',
                'value' => 'r',
                'id' => 'crayons-color-r',
            ]],
            'Red',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering the input inside the label.
     */
    public function testRenderInputInsideLabel(): void
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
                'id' => 'crayons-color-r',
            ]],
            'Red',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test render() and selected inputs.
     */
    public function testRenderSelected(): void
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
            ],
        ];
        $result = $radio->render($data, $this->context);
        $expected = [
            ['label' => ['for' => 'versions-ver-1']],
            ['input' => [
                'id' => 'versions-ver-1',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1',
                'checked' => 'checked',
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
            ['label' => ['for' => 'versions-ver-2']],
            ['input' => [
                'id' => 'versions-ver-2',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '2',
            ]],
            'two',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering with disable inputs
     */
    public function testRenderDisabled(): void
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
                'disabled' => 'disabled',
            ]],
            'one',
            '/label',
            ['label' => ['for' => 'versions-ver-1x']],
            ['input' => [
                'id' => 'versions-ver-1x',
                'name' => 'Versions[ver]',
                'type' => 'radio',
                'value' => '1x',
                'disabled' => 'disabled',
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
                'disabled' => 'disabled',
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
     */
    public function testRenderLabelOptions(): void
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
            ],
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
     */
    public function testRenderContainerTemplate(): void
    {
        $this->templates->add([
            'radioWrapper' => '<div class="radio">{{input}}{{label}}</div>',
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
        $this->assertStringContainsString(
            '<div class="radio"><input type="radio"',
            $result
        );
        $this->assertStringContainsString(
            '</label></div>',
            $result
        );
    }

    /**
     * Ensure that template vars work.
     */
    public function testRenderTemplateVars(): void
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
            ],
        ];
        $result = $radio->render($data, $this->context);
        $this->assertStringContainsString('data-var="wrap-var"><label', $result);
        $this->assertStringContainsString('type="radio" data-i="i-var"', $result);
        $this->assertStringContainsString('one x l-var wrap-var</label>', $result);
        $this->assertStringContainsString('two  wrap-var</label>', $result);
    }

    /**
     * testRenderCustomAttributes method
     *
     * Test render with custom attributes.
     */
    public function testRenderCustomAttributes(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $radio = new RadioWidget($this->templates, $label);
        $result = $radio->render([
            'name' => 'Model[field]',
            'label' => null,
            'options' => ['option A', 'option B'],
            'class' => 'my-class',
            'data-ref' => 'custom-attr',
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
                    'data-ref' => 'custom-attr',
                ],
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
                    'data-ref' => 'custom-attr',
                ],
            ],
            'option B',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRenderExplicitId method
     *
     * Test that the id passed is actually used
     * Issue: https://github.com/cakephp/cakephp/issues/13342
     */
    public function testRenderExplicitId(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $input = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'field',
            'options' => ['value1', 'value2', -1 => 'negative'],
            'id' => 'alternative-id',
            'idPrefix' => 'willBeIgnored',
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            [
                'label' => ['for' => 'alternative-id-0'],
                'input' => ['type' => 'radio', 'name' => 'field', 'value' => '0', 'id' => 'alternative-id-0'],
            ],
            'value1',
            '/label',
            [
                'label' => ['for' => 'alternative-id-1'],
                'input' => ['type' => 'radio', 'name' => 'field', 'value' => '1', 'id' => 'alternative-id-1'],
            ],
            'value2',
            '/label',
            [
                'label' => ['for' => 'alternative-id--1'],
                'input' => ['type' => 'radio', 'name' => 'field', 'value' => '-1', 'id' => 'alternative-id--1'],
            ],
            'negative',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'name' => 'field',
            'options' => ['value1', 'value2'],
            'idPrefix' => 'formprefix',
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            [
                'label' => ['for' => 'formprefix-field-0'],
                'input' => ['type' => 'radio', 'name' => 'field', 'value' => '0', 'id' => 'formprefix-field-0'],
            ],
            'value1',
            '/label',
            [
                'label' => ['for' => 'formprefix-field-1'],
                'input' => ['type' => 'radio', 'name' => 'field', 'value' => '1', 'id' => 'formprefix-field-1'],
            ],
            'value2',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRenderSelectedClass method
     *
     * Test that the custom selected class is passed to label
     * Issue: https://github.com/cakephp/cakephp/issues/11249
     */
    public function testRenderSelectedClass(): void
    {
        $this->templates->add(['selectedClass' => 'active']);

        $label = new NestingLabelWidget($this->templates);
        $input = new RadioWidget($this->templates, $label);
        $data = [
            'name' => 'field',
            'options' => ['value1' => 'title1'],
            'val' => 'value1',
            'label' => ['title' => 'my label'],
        ];
        $result = $input->render($data, $this->context);

        $expected = [
            ['label' => [
                'title' => 'my label',
                'class' => 'active',
                'for' => 'field-value1',
            ]],
        ];

        $this->assertHtml($expected, $result);
    }
}
