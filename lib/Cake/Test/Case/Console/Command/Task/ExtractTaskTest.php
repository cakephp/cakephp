<?php
/**
 * ExtractTaskTest file
 *
 * Test Case for i18n extraction shell task
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP v 1.2.0.7726
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Folder', 'Utility');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('ExtractTask', 'Console/Command/Task');

/**
 * ExtractTaskTest class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ExtractTaskTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock(
			'ExtractTask',
			array('in', 'out', 'err', '_stop'),
			array($out, $out, $in)
		);
		$this->path = TMP . 'tests' . DS . 'extract_task_test';
		new Folder($this->path . DS . 'locale', true);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);

		$Folder = new Folder($this->path);
		$Folder->delete();
		CakePlugin::unload();
	}

/**
 * testExecute method
 *
 * @return void
 */
	public function testExecute() {
		$this->Task->interactive = false;

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages';
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['extract-core'] = 'no';
		$this->Task->expects($this->never())->method('err');
		$this->Task->expects($this->any())->method('in')
			->will($this->returnValue('y'));
		$this->Task->expects($this->never())->method('_stop');

		$this->Task->execute();
		$this->assertTrue(file_exists($this->path . DS . 'default.pot'));
		$result = file_get_contents($this->path . DS . 'default.pot');

		$this->assertFalse(file_exists($this->path . DS . 'cake.pot'));

		$pattern = '/"Content-Type\: text\/plain; charset\=utf-8/';
		$this->assertRegExp($pattern, $result);
		$pattern = '/"Content-Transfer-Encoding\: 8bit/';
		$this->assertRegExp($pattern, $result);
		$pattern = '/"Plural-Forms\: nplurals\=INTEGER; plural\=EXPRESSION;/';
		$this->assertRegExp($pattern, $result);

		// home.ctp
		$pattern = '/msgid "Your tmp directory is writable."\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Your tmp directory is NOT writable."\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "The %s is being used for caching. To change the config edit ';
		$pattern .= 'APP\/config\/core.php "\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Your cache is NOT working. Please check ';
		$pattern .= 'the settings in APP\/config\/core.php"\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Your database configuration file is present."\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Your database configuration file is NOT present."\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Rename config\/database.php.default to ';
		$pattern .= 'config\/database.php"\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Cake is able to connect to the database."\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Cake is NOT able to connect to the database."\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "Editing this Page"\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "To change the content of this page, create: APP\/views\/pages\/home\.ctp/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/To change its layout, create: APP\/views\/layouts\/default\.ctp\./s';
		$this->assertRegExp($pattern, $result);

		// extract.ctp
		$pattern = '/\#: (\\\\|\/)extract\.ctp:15;6\n';
		$pattern .= 'msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/msgid "You have %d new message."\nmsgstr ""/';
		$this->assertNotRegExp($pattern, $result, 'No duplicate msgid');

		$pattern = '/\#: (\\\\|\/)extract\.ctp:7\n';
		$pattern .= 'msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/\#: (\\\\|\/)extract\.ctp:14\n';
		$pattern .= '\#: (\\\\|\/)home\.ctp:68\n';
		$pattern .= 'msgid "Editing this Page"\nmsgstr ""/';
		$this->assertRegExp($pattern, $result);

		$pattern = '/\#: (\\\\|\/)extract\.ctp:22\nmsgid "';
		$pattern .= 'Hot features!';
		$pattern .= '\\\n - No Configuration: Set-up the database and let the magic begin';
		$pattern .= '\\\n - Extremely Simple: Just look at the name...It\'s Cake';
		$pattern .= '\\\n - Active, Friendly Community: Join us #cakephp on IRC. We\'d love to help you get started';
		$pattern .= '"\nmsgstr ""/';
		$this->assertRegExp($pattern, $result);

		$this->assertContains('msgid "double \\"quoted\\""', $result, 'Strings with quotes not handled correctly');
		$this->assertContains("msgid \"single 'quoted'\"", $result, 'Strings with quotes not handled correctly');

		$pattern = '/\#: (\\\\|\/)extract\.ctp:34\nmsgid "letter"/';
		$this->assertRegExp($pattern, $result, 'Strings with context should not overwrite strings without context');

		$pattern = '/\#: (\\\\|\/)extract\.ctp:35;37\nmsgctxt "A"\nmsgid "letter"/';
		$this->assertRegExp($pattern, $result, 'Should contain string with context "A"');

		$pattern = '/\#: (\\\\|\/)extract\.ctp:36\nmsgctxt "B"\nmsgid "letter"/';
		$this->assertRegExp($pattern, $result, 'Should contain string with context "B"');

		$pattern = '/\#: (\\\\|\/)extract\.ctp:38\nmsgid "%d letter"\nmsgid_plural "%d letters"/';
		$this->assertRegExp($pattern, $result, 'Plural strings with context should not overwrite strings without context');

		$pattern = '/\#: (\\\\|\/)extract\.ctp:39\nmsgctxt "A"\nmsgid "%d letter"\nmsgid_plural "%d letters"/';
		$this->assertRegExp($pattern, $result, 'Should contain plural string with context "A"');

		// extract.ctp - reading the domain.pot
		$result = file_get_contents($this->path . DS . 'domain.pot');

		$pattern = '/msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
		$this->assertNotRegExp($pattern, $result);
		$pattern = '/msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
		$this->assertNotRegExp($pattern, $result);

		$pattern = '/msgid "You have %d new message \(domain\)."\nmsgid_plural "You have %d new messages \(domain\)."/';
		$this->assertRegExp($pattern, $result);
		$pattern = '/msgid "You deleted %d message \(domain\)."\nmsgid_plural "You deleted %d messages \(domain\)."/';
		$this->assertRegExp($pattern, $result);
	}

