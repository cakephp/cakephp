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
use Cake\View\Widget\LabelWidget;
use Cake\View\Widget\MultiCheckboxWidget;
use Cake\View\Widget\NestingLabelWidget;

/**
 * MultiCheckbox test case.
 */
class MultiCheckboxWidgetTest extends TestCase
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
            'label' => '<label{{attrs}}>{{text}}</label>',
            'nestingLabel' => '<label{{attrs}}>{{input}}{{text}}</label>',
            'checkboxWrapper' => '<div class="checkbox">{{input}}{{label}}</div>',
            'multicheckboxWrapper' => '<fieldset{{attrs}}>{{content}}</fieldset>',
            'multicheckboxTitle' => '<legend>{{text}}</legend>',
            'selectedClass' => 'selected',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = new NullContext([]);
    }

    /**
     * Test render simple option sets.
     */
    public function testRenderSimple(): void
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                1 => 'CakePHP',
                2 => 'Development',
            ],
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
     */
    public function testRenderComplex(): void
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
            ],
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
     */
    public function testRenderEscaping(): void
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'Tags[id]',
            'options' => [
                '>' => '>>',
            ],
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
     */
    public function testRenderSelected(): void
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
            'disabled' => false,
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
                'checked' => 'checked',
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
     */
    public function testRenderDisabled(): void
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
                'disabled' => 'disabled',
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
                'disabled' => 'disabled',
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
            'disabled' => [1],
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Tags[id][]',
                'value' => 1,
                'id' => 'tags-id-1',
                'disabled' => 'disabled',
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
     */
    public function testRenderTemplateVars(): void
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
                'data-var' => 'default',
            ]],
            ['label' => ['for' => 'tags-id-1x']],
            'Development default',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test label = false with checkboxWrapper option.
     */
    public function testNoLabelWithCheckboxWrapperOption(): void
    {
        $data = [
            'label' => false,
            'name' => 'test',
            'options' => [
                1 => 'A',
                2 => 'B',
            ],
        ];

        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'test[]',
                'value' => 1,
                'id' => 'test-1',
            ]],
            ['label' => ['for' => 'test-1']],
            'A',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'test[]',
                'value' => '2',
                'id' => 'test-2',
            ]],
            ['label' => ['for' => 'test-2']],
            'B',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $templates = [
            'checkboxWrapper' => '<div class="checkbox">{{label}}</div>',
        ];
        $this->templates->add($templates);
        $result = $input->render($data, $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'test[]',
                'value' => 1,
                'id' => 'test-1',
            ]],
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'test[]',
                'value' => '2',
                'id' => 'test-2',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering without input nesting inspite of using NestingLabelWidget
     */
    public function testRenderNestingLabelWidgetWithoutInputNesting(): void
    {
        $label = new NestingLabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'tags',
            'label' => [
                'input' => false,
            ],
            'options' => [
                1 => 'CakePHP',
            ],
        ];
        $result = $input->render($data, $this->context);

        $expected = [
            ['div' => ['class' => 'checkbox']],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'tags[]',
                'value' => 1,
                'id' => 'tags-1',
            ]],
            ['label' => ['for' => 'tags-1']],
            'CakePHP',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with groupings.
     */
    public function testRenderGrouped(): void
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
                ],
            ],
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
     */
    public function testRenderPartialGrouped(): void
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
            ],
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

    /**
     * testRenderCustomAttributes method
     *
     * Test render with custom attributes
     */
    public function testRenderCustomAttributes(): void
    {
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $result = $input->render([
            'name' => 'category',
            'options' => ['1', '2'],
            'class' => 'my-class',
            'data-ref' => 'custom-attr',
        ], $this->context);
        $expected = [
            ['div' => ['class' => 'checkbox']],
            [
                'input' => [
                    'type' => 'checkbox',
                    'name' => 'category[]',
                    'value' => '0',
                    'id' => 'category-0',
                    'class' => 'my-class',
                    'data-ref' => 'custom-attr',
                ],
            ],
            ['label' => ['for' => 'category-0']],
            '1',
            '/label',
            '/div',

            ['div' => ['class' => 'checkbox']],
            [
                'input' => [
                    'type' => 'checkbox',
                    'name' => 'category[]',
                    'value' => '1',
                    'id' => 'category-1',
                    'class' => 'my-class',
                    'data-ref' => 'custom-attr',
                ],
            ],
            ['label' => ['for' => 'category-1']],
            '2',
            '/label',
            '/div',
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
        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'field',
            'options' => ['value1', 'value2'],
            'id' => 'alternative-id',
            'idPrefix' => 'willBeIgnored',
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            [
                'div' => ['class' => 'checkbox'],
                'input' => ['type' => 'checkbox', 'name' => 'field[]', 'value' => '0', 'id' => 'alternative-id-0'],
                'label' => ['for' => 'alternative-id-0'],
            ],
            'value1',
            '/label',
            '/div',
            [
                'div' => ['class' => 'checkbox'],
                'input' => ['type' => 'checkbox', 'name' => 'field[]', 'value' => '1', 'id' => 'alternative-id-1'],
                'label' => ['for' => 'alternative-id-1'],
            ],
            'value2',
            '/label',
            '/div',
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
                'div' => ['class' => 'checkbox'],
                'input' => ['type' => 'checkbox', 'name' => 'field[]', 'value' => '0', 'id' => 'formprefix-field-0'],
                'label' => ['for' => 'formprefix-field-0'],
            ],
            'value1',
            '/label',
            '/div',
            [
                'div' => ['class' => 'checkbox'],
                'input' => ['type' => 'checkbox', 'name' => 'field[]', 'value' => '1', 'id' => 'formprefix-field-1'],
                'label' => ['for' => 'formprefix-field-1'],
            ],
            'value2',
            '/label',
            '/div',
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

        $label = new LabelWidget($this->templates);
        $input = new MultiCheckboxWidget($this->templates, $label);
        $data = [
            'name' => 'field',
            'options' => ['value1', 'value2'],
            'id' => 'alternative-id',
            'idPrefix' => 'willBeIgnored',
        ];
        $result = $input->render($data, $this->context);

        $data = [
            'name' => 'field',
            'options' => [1 => 'value1', 2 => 'value2'],
            'val' => 1,
            'label' => ['title' => 'my label'],
        ];
        $result = $input->render($data, $this->context);

        $expected = [
            [
                'div' => ['class' => 'checkbox'],
                'input' => ['type' => 'checkbox', 'name' => 'field[]', 'value' => '1', 'checked' => 'checked', 'id' => 'field-1'],
                'label' => ['title' => 'my label', 'for' => 'field-1', 'class' => 'active'],
            ],
            'value1',
            '/label',
        ];

        $this->assertHtml($expected, $result);
    }
}
