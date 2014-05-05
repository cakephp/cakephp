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
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\TemplateTask;
use Cake\Core\App;
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

		$this->Task = $this->getMock('Cake\Console\Command\Task\TemplateTask',
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
 * test finding themes installed in
 *
 * @return void
 */
	public function testFindingInstalledThemesForBake() {
		$consoleLibs = CAKE . 'Console' . DS;
		$this->Task->initialize();
		$this->assertPathEquals($this->Task->templatePaths['default'], $consoleLibs . 'Templates/default/');
	}

/**
 * test using an invalid theme name.
 *
 * @expectedException \RuntimeException
 * @expectedExceptionMessage Unable to locate "nope" bake theme
 * @return void
 */
	public function testGetThemePathInvalid() {
		$defaultTheme = CAKE . 'Console/Templates/default/';
		$this->Task->templatePaths = ['default' => $defaultTheme];
		$this->Task->params['theme'] = 'nope';
		$this->Task->getThemePath();
	}

/**
 * test getting the correct theme name. Ensure that with only one theme, or a theme param
 * that the user is not bugged. If there are more, find and return the correct theme name
 *
 * @return void
 */
	public function testGetThemePath() {
		$defaultTheme = CAKE . 'Console/Templates/default/';
		$this->Task->templatePaths = ['default' => $defaultTheme];

		$result = $this->Task->getThemePath();
		$this->assertEquals($defaultTheme, $result);

		$this->Task->templatePaths = ['other' => '/some/path', 'default' => $defaultTheme];
		$this->Task->params['theme'] = 'other';
		$result = $this->Task->getThemePath();
		$this->assertEquals('/some/path', $result);

		$this->Task->params = array();
		$result = $this->Task->getThemePath();
		$this->assertEquals($defaultTheme, $result);
		$this->assertEquals('default', $this->Task->params['theme']);
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
 * test generate with a missing template in the chosen theme.
 * ensure fallback to default works.
 *
 * @return void
 */
	public function testGenerateWithTemplateFallbacks() {
		$this->Task->initialize();
		$this->Task->params['theme'] = 'test';
		$this->Task->set(array(
			'name' => 'Article',
			'model' => 'Article',
			'table' => 'articles',
			'import' => false,
			'records' => false,
			'schema' => '',
			'namespace' => ''
		));
		$result = $this->Task->generate('classes', 'fixture');
		$this->assertRegExp('/ArticleFixture extends .*TestFixture/', $result);
	}
}
