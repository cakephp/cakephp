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

/**
 * Label test case.
 */
class LabelWidgetTest extends TestCase
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
            'label' => '<label{{attrs}}>{{text}}</label>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = new NullContext([]);
    }

    /**
     * test render
     */
    public function testRender(): void
    {
        $label = new LabelWidget($this->templates);
        $data = [
            'text' => 'My text',
        ];
        $result = $label->render($data, $this->context);
        $expected = [
            'label' => [],
            'My text',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test render escape
     */
    public function testRenderEscape(): void
    {
        $label = new LabelWidget($this->templates);
        $data = [
            'text' => 'My > text',
            'for' => 'Some > value',
            'escape' => false,
        ];
        $result = $label->render($data, $this->context);
        $expected = [
            'label' => ['for' => 'Some > value'],
            'My > text',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test render escape
     */
    public function testRenderAttributes(): void
    {
        $label = new LabelWidget($this->templates);
        $data = [
            'text' => 'My > text',
            'for' => 'some-id',
            'id' => 'some-id',
            'data-foo' => 'value',
        ];
        $result = $label->render($data, $this->context);
        $expected = [
            'label' => ['id' => 'some-id', 'data-foo' => 'value', 'for' => 'some-id'],
            'My &gt; text',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure templateVars option is hooked up.
     */
    public function testRenderTemplateVars(): void
    {
        $this->templates->add([
            'label' => '<label custom="{{custom}}" {{attrs}}>{{text}}</label>',
        ]);

        $label = new LabelWidget($this->templates);
        $data = [
            'templateVars' => ['custom' => 'value'],
            'text' => 'Label Text',
        ];
        $result = $label->render($data, $this->context);
        $expected = [
            'label' => ['custom' => 'value'],
            'Label Text',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }
}
