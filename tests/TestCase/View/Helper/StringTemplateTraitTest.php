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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\Helper\StringTemplate;
use Cake\View\Helper\StringTemplateTrait;

/**
 * StringTemplateTraitTest class
 *
 */
class StringTemplateTraitTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Template = $this->getObjectForTrait('\Cake\View\Helper\StringTemplateTrait');
	}

/**
 * testInitStringTemplates
 *
 * @return void
 */
	public function testInitStringTemplates() {
		$templates = [
			'text' => '<p>{{text}}</p>',
		];
		$this->Template->initStringTemplates($templates);

		$result = $this->Template->templates(null);
		$this->assertEquals($result, [
			'attribute' => '{{name}}="{{value}}"',
			'compactAttribute' => '{{name}}="{{value}}"',
			'text' => '<p>{{text}}</p>'
		]);
	}

/**
 * test settings['templates']
 *
 * @return void
 */
	public function testInitStringTemplatesArrayForm() {
		$this->Template->settings['templates'] = [
			'text' => '<p>{{text}}</p>',
		];
		$this->Template->initStringTemplates();

		$result = $this->Template->templates(null);
		$this->assertEquals($result, [
			'attribute' => '{{name}}="{{value}}"',
			'compactAttribute' => '{{name}}="{{value}}"',
			'text' => '<p>{{text}}</p>'
		]);
	}

/**
 * testFormatStringTemplate
 *
 * @return void
 */
	public function testFormatStringTemplate() {
		$templates = [
			'text' => '<p>{{text}}</p>',
		];
		$this->Template->initStringTemplates($templates);
		$result = $this->Template->formatTemplate('text', [
			'text' => 'CakePHP'
		]);
		$this->assertEquals($result, '<p>CakePHP</p>');
	}

/**
 * testGetTemplater
 *
 * @return void
 */
	public function testGetTemplater() {
		$templates = [
			'text' => '<p>{{text}}</p>',
		];
		$this->Template->initStringTemplates($templates);
		$result = $this->Template->getTemplater();
		$this->assertInstanceOf('\Cake\View\StringTemplate', $result);
	}

}
