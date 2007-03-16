<?php
/* SVN FILE: $Id: $ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * Author(s): Larry E. Masters aka PhpNut <phpnut@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @author       Larry E. Masters aka PhpNut <phpnut@gmail.com>
 * @copyright    Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * @link         http://www.phpnut.com/projects/
 * @package      test_suite
 * @subpackage   test_suite.cases.app
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision: $
 * @modifiedby   $LastChangedBy: $
 * @lastmodified $Date: $
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	require_once LIBS.'session.php';
/**
 * Short description for class.
 *
 * @package    test_suite
 * @subpackage test_suite.cases.libs
 * @since      CakePHP Test Suite v 1.0.0.0
 */
class SessionTest extends UnitTestCase {

    function setUp() {
        $this->Session = new CakeSession();
    }

    function testCheck() {
    	$this->Session->write('SessionTestCase', 'value');
        $result = $this->Session->check('SessionTestCase');
        $this->assertEqual($result, true);
        
        $result = $this->Session->check('NotExistingSessionTestCase');
        $this->assertEqual($result, false);
    }
    
    function testCheckingSavedEmpty() {
        $this->Session->write('SessionTestCase', 0);
        $result = $this->Session->check('SessionTestCase');
        $this->assertEqual($result, true);

        $this->Session->write('SessionTestCase', '0');
        $result = $this->Session->check('SessionTestCase');
        $this->assertEqual($result, true);

        $this->Session->write('SessionTestCase', false);
        $result = $this->Session->check('SessionTestCase');
        $this->assertEqual($result, true);

        $this->Session->write('SessionTestCase', null);
        $result = $this->Session->check('SessionTestCase');
        $this->assertEqual($result, null);
    }

    function testReadingSavedEmpty() {
        $this->Session->write('SessionTestCase', 0);
        $result = $this->Session->read('SessionTestCase');
        $this->assertEqual($result, 0);

        $this->Session->write('SessionTestCase', '0');
        $result = $this->Session->read('SessionTestCase');
        $this->assertEqual($result, '0');

        $this->Session->write('SessionTestCase', false);
        $result = $this->Session->read('SessionTestCase');
        $this->assertEqual($result, false);

        $this->Session->write('SessionTestCase', null);
        $result = $this->Session->read('SessionTestCase');
        $this->assertEqual($result, null);
    }

    function tearDown() {
        $this->Session->del('SessionTestCase');
        unset($this->Session);
    }
}

?>