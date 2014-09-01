<?php
/**
 * NumberTest file
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
use Cake\I18n\LocalizedNumber;
use Cake\TestSuite\TestCase;

/**
 * NumberTest class
 *
 */
class LocalizedNumberTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Number = new LocalizedNumber();
		$this->locale = I18n::defaultLocale();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Number);
		I18n::defaultLocale($this->locale);
	}
/**
 * testToReadableSize method
 *
 * @return void
 */
	public function testToReadableSize() {
		$result = $this->Number->toReadableSize(0);
		$expected = '0 Bytes';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1);
		$expected = '1 Byte';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(45);
		$expected = '45 Bytes';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1023);
		$expected = '1,023 Bytes';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024);
		$expected = '1 KB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 + 123);
		$expected = '1.12 KB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 512);
		$expected = '512 KB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 - 1);
		$expected = '1 MB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(512.05 * 1024 * 1024);
		$expected = '512.05 MB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 - 1);
		$expected = '1 GB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 512);
		$expected = '512 GB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 - 1);
		$expected = '1 TB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 512);
		$expected = '512 TB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 1024 - 1);
		$expected = '1,024 TB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 1024 * 1024);
		$expected = '1,048,576 TB';
		$this->assertEquals($expected, $result);
	}

/**
 * test toReadableSize() with locales
 *
 * @return void
 */
	public function testReadableSizeLocalized() {
		I18n::defaultLocale('fr_FR');
		$result = $this->Number->toReadableSize(1321205);
		$this->assertEquals('1,26 MB', $result);

		$result = $this->Number->toReadableSize(512.05 * 1024 * 1024 * 1024);
		$this->assertEquals('512,05 GB', $result);
	}

}
