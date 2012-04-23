<?php
/**
 * CakeRuleTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Validator
 * @since         CakePHP(tm) v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once dirname(dirname(__FILE__)) . DS . 'ModelTestBase.php';

/**
 * CakeRuleTest
 *
 * @package       Cake.Test.Case.Model.Validator
 */
class CakeRuleTest extends BaseModelTest {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$Article = new Article();
		$Article->set(array('title' => '', 'body' => 'no title'));
		$this->Validator = new ModelValidator($Article);
		$this->Validator->getData();
		$rule = array('notEmpty' => array('rule' => 'notEmpty', 'required' => true, 'last' => false));
		$this->Field = new CakeField($this->Validator, 'body', $rule);
	}

/**
 * testIsValid method
 *
 * @return void
 */
	public function testIsValid() {

	}
}
