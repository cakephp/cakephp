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

/**
 * Label test case.
 */
class LabelWidgetTest extends TestCase
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
            'label' => '<label{{attrs}}>{{text}}</label>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = $this->getMock('Cake\View\Form\ContextInterface');
    }

    /**
     * test render
     *
     * @return void
     */
    public function testRender()
    {
        $label = new LabelWidget($this->templates);
        $data = [
            'text' => 'My text',
        ];
        $result = $label->render($data, $this->context);
        $expected = [
            'label' => [],
            'My text',
            '/label'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test render escape
     *
     * @return void
     */
    public function testRenderEscape()
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
            '/label'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test render escape
     *
     * @return void
     */
    public function testRenderAttributes()
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
            '/label'
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
            '/label'
        ];
        $this->assertHtml($expected, $result);
    }
}