/**
 * testExtractCategory method
 *
 * @return void
 */
	public function testExtractCategory() {
		$this->Task->interactive = false;

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages';
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['extract-core'] = 'no';
		$this->Task->params['merge'] = 'no';
		$this->Task->expects($this->never())->method('err');
		$this->Task->expects($this->any())->method('in')
			->will($this->returnValue('y'));
		$this->Task->expects($this->never())->method('_stop');

		$this->Task->execute();
		$this->assertTrue(file_exists($this->path . DS . 'LC_TIME' . DS . 'default.pot'));

		$result = file_get_contents($this->path . DS . 'default.pot');

		$pattern = '/\#: .*extract\.ctp:31\n/';
		$this->assertNotRegExp($pattern, $result);
	}

/**
 * test exclusions
 *
 * @return void
 */
	public function testExtractWithExclude() {
		$this->Task->interactive = false;

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS . 'View';
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['exclude'] = 'Pages,Layouts';
		$this->Task->params['extract-core'] = 'no';

		$this->Task->expects($this->any())->method('in')
			->will($this->returnValue('y'));

		$this->Task->execute();
		$this->assertTrue(file_exists($this->path . DS . 'default.pot'));
		$result = file_get_contents($this->path . DS . 'default.pot');

		$pattern = '/\#: .*extract\.ctp:6\n/';
		$this->assertNotRegExp($pattern, $result);

		$pattern = '/\#: .*default\.ctp:26\n/';
		$this->assertNotRegExp($pattern, $result);
	}

/**
 * test extract can read more than one path.
 *
 * @return void
 */
	public function testExtractMultiplePaths() {
		$this->Task->interactive = false;

		$this->Task->params['paths'] =
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages,' .
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Posts';

		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['extract-core'] = 'no';
		$this->Task->expects($this->never())->method('err');
		$this->Task->expects($this->never())->method('_stop');
		$this->Task->execute();

		$result = file_get_contents($this->path . DS . 'default.pot');

		$pattern = '/msgid "Add User"/';
		$this->assertRegExp($pattern, $result);
	}

/**
 * Tests that it is possible to exclude plugin paths by enabling the param option for the ExtractTask
 *
 * @return void
 */
	public function testExtractExcludePlugins() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		$this->out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ExtractTask',
			array('_isExtractingApp', '_extractValidationMessages', 'in', 'out', 'err', 'clear', '_stop'),
			array($this->out, $this->out, $this->in)
		);
		$this->Task->expects($this->exactly(2))->method('_isExtractingApp')->will($this->returnValue(true));

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS;
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['exclude-plugins'] = true;

		$this->Task->execute();
		$result = file_get_contents($this->path . DS . 'default.pot');
		$this->assertNotRegExp('#TestPlugin#', $result);
	}

/**
 * Test that is possible to extract messages form a single plugin
 *
 * @return void
 */
	public function testExtractPlugin() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));

		$this->out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ExtractTask',
			array('_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'),
			array($this->out, $this->out, $this->in)
		);

		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['plugin'] = 'TestPlugin';

		$this->Task->execute();
		$result = file_get_contents($this->path . DS . 'default.pot');
		$this->assertNotRegExp('#Pages#', $result);
		$this->assertContains('translate.ctp:1', $result);
		$this->assertContains('This is a translatable string', $result);
		$this->assertContains('I can haz plugin model validation message', $result);
	}

