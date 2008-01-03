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
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}

uses('view'.DS.'helpers'.DS.'app_helper', 'controller'.DS.'controller', 'model'.DS.'model', 'view'.DS.'helper', 'view'.DS.'helpers'.DS.'time');

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class TimeTest extends UnitTestCase {

	function skip() {
		$this->skipif (false, 'TimeHelper test not implemented');
	}

	function setUp() {
		$this->Time = new TimeHelper();
	}

	function testToQuarter() {
		$result = $this->Time->toQuarter('2007-12-25');
		$this->assertEqual($result, 4);

		$result = $this->Time->toQuarter('2007-9-25');
		$this->assertEqual($result, 3);

		$result = $this->Time->toQuarter('2007-3-25');
		$this->assertEqual($result, 1);

		$result = $this->Time->toQuarter('2007-3-25', true);
		$this->assertEqual($result, array('2007-01-01', '2007-03-31'));
	}

	function testTimeAgoInWords() {
		$result = $this->Time->timeAgoInWords('2007-9-25');
		$this->assertEqual($result, 'on 25/9/07');

		$result = $this->Time->timeAgoInWords('2007-9-25', 'Y-m-d');
		$this->assertEqual($result, 'on 2007-09-25');

		$result = $this->Time->timeAgoInWords('2007-9-25', 'Y-m-d', true);
		$this->assertEqual($result, 'on 2007-09-25');

		$result = $this->Time->timeAgoInWords(strtotime('-2 weeks, -2 days'), 'Y-m-d', false);
		$this->assertEqual($result, '2 weeks, 2 days ago');

		$result = $this->Time->timeAgoInWords(strtotime('2 weeks, 2 days'), 'Y-m-d', true);
		$this->assertEqual($result, '2 weeks, 2 days');

		$result = $this->Time->timeAgoInWords(strtotime('2 weeks, 2 days'), 'Y-m-d', true);
		$this->assertEqual($result, '2 weeks, 2 days');

		$result = $this->Time->timeAgoInWords(strtotime('2 months, 2 days'), array('end' => '1 month'));
		$this->assertEqual($result, 'on ' . date('j/n/y', strtotime('2 months, 2 days')));

		$result = $this->Time->timeAgoInWords(strtotime('2 months, 2 days'), array('end' => '3 month'));
		$this->assertPattern('/2 months/', $result);

		$result = $this->Time->timeAgoInWords(strtotime('2 months, 12 days'), array('end' => '3 month'));
		$this->assertPattern('/2 months, 1 week/', $result);

		$result = $this->Time->timeAgoInWords(strtotime('3 months, 5 days'), array('end' => '4 month'));
		$this->assertEqual($result, '3 months, 4 days');

		$result = $this->Time->timeAgoInWords(strtotime('2 months, 2 days'), array('end' => '3 month'));
		$this->assertPattern('/2 months/', $result);

		$result = $this->Time->timeAgoInWords(strtotime('2 months, 2 days'), array('end' => '1 month', 'format' => 'Y-m-d'));
		$this->assertEqual($result, 'on ' . date('Y-m-d', strtotime('2 months, 2 days')));

		$result = $this->Time->timeAgoInWords(strtotime('-2 months, -2 days'), array('end' => '3 month'));
		$this->assertEqual($result, '2 months, 1 day ago');

		$result = $this->Time->timeAgoInWords(strtotime('-2 months, -2 days'), array('end' => '1 month', 'format' => 'Y-m-d'));
		$this->assertEqual($result, 'on ' . date('Y-m-d', strtotime('-2 months, -2 days')));
	}

	function tearDown() {
		unset($this->Time);
	}
}
?>