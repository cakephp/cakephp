<?php
/* SVN FILE: $Id$ */
/**
 * ExtractTaskTest file
 *
 * Test Case for i18n extraction shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', array('Shell', 'Folder'));

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('ExtractTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'extract.php';
}

Mock::generatePartial(
				'ShellDispatcher', 'TestExtractTaskMockShellDispatcher',
				array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
				);
/**
 * ExtractTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ExtractTaskTest extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->Dispatcher =& new TestExtractTaskMockShellDispatcher();
		$this->Task =& new ExtractTask($this->Dispatcher);
	}
/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function tearDown() {
		ClassRegistry::flush();
	}
/**
 * testExecute method
 *
 * @return void
 * @access public
 */
	function testExecute() {
		$path = TMP . 'extract_task_test';
		$folder1 = $path . DS . 'locale';

		new Folder($path, true);
		new Folder($folder1, true);

		$this->Task->interactive = false;

		$this->Task->params['path'] = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'pages';
		$this->Task->params['output'] = $path . DS;
		$this->Task->Dispatch->expectNever('stderr');
		$this->Task->Dispatch->expectNever('_stop');
		$this->Task->execute();
		$this->assertTrue(file_exists($path . DS . 'default.pot'));
		$result = file_get_contents($path . DS . 'default.pot');

		$pattern = '/"Content-Type\: text\/plain; charset\=utf-8/';
		$this->assertPattern($pattern, $result);
		$pattern = '/"Content-Transfer-Encoding\: 8bit/';
		$this->assertPattern($pattern, $result);
		$pattern = '/"Plural-Forms\: nplurals\=INTEGER; plural\=EXPRESSION;/';
		$this->assertPattern($pattern, $result);

		$pattern = '/msgid "Your tmp directory is writable."\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Your tmp directory is NOT writable."\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "The %s is being used for caching. To change the config edit ';
		$pattern .= 'APP\/config\/core.php "\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Your cache is NOT working. Please check ';
		$pattern .= 'the settings in APP\/config\/core.php"\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Your database configuration file is present."\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Your database configuration file is NOT present."\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Rename config\/database.php.default to ';
		$pattern .= 'config\/database.php"\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Cake is able to connect to the database."\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Cake is NOT able to connect to the database."\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "Editing this Page"\nmsgstr ""\n/';
		$this->assertPattern($pattern, $result);
		$pattern = '/msgid "To change the content of this page, edit: %s.*To change its layout, ';
		$pattern .= 'edit: %s.*You can also add some CSS styles for your pages at: %s"\nmsgstr ""/s';
		$this->assertPattern($pattern, $result);

		$Folder = new Folder($path);
		$Folder->delete();
	}
}
?>