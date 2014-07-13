<?php
/**
 * I18nTest file
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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * I18nTest class
 *
 */
class I18nTest extends TestCase {

	public function testDefaultTranslator() {
		$translator = I18n::translator();
		$this->assertInstanceOf('Aura\Intl\Translator', $translator);
	}

}
