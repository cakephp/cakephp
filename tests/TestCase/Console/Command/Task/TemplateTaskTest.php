<?php
/**
 * TemplateTask file
 *
 * Test Case for TemplateTask generation shell task
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\TemplateTask;
use Cake\Core\App;
use Cake\TestSuite\TestCase;

/**
 * TemplateTaskTest class
 *
 */
class TemplateTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\TemplateTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($out, $out, $in)
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
		$consoleLibs = CAKE . 'Console/';
		$this->Task->initialize();
		$this->assertEquals($this->Task->templatePaths['default'], $consoleLibs . 'Templates/default/');
	}

/**
 * test getting the correct theme name. Ensure that with only one theme, or a theme param
 * that the user is not bugged. If there are more, find and return the correct theme name
 *
 * @return void
 */
	public function testGetThemePath() {
		$defaultTheme = CAKE . 'Console/Templates/default/';
		$this->Task->templatePaths = array('default' => $defaultTheme);

		$this->Task->expects($this->exactly(1))->method('in')->will($this->returnValue('1'));

		$result = $this->Task->getThemePath();
		$this->assertEquals($defaultTheme, $result);

		$this->Task->templatePaths = array('other' => '/some/path', 'default' => $defaultTheme);
		$this->Task->params['theme'] = 'other';
		$result = $this->Task->getThemePath();
		$this->assertEquals('/some/path', $result);

		$this->Task->params = array();
		$result = $this->Task->getThemePath();
		$this->assertEquals('/some/path', $result);
		$this->assertEquals('other', $this->Task->params['theme']);
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
