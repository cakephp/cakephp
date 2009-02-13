<?php
/* SVN FILE: $Id: extract.test.php 7838 2008-11-07 10:41:52Z nate $ */
/**
 * Test Case for i18n extraction shell task
 *
 * 
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.cake.libs.
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision: 7838 $
 * @modifiedby    $LastChangedBy: DarkAngelBGE $
 * @lastmodified  $Date: 2008-11-07 05:41:52 -0500 (Fri, 07 Nov 2008) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');

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

class TestExtractShellDispatcher extends ShellDispatcher {

	function _initEnvironment() {
	}

	function stdout($string, $newline = true) {
	}

	function stderr($string) {
	}

	function _stop($status = 0) {
		$this->stopped = 'Stopped with status: ' . $status;
	}
}

class MockExtractTast extends ExtractTask {

	function searchDirectory($path = null) {
		return parent::__searchDirectory($path);
	}
}

class ExtractTaskTest extends CakeTestCase {

	function setUp() {
		$this->dispatcher = new TestExtractShellDispatcher();
		$this->task = new MockExtractTast($this->dispatcher);
		mkdir(TMP . '/extract_test');
	}

	function testDirectorySearching() {
		$this->assertIdentical($this->task->searchDirectory(TMP . '/extract_test'), array());
	}

	function tearDown() {
		unset($this->task, $this->dispatcher);
		rmdir(TMP . '/extract_test');
	}
}

?>