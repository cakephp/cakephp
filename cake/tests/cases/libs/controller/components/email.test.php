<?php
/* SVN FILE: $Id$ */
/**
 * Series of tests for email component.
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
 * @package			cake
 * @subpackage		cake.cake.tests.cases.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.5347
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'components' . DS .'email');

class EmailTestController extends Controller {
	var $name = 'EmailTest';
	var $uses = null;
	var $components = array('Email');
}

class EmailTest extends CakeTestCase {
	var $name = 'Email';

	function setUp() {
		$this->Controller =& new EmailTestController();

		restore_error_handler();
		@$this->Controller->_initComponents();
		set_error_handler('simpleTestErrorHandler');

		$this->Controller->Email->startup($this->Controller);
		ClassRegistry::addObject('view', new View($this->Controller));
	}

	function testBadSmtpSend() {
		if (@fsockopen('localhost', 25)) {
			$this->Controller->Email->smtpOptions['host'] = 'blah';
            $this->Controller->Email->delivery = 'smtp';
			$this->assertFalse($this->Controller->Email->send('Should not work'));
		} else {
			$this->skipUnless(@fsockopen('localhost', 25), 'Must be able to connect to localhost port 25');
		}
	}

	function testSmtpSend() {
		if (@fsockopen('localhost', 25)) {
			$this->assertTrue(@fsockopen('localhost', 25), "Local mail server is running");
			$this->Controller->Email->reset();
			$this->Controller->Email->to = 'chartjes@localhost';
			$this->Controller->Email->subject = 'Cake SMTP test';
			$this->Controller->Email->replyTo = 'noreply@example.com';
			$this->Controller->Email->from = 'noreply@example.com';
			$this->Controller->Email->delivery = 'smtp';
			$this->Controller->Email->template = null;
			$this->assertTrue($this->Controller->Email->send("This is the body of the message"));
		}
	}
}
?>