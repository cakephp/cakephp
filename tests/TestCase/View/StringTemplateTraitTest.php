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
namespace Cake\Test\TestCase\View;

use Cake\Core\InstanceConfigTrait;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplateTrait;

/**
 * TestStringTemplate
 */
class TestStringTemplate
{

    use InstanceConfigTrait;
    use StringTemplateTrait;

    /**
     * _defaultConfig
     *
     * @var array
     */
    protected $_defaultConfig = [];
}

/**
 * StringTemplateTraitTest class
 */
class StringTemplateTraitTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Template = new TestStringTemplate;
    }

    /**
     * testInitStringTemplates
     *
     * @return void
     */
    public function testInitStringTemplates()
    {
        $templates = [
            'text' => '<p>{{text}}</p>',
        ];
        $this->Template->templates($templates);

        $this->assertEquals(
            [
                'text' => '<p>{{text}}</p>'
            ],
            $this->Template->templates(),
            'newly added template should be included in template list'
        );
    }

    /**
     * test settings['templates']
     *
     * @return void
     */
    public function testInitStringTemplatesArrayForm()
    {
        $this->Template->config(
            'templates.text',
            '<p>{{text}}</p>'
        );

        $this->assertEquals(
            [
                'text' => '<p>{{text}}</p>'
            ],
            $this->Template->templates(),
            'Configured templates should be included in template list'
        );
    }

    /**
     * testFormatStringTemplate
     *
     * @return void
     */
    public function testFormatStringTemplate()
    {
        $templates = [
            'text' => '<p>{{text}}</p>',
        ];
        $this->Template->templates($templates);
        $result = $this->Template->formatTemplate('text', [
            'text' => 'CakePHP'
        ]);
        $this->assertEquals(
            '<p>CakePHP</p>',
            $result
        );
    }

    /**
     * testGetTemplater
     *
     * @return void
     */
    public function testGetTemplater()
    {
        $templates = [
            'text' => '<p>{{text}}</p>',
        ];
        $this->Template->templates($templates);
        $result = $this->Template->templater();
        $this->assertInstanceOf('Cake\View\StringTemplate', $result);
    }
}
