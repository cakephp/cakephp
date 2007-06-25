<?php
/* SVN FILE: $Id$ */

/**
 * Series of tests for email component
 */

require_once LIBS . '/controller/components/email.php';

class EmailTestController extends Controller {
	var $name = 'EmailTest';
	var $uses = null;
	var $components = array('Email');
}

class EmailTest extends CakeTestCase {
	var $name = 'Email';

	function setUp() {
		$this->Controller =& new Controller();
		$this->Controller->_initComponents();
		$this->View =& new View($this->Controller);
		ClassRegistry::addObject('view', $this->View);
	}

	function testConstruction() {
		$this->assertTrue(is_object($this->Email));
	}

	function testBadSmtpSent() {
		$this->Controller->Email->smtpOptions['host'] = 'caketest.com';
		$this->Controller->Email->delivery = 'smtp';
		$this->assertFalse($this->Email->send('This should not work'));
	}
}
?>