/**
 * Tests that the task will inspect application models and extract the validation messages from them
 *
 * @return void
 */
	public function testExtractModelValidation() {
		App::build(array(
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		$this->out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ExtractTask',
			array('_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'),
			array($this->out, $this->out, $this->in)
		);
		$this->Task->expects($this->exactly(2))->method('_isExtractingApp')->will($this->returnValue(true));

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS;
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['extract-core'] = 'no';
		$this->Task->params['exclude-plugins'] = true;
		$this->Task->params['ignore-model-validation'] = false;

		$this->Task->execute();
		$result = file_get_contents($this->path . DS . 'default.pot');

		$pattern = preg_quote('#Model/PersisterOne.php:validation for field title#', '\\');
		$this->assertRegExp($pattern, $result);

		$pattern = preg_quote('#Model/PersisterOne.php:validation for field body#', '\\');
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post title is required"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "You may enter up to %s chars \(minimum is %s chars\)"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post body is required"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post body is super required"#';
		$this->assertRegExp($pattern, $result);

		$this->assertContains('msgid "double \\"quoted\\" validation"', $result, 'Strings with quotes not handled correctly');
		$this->assertContains("msgid \"single 'quoted' validation\"", $result, 'Strings with quotes not handled correctly');
	}

/**
 *  Tests that the task will inspect application models and extract the validation messages from them
 *	while using a custom validation domain for the messages set on the model itself
 *
 * @return void
 */
	public function testExtractModelValidationWithDomainInModel() {
		App::build(array(
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Model' . DS)
		));
		$this->out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ExtractTask',
			array('_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'),
			array($this->out, $this->out, $this->in)
		);
		$this->Task->expects($this->exactly(2))->method('_isExtractingApp')->will($this->returnValue(true));

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS;
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['extract-core'] = 'no';
		$this->Task->params['exclude-plugins'] = true;
		$this->Task->params['ignore-model-validation'] = false;

		$this->Task->execute();
		$result = file_get_contents($this->path . DS . 'test_plugin.pot');

		$pattern = preg_quote('#Plugin/TestPlugin/Model/TestPluginPost.php:validation for field title#', '\\');
		$this->assertRegExp($pattern, $result);

		$pattern = preg_quote('#Plugin/TestPlugin/Model/TestPluginPost.php:validation for field body#', '\\');
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post title is required"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post body is required"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post body is super required"#';
		$this->assertRegExp($pattern, $result);
	}

/**
 *  Test that the extract shell can obtain validation messages from models inside a specific plugin
 *
 * @return void
 */
	public function testExtractModelValidationInPlugin() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		$this->out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ExtractTask',
			array('_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'),
			array($this->out, $this->out, $this->in)
		);

		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['ignore-model-validation'] = false;
		$this->Task->params['plugin'] = 'TestPlugin';

		$this->Task->execute();
		$result = file_get_contents($this->path . DS . 'test_plugin.pot');

		$pattern = preg_quote('#Model/TestPluginPost.php:validation for field title#', '\\');
		$this->assertRegExp($pattern, $result);

		$pattern = preg_quote('#Model/TestPluginPost.php:validation for field body#', '\\');
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post title is required"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post body is required"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#msgid "Post body is super required"#';
		$this->assertRegExp($pattern, $result);

		$pattern = '#Plugin/TestPlugin/Model/TestPluginPost.php:validation for field title#';
		$this->assertNotRegExp($pattern, $result);
	}

/**
 *  Test that the extract shell overwrites existing files with the overwrite parameter
 *
 * @return void
 */
	public function testExtractOverwrite() {
		$this->Task->interactive = false;

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS;
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['extract-core'] = 'no';
		$this->Task->params['overwrite'] = true;

		file_put_contents($this->path . DS . 'default.pot', 'will be overwritten');
		$this->assertTrue(file_exists($this->path . DS . 'default.pot'));
		$original = file_get_contents($this->path . DS . 'default.pot');

		$this->Task->execute();
		$result = file_get_contents($this->path . DS . 'default.pot');
		$this->assertNotEquals($original, $result);
	}

/**
 *  Test that the extract shell scans the core libs
 *
 * @return void
 */
	public function testExtractCore() {
		$this->Task->interactive = false;

		$this->Task->params['paths'] = CAKE . 'Test' . DS . 'test_app' . DS;
		$this->Task->params['output'] = $this->path . DS;
		$this->Task->params['extract-core'] = 'yes';

		$this->Task->execute();
		$this->assertTrue(file_exists($this->path . DS . 'cake.pot'));
		$result = file_get_contents($this->path . DS . 'cake.pot');

		$pattern = '/msgid "Yesterday, %s"\nmsgstr ""\n/';
		$this->assertRegExp($pattern, $result);

		$this->assertTrue(file_exists($this->path . DS . 'cake_dev.pot'));
		$result = file_get_contents($this->path . DS . 'cake_dev.pot');

		$pattern = '/#: Console\/Templates\//';
		$this->assertNotRegExp($pattern, $result);

		$pattern = '/#: Test\//';
		$this->assertNotRegExp($pattern, $result);
	}
}
