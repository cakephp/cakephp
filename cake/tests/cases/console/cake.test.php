<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
class TestShellDispatcher {

	var $params = array();


	function __parseParams($params) {
		$count = count($params);
		for ($i = 0; $i < $count; $i++) {
			if(isset($params[$i])) {
				if ($params[$i]{0} === '-') {
					$key = substr($params[$i], 1);
					$this->params[$key] = true;
					unset($params[$i]);
					if(isset($params[++$i])) {
						if ($params[$i]{0} !== '-') {
							$this->params[$key] = str_replace('"', '', $params[$i]);
							unset($params[$i]);
						} else {
							$i--;
							$this->__parseParams($params);
						}
					}
				} else {
					$this->args[] = $params[$i];
					unset($params[$i]);
				}

			}
		}
	}

	function parseParams($params) {
		$this->params = $this->args = array();
		$this->__parseParams($params);
		$app = 'app';
		$root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
		if (!empty($this->params['working']) && (isset($this->args[0]) && $this->args[0]{0} !== '.')) {
			if (empty($this->params['app'])) {
				$root = dirname($this->params['working']);
				$app = basename($this->params['working']);
			} else {
				$root = $this->params['working'];
			}
			unset($this->params['working']);
 		}

		if (!empty($this->params['app'])) {
			if ($this->params['app']{0} == '/') {
				$root = dirname($this->params['app']);
			}
			$app = basename($this->params['app']);
			unset($this->params['app']);
		}

		$working = str_replace(DS . DS, DS, $root . DS . $app);

		$this->params = array_merge($this->params, array('app'=> $app, 'root'=> $root, 'working'=> $working));
	}

}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class ShellDispatcherTest extends UnitTestCase {

	function skip() {
		$this->skipif (false, 'ShellDispatcherTest not implemented');
	}

	function setUp() {
		$this->Dispatcher =& new TestShellDispatcher();
	}

	function testParseParmas() {
		$params = array('/cake/1.2.x.x/cake/console/cake.php',
						'bake',
						'-app',
						'new',
						'-working',
						'/var/www/htdocs'
					);

		$expected = array('app' => 'new',
						'working' => '/var/www/htdocs/new',
						'root' => '/var/www/htdocs'
						);
		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);
		unset($this->Dispatcher);


		$this->Dispatcher =& new TestShellDispatcher();
		$params = array('cake.php');

		$expected = array('app' => 'app',
						'working' => ROOT . DS . 'app',
						'root' => ROOT
						);
		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);
		unset($this->Dispatcher);


		$this->Dispatcher =& new TestShellDispatcher();
		$params = array('cake.php',
						'-app',
						'new',
					);

		$expected = array('app' => 'new',
						'working' => ROOT . DS . 'new',
						'root' => ROOT
						);
		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);
		unset($this->Dispatcher);


		$this->Dispatcher =& new TestShellDispatcher();
		$params = array('./cake.php',
						'bake',
						'-app',
						'new',
						'-working',
						' /cake/1.2.x.x/cake/console'
					);

		$expected = array('app' => 'new',
						'working' => ROOT . DS . 'new',
						'root' => ROOT
						);
		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);
		unset($this->Dispatcher);


		$this->Dispatcher =& new TestShellDispatcher();

		$params = array('./console/cake.php',
						'bake',
						'-app',
						'new',
						'-working',
						' /cake/1.2.x.x/cake'
					);

		$expected = array('app' => 'new',
						'working' => ROOT . DS . 'new',
						'root' => ROOT
						);
		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);

		$params = array('./console/cake.php',
						'bake',
						'-app',
						'new',
						'-dry',
						'-working',
						' /cake/1.2.x.x/cake'
					);

		$expected = array('app' => 'new',
						'working' => ROOT . DS . 'new',
						'root' => ROOT,
						'dry' => 1
						);
		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);

		$params = array('./console/cake.php',
						'-working',
						'/cake/1.2.x.x/cake',
						'schema',
						'run',
						'create',
						'-dry',
						'-f',
						'-name',
						'DbAcl'
					);
		$expected = array('app' => 'app',
						'working' => ROOT . DS . 'app',
						'root' => ROOT,
						'dry' => 1,
						'f' => 1,
						'name' => 'DbAcl'
						);

		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);

		$expected = array('./console/cake.php', 'schema', 'run', 'create');
		$this->assertEqual($expected, $this->Dispatcher->args);

		$params = array('/cake/1.2.x.x/cake/console/cake.php',
						'-working',
						'/cake/1.2.x.x/app',
						'schema',
						'run',
						'create',
						'-dry',
						'-name',
						'DbAcl'
					);
		$expected = array('app' => 'app',
						'working' => '/cake/1.2.x.x/app',
						'root' => '/cake/1.2.x.x',
						'dry' => 1,
						'name' => 'DbAcl'
						);
		$this->Dispatcher->parseParams($params);
		$this->assertEqual($expected, $this->Dispatcher->params);

		$expected = array('/cake/1.2.x.x/cake/console/cake.php', 'schema', 'run', 'create');
		$this->assertEqual($expected, $this->Dispatcher->args);


		unset($this->Dispatcher);
	}

	function tearDown() {
		unset($this->Dispatcher);
	}
}
?>