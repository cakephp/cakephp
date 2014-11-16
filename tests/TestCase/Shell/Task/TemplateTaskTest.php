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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\App;
use Cake\Shell\Task\TemplateTask;
use Cake\TestSuite\TestCase;

/**
 * TemplateTaskTest class
 */
class TemplateTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Shell\Task\TemplateTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($io)
		);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * test finding templates installed in
 *
 * @return void
 */
	public function testFindingInstalledTemplatesForBake() {
		$consoleLibs = CAKE . 'Template' . DS;
		$this->Task->initialize();
		$this->assertPathEquals($this->Task->templatePaths['default'], $consoleLibs . 'Bake/default/');
	}

/**
 * test using an invalid template name.
 *
 * @expectedException \RuntimeException
 * @expectedExceptionMessage Unable to locate "nope" bake template
 * @return void
 */
	public function testGetTemplatePathInvalid() {
		$defaultTemplate = CAKE . 'Template/Bake/default/';
		$this->Task->templatePaths = ['default' => $defaultTemplate];
		$this->Task->params['template'] = 'nope';
		$this->Task->getTemplatePath();
	}

/**
 * test getting the correct template name. Ensure that with only one template, or a template param
 * that the user is not bugged. If there are more, find and return the correct template name
 *
 * @return void
 */
	public function testGetTemplatePath() {
		$defaultTemplate = CAKE . 'Template/Bake/default/';
		$this->Task->templatePaths = ['default' => $defaultTemplate];

		$result = $this->Task->getTemplatePath();
		$this->assertEquals($defaultTemplate, $result);

		$this->Task->templatePaths = ['other' => '/some/path', 'default' => $defaultTemplate];
		$this->Task->params['template'] = 'other';
		$result = $this->Task->getTemplatePath();
		$this->assertEquals('/some/path', $result);

		$this->Task->params = array();
		$result = $this->Task->getTemplatePath();
		$this->assertEquals($defaultTemplate, $result);
		$this->assertEquals('default', $this->Task->params['template']);
	}

/**
 * test generate
 *
 * @return void
 */
	public function testGenerate() {
		$this->Task->initialize();
		$this->Task->expects($this->any())->method('in')->will($this->returnValue(1));

		$result = $this->Task->generate('classes', 'test_object', array('test' => 'foo'));
		$expected = "I got rendered\nfoo";
		$this->assertTextEquals($expected, $result);
	}

/**
 * test generate with a missing template in the chosen template.
 * ensure fallback to default works.
 *
 * @return void
 */
	public function testGenerateWithTemplateFallbacks() {
		$this->Task->initialize();
		$this->Task->params['template'] = 'test';
		$this->Task->set(array(
			'name' => 'Articles',
			'table' => 'articles',
			'import' => false,
			'records' => false,
			'schema' => '',
			'namespace' => ''
		));
		$result = $this->Task->generate('classes', 'fixture');
		$this->assertRegExp('/ArticlesFixture extends .*TestFixture/', $result);
	}
}
