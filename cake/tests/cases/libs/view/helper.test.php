<?php
/* SVN FILE: $Id: helper.test.php 5497 2007-08-07 07:44:12Z gwoo $ */
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
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision: 5497 $
 * @modifiedby		$LastChangedBy: gwoo $
 * @lastmodified	$Date: 2007-08-07 03:44:12 -0400 (Tue, 07 Aug 2007) $
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('view' . DS . 'view', 'view' . DS . 'helper');

class HelperTestPost extends Model {

	var $useTable = false;

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'title' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
			'body' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => ''),
			'number' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}
}


class HelperTestComment extends Model {

	var $useTable = false;

	function schema() {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'author_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'title' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
			'body' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => ''),
			'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}
}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class HelperTest extends UnitTestCase {

	function setUp() {
		Router::reload();
		$null = null;
		$this->View = new View($null);
		$this->Helper = new Helper();
		ClassRegistry::addObject('HelperTestPost', new HelperTestPost());
		ClassRegistry::addObject('HelperTestComment', new HelperTestComment());
	}

	function testFormFieldNameParsing() {
		$this->Helper->setEntity('HelperTestPost.id');
		$this->assertFalse($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);

		$this->Helper->setEntity('HelperTestComment.body');
		$this->assertFalse($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestComment');
		$this->assertEqual($this->View->field, 'body');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('HelperTestPost', true);
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, null);
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('id');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('HelperTestComment.body');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'body');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, 'HelperTestComment');
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('body');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'body');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('5.id');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, '5');
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('5.id.time');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, '5');
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, 'time');

		$this->Helper->setEntity('HelperTestComment.id.time');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, '5');
		$this->assertEqual($this->View->association, 'HelperTestComment');
		$this->assertEqual($this->View->fieldSuffix, 'time');

		$this->Helper->setEntity(null);
		$this->Helper->setEntity('ModelThatDoesntExist.field_that_doesnt_exist');
		$this->assertFalse($this->View->modelScope);
		$this->assertEqual($this->View->model, 'ModelThatDoesntExist');
		$this->assertEqual($this->View->field, 'field_that_doesnt_exist');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);
	}

	function tearDown() {
		unset($this->Helper, $this->View);
		ClassRegistry::flush();
	}
}

?>