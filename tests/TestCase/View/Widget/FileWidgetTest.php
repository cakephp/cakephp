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
use Cake\View\Widget\FileWidget;

/**
 * File input test.
 */
class FileWidgetTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $templates = [
            'file' => '<input type="file" name="{{name}}"{{attrs}}>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = $this->getMock('Cake\View\Form\ContextInterface');
    }

    /**
     * Test render in a simple case.
     *
     * @return void
     */
    public function testRenderSimple()
    {
        $input = new FileWidget($this->templates);
        $result = $input->render(['name' => 'image'], $this->context);
        $expected = [
            'input' => ['type' => 'file', 'name' => 'image'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with a value
     *
     * @return void
     */
    public function testRenderAttributes()
    {
        $input = new FileWidget($this->templates);
        $data = ['name' => 'image', 'required' => true, 'val' => 'nope'];
        $result = $input->render($data, $this->context);
        $expected = [
            'input' => ['type' => 'file', 'required' => 'required', 'name' => 'image'],
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
            'file' => '<input custom="{{custom}}" type="file" name="{{name}}"{{attrs}}>',
        ]);

        $input = new FileWidget($this->templates);
        $data = [
            'templateVars' => ['custom' => 'value'],
            'name' => 'files',
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            'input' => [
                'type' => 'file',
                'name' => 'files',
                'custom' => 'value'
            ],
        ];
        $this->assertHtml($expected, $result);
    }
}
