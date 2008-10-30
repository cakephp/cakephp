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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'l10n');
/**
 * L10nTest class
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class L10nTest extends CakeTestCase {
/**
 * testGet method
 *
 * @access public
 * @return void
 */
	function testGet() {
		$l10n =& new L10n();

		// Catalog Entry
		$l10n->get('en');
		$result = $l10n->language;
		$expected = 'English';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('eng', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'eng';
		$this->assertEqual($result, $expected);

		// Map Entry
		$l10n->get('eng');
		$result = $l10n->language;
		$expected = 'English';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('eng', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'eng';
		$this->assertEqual($result, $expected);

		// Catalog Entry
		$l10n->get('en-ca');
		$result = $l10n->language;
		$expected = 'English (Canadian)';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('en_ca', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'en_ca';
		$this->assertEqual($result, $expected);

		// Default Entry
		define('DEFAULT_LANGUAGE', 'en-us');

		$l10n->get('use_default');
		$result = $l10n->language;
		$expected = 'English (United States)';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('en_us', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'en_us';
		$this->assertEqual($result, $expected);

		// Using $this->default
		$l10n = new L10n();
		$l10n->get('use_default');
		$result = $l10n->language;
		$expected = 'English (United States)';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('en_us', 'eng', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'en_us';
		$this->assertEqual($result, $expected);
	}
/**
 * testGetAutoLanguage method
 *
 * @access public
 * @return void
 */
	function testGetAutoLanguage() {
		$__SERVER = $_SERVER;
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'inexistent,en-ca';

		$l10n =& new L10n();
		$l10n->get();
		$result = $l10n->language;
		$expected = 'English (Canadian)';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('en_ca', 'eng', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'en_ca';
		$this->assertEqual($result, $expected);

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx';
		$l10n->get();
		$result = $l10n->language;
		$expected = 'Spanish (Mexican)';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('es_mx', 'spa', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'es_mx';
		$this->assertEqual($result, $expected);

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en_xy,en_ca';
		$l10n->get();
		$result = $l10n->language;
		$expected = 'English';
		$this->assertEqual($result, $expected);

		$result = $l10n->languagePath;
		$expected = array('eng', 'eng', 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->locale;
		$expected = 'eng';
		$this->assertEqual($result, $expected);

		$_SERVER = $__SERVER;
	}
/**
 * testMap method
 *
 * @access public
 * @return void
 */
	function testMap() {
		$l10n =& new L10n();

		$result = $l10n->map(array('eng', 'en', 'en-us'));
		$expected = array('eng' => 'en', 'en' => 'eng');
		$this->assertEqual($result, $expected);
	}
/**
 * testCatalog method
 *
 * @access public
 * @return void
 */
	function testCatalog() {
		$l10n =& new L10n();

		$result = $l10n->catalog(array('eng', 'en', 'en-us'));
		$expected = array(
			'en' => array('language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => 'utf-8'),
			'en-us' => array('language' => 'English (United States)', 'locale' => 'en_us', 'localeFallback' => 'eng', 'charset' => 'utf-8')
		);
		$this->assertEqual($result, $expected);
	}
}
?>