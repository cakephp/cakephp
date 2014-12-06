<?php
/**
 * PluralFormsTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.I18n
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('PluralForms', 'I18n');

/**
 * PluralFormsTest class
 *
 * @package       Cake.Test.Case.I18n
 */
class PluralFormsTest extends CakeTestCase {

/**
 * This function sets up a PluralForms
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->PluralForms = new PluralForms();
	}

/**
 * testAllOperators
 *
 * @return void
 */
	public function testAllOperators() {
		$formula = $this->PluralForms->parsePluralForms('nplurals=2; plural=('.
			'(n == 1 || n != 0) && (n > 0 || n < 2) && (n >= 1 || n <= 1) && (n % 2) ? 0 : 1'.
		')');
		$this->assertEquals(1, $this->PluralForms->getPlural($formula, 0));
		$this->assertEquals(0, $this->PluralForms->getPlural($formula, 1));
	}

/**
 * testOperatorsPrecedence
 *
 * @return void
 */
	public function testOperatorsPrecedence() {
		$formula = $this->PluralForms->parsePluralForms('nplurals=2; plural='.
			'0 ? 0 : 0 || 1 && 0 != 1 == 1 >= 1 <= 1 < 2 > 0 % 2'
		);
		$this->assertEquals(1, $this->PluralForms->getPlural($formula, 0));
	}

/**
 * testTwoForms
 *
 * @return void
 */
	public function testTwoForms() {
		$formula = $this->PluralForms->parsePluralForms('nplurals=2; plural=(n != 1)');
		$this->assertEquals(1, $this->PluralForms->getPlural($formula, 0));
		$this->assertEquals(0, $this->PluralForms->getPlural($formula, 1));
		$this->assertEquals(1, $this->PluralForms->getPlural($formula, 2));
		$this->assertEquals(1, $this->PluralForms->getPlural($formula, 3));
	}

/**
 * testThreeForms
 *
 * @return void
 */
	public function testThreeForms() {
		$formula = $this->PluralForms->parsePluralForms('nplurals=3; plural=(n == 0 ? 0 : n > 1 ? 2 : 1');
		$this->assertEquals(0, $this->PluralForms->getPlural($formula, 0));
		$this->assertEquals(1, $this->PluralForms->getPlural($formula, 1));
		$this->assertEquals(2, $this->PluralForms->getPlural($formula, 2));
		$this->assertEquals(2, $this->PluralForms->getPlural($formula, 3));
	}

/**
 * testNoSemicolonInHeader
 *
 * @return void
 */
	public function testNoSemicolonInHeader() {
		$this->setExpectedException('CakeException');
		$formula = $this->PluralForms->parsePluralForms('nplurals=2 plural=(n != 1)');
	}

/**
 * testNoAssignmentInHeader
 *
 * @return void
 */
	public function testNoAssignmentInHeader() {
		$this->setExpectedException('CakeException');
		$formula = $this->PluralForms->parsePluralForms('nplurals=2; (n > 1)');
	}

/**
 * testSyntaxErrorInFormula
 *
 * @return void
 */
	public function testSyntaxErrorInFormula() {
		$formula = $this->PluralForms->parsePluralForms('nplurals=2; plural=(n => 1)');
		$this->setExpectedException('CakeException');
		$this->PluralForms->getPlural($formula, 0);
	}

/**
 * testDivisionByZeroInFormula
 *
 * @return void
 */
	public function testDivisionByZeroInFormula() {
		$formula = $this->PluralForms->parsePluralForms('nplurals=2; plural=(10 % n)');
		$this->setExpectedException('CakeException');
		$this->PluralForms->getPlural($formula, 0);
	}
}
