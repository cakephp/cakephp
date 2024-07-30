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
namespace Cake\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use TestApp\View\TestStringTemplate;

/**
 * StringTemplateTraitTest class
 */
class StringTemplateTraitTest extends TestCase
{
    /**
     * @var \TestApp\View\TestStringTemplate
     */
    protected $Template;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->Template = new TestStringTemplate();
    }

    /**
     * testInitStringTemplates
     */
    public function testInitStringTemplates(): void
    {
        $templates = [
            'text' => '<p>{{text}}</p>',
        ];
        $this->Template->setTemplates($templates);

        $this->assertSame(
            [
                'text' => '<p>{{text}}</p>',
            ],
            $this->Template->getTemplates(),
            'newly added template should be included in template list'
        );
    }

    /**
     * test settings['templates']
     */
    public function testInitStringTemplatesArrayForm(): void
    {
        $this->Template->setConfig(
            'templates.text',
            '<p>{{text}}</p>'
        );

        $this->assertSame(
            [
                'text' => '<p>{{text}}</p>',
            ],
            $this->Template->getTemplates(),
            'Configured templates should be included in template list'
        );
    }

    /**
     * testFormatStringTemplate
     */
    public function testFormatStringTemplate(): void
    {
        $templates = [
            'text' => '<p>{{text}}</p>',
        ];
        $this->Template->setTemplates($templates);
        $result = $this->Template->formatTemplate('text', [
            'text' => 'CakePHP',
        ]);
        $this->assertSame(
            '<p>CakePHP</p>',
            $result
        );
    }

    /**
     * testGetTemplater
     */
    public function testGetTemplater(): void
    {
        $templates = [
            'text' => '<p>{{text}}</p>',
        ];
        $this->Template->setTemplates($templates);
        $result = $this->Template->templater();
        $this->assertInstanceOf(StringTemplate::class, $result);
    }
}
