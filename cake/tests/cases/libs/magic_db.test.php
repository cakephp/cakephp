<?php
/* SVN FILE: $Id: magic_db.test.php 5444 2007-07-19 13:38:26Z the_undefined $ */
/**
 * MagicDb test
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link			http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0
 * @version			$Revision: 5444 $
 * @modifiedby		$LastChangedBy: the_undefined $
 * @lastmodified	$Date: 2007-07-19 15:38:26 +0200 (Do, 19 Jul 2007) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses('magic_db', 'object');
/**
 * The test class for the MagicDb classA
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class MagicDbTest extends UnitTestCase {
/**
 * The MagicDb instance to be tested
 *
 * @var object
 * @access public
 */
	var $Db = null;

/**
 * Sets up a MagicDb class instance for testing
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->Db =& new MagicDb();
	}

/**
 * MagicDb::read should properly read MagicDb databases from .php-/.db-files and plain data arguments passed in and return false if the file wasn't found or
 * if the readed data did not validate.
 *
 * @return void
 * @access public
 */
	function testRead() {
		$this->Db->db = array();

		$r = $this->Db->read(true);
		$this->assertTrue($r === false);
		$r = $this->Db->read(5);
		$this->assertTrue($r === false);

		$this->Db->db = array('a');
		$r = $this->Db->read(array('foo' => 'bar'));
		$this->assertTrue($r === false);
		$this->assertTrue($this->Db->db === array('a'));

		$magicDb = array('header' => array(), 'database' => array());
		$r = $this->Db->read($magicDb);
		$this->assertTrue($r === true);
		$this->assertTrue($this->Db->db === $magicDb);

		// @TODO: Test parsing an actual magic.db file

		$r = $this->Db->read('does-not-exist.db');
		$this->assertTrue($r === false);
		$this->assertTrue($this->Db->db === $magicDb);

		if (file_exists(VENDORS.'magic.php')) {
			$r = $this->Db->read(VENDORS.'magic.php');
			$this->assertTrue($r === true);
			$this->assertTrue($this->Db->db === array('header' => array(), 'database' => array()));
		}

		$r = $this->Db->read(MagicDbTestData::get('wordperfect'));
		// @TODO: Test $this->Db->data value
		// $this->assertTrue($r === true);
	}

/**
 * MagicDb::toArray should either return the MagicDb::db property, or the parsed array data if a magic.db dump is passed in as the first argument
 *
 * @return void
 * @access public
 */
	function testToArray() {
		$this->Db->db = array();

		$r = $this->Db->toArray();
		$this->assertTrue($r === array());
		$this->Db->db = array('foo' => 'bar');
		$r = $this->Db->toArray();
		$this->assertTrue($r === array('foo' => 'bar'));

		$r = $this->Db->toArray(array('yeah'));
		$this->assertTrue($r === array('yeah'));

		$r = $this->Db->toArray('foo');
		$this->assertTrue($r === array());
	}

/**
 * The MagicDb::validates function should return if the array passed to it or the local db property contains a valid MagicDb record set
 *
 * @return void
 * @access public
 */
	function testValidates() {
		$r = $this->Db->validates(array());
		$this->assertTrue($r === false);

		$r = $this->Db->validates(array('header' => true, 'database' => true));
		$this->assertTrue($r === false);
		$magicDb = array('header' => array(), 'database' => array());
		$r = $this->Db->validates($magicDb);
		$this->assertTrue($r === true);

		$this->Db->db = array();
		$r = $this->Db->validates();
		$this->assertTrue($r === false);

		$this->Db->db = $magicDb;
		$r = $this->Db->validates();
		$this->assertTrue($r === true);
	}
}

/**
 * Test data holding object for MagicDb tests
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */

class MagicDbTestData extends Object {
/**
 * Base64 encoded data
 *
 * @var array
 * @access public
 */
	var $data = array(
		'wordperfect' => 'IyBGSUxFX0lEIERCDQojIERhdGU6MjAwNS0wMy0yOQ0KIyBTb3VyY2U6aHR0cDovL3d3dy5tYWdpY2RiLm9yZw0KDQojIE1hZ2ljIElEIGZvciBXb3JkcGVyZmVjdCBmaWxlcy4NCiMgU3VibWl0dGVkIG9uIDIwMDQtMDItMTQgYnkgQ2FybA0KMAlzdHJpbmcJXFx4RkZXUEMJW2ZpZD0wMDAwMDEwMDgtMDAtMDAwMDAwMTtleHQ9O21pbWU9O11Xb3JkcGVyZmVjdCBoZWxwIGZpbGUNCiY5CWJ5dGUJMHgwMgkNCj4xMCBieXRlCXgJLCB2ZXJzaW9uICVkDQo+MTEJYnl0ZQl4CS4lZA0KDQojIE1hZ2ljIElEIGZvciBXb3JkcGVyZmVjdCBmaWxlcy4NCiMgU3VibWl0dGVkIG9uIDIwMDQtMDItMTQgYnkgQ2FybA0KMAlzdHJpbmcJXFx4RkZXUEMJW2ZpZD0wMDAwMDEwMDgtMDAtMDAwMDAwMTtleHQ9O21pbWU9O11Xb3JkcGVyZmVjdCBhcHBsaWNhdGlvbiByZXNvdXJjZSBsaWJyYXJ5DQomOQlieXRlCTUxCQ0KPjEwCWJ5dGUJeAksIHZlcnNpb24gJWQNCj4xMQlieXRlCXgJLiVkDQoNCiMgTWFnaWMgSUQgZm9yIFdvcmRwZXJmZWN0IGZpbGVzLg0KIyBTdWJtaXR0ZWQgb24gMjAwNC0wMi0xNCBieSBDYXJsDQowCXN0cmluZwlcXHhGRldQQwlbZmlkPTAwMDAwMTAwOC0wMC0wMDAwMDAxO2V4dD07bWltZT07XVdvcmRwZXJmZWN0IGJsb2NrIGZpbGUNCiY5CWJ5dGUJMTMJDQo+MTAJYnl0ZQl4CSwgdmVyc2lvbiAlZA0KPjExCWJ5dGUJeAkuJWQ='
	);

/**
 * Returns the test data for a given key
 *
 * @param string $key
 * @return void
 * @access public
 **/
	function get($key) {
		static $data = array();

		if (empty($data)) {
			$vars = get_class_vars(__CLASS__);
			foreach ($vars['data'] as $key => $val) {
				$data[$key] = base64_decode($val);
			}
		}

		if (!isset($data[$key])) {
			return false;
		}
		return $data[$key];
	}
}


?>