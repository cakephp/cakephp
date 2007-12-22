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
uses('xml');

/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class XMLNodeTest extends UnitTestCase {

	function skip() {
		$this->skipif (true, 'XMLNodeTest not implemented');
	}
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class XMLTest extends UnitTestCase {

	function testSerialization() {
		$input = array(
			array(
				'Project' => array(
					'id' => 1,
					'title' => null,
					'client_id' => 1,
					'show' => 1,
					'is_spotlight' => null,
					'style_id' => 0,
					'job_type_id' => 1,
					'industry_id' => 1,
					'modified' => null,
					'created' => null
				),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array(
					'id' => 2,
					'title' => null, 
					'client_id' => 2,
					'show' => 1,
					'is_spotlight' => null,
					'style_id' => 0,
					'job_type_id' => 2,
					'industry_id' => 2,
					'modified' => '2007-11-26 14:48:36',
					'created' => null
				),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education')
			)
		);
		$expected = '<project id="1" title="" client_id="1" show="1" is_spotlight="" style_id="0" job_type_id="1" industry_id="1" modified="" created=""><style id="" name="" /><job_type id="1" name="Touch Screen Kiosk" /><industry id="1" name="Financial" /></project><project id="2" title="" client_id="2" show="1" is_spotlight="" style_id="0" job_type_id="2" industry_id="2" modified="2007-11-26 14:48:36" created=""><style id="" name="" /><job_type id="2" name="Awareness Campaign" /><industry id="2" name="Education" /></project>';

		$xml = new XML($input);
		$result = $xml->compose(false);
		$result = preg_replace("/\n/",'', $result);
		$this->assertEqual($expected, $result);
	}
}

?>