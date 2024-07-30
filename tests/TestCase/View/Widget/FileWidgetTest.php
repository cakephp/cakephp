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
use Cake\View\Widget\FileWidget;

/**
 * File input test.
 */
class FileWidgetTest extends TestCase
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
     * setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $templates = [
            'file' => '<input type="file" name="{{name}}"{{attrs}}>',
        ];
        $this->templates = new StringTemplate($templates);

        $this->context = new NullContext([]);
    }

    /**
     * Test render in a simple case.
     */
    public function testRenderSimple(): void
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
     */
    public function testRenderAttributes(): void
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
     */
    public function testRenderTemplateVars(): void
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
                'custom' => 'value',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test secureFields
     */
    public function testSecureFields(): void
    {
        $input = new FileWidget($this->templates);
        $data = ['name' => 'image', 'required' => true, 'val' => 'nope'];
        $this->assertSame(['image'], $input->secureFields($data));

        $this->assertSame(
            ['image'],
            $input->secureFields($data)
        );
    }
}
