<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Network\Http;

use Cake\Network\Http\FormData;
use Cake\Network\Http\FormData\Part;
use Cake\TestSuite\TestCase;

/**
 * Test case for FormData.
 */
class FormDataTest extends TestCase {

/**
 * Test getting the boundary.
 *
 * @return void
 */
	public function testBoundary() {
		$data = new FormData();
		$result = $data->boundary();
		$this->assertRegExp('/^[a-f0-9]{32}$/', $result);

		$result2 = $data->boundary();
		$this->assertEquals($result, $result2);
	}

/**
 * test adding parts returns this.
 *
 * @return void
 */
	public function testAddReturnThis() {
		$data = new FormData();
		$return = $data->add('test', 'value');
		$this->assertSame($data, $return);
	}

/**
 * Test adding parts that are simple.
 *
 * @return void
 */
	public function testAddSimple() {
		$data = new FormData();
		$data->add('test', 'value')
			->add('int', 1)
			->add('float', 2.3);

		$this->assertCount(3, $data);
		$boundary = $data->boundary();
		$result = (string)$data;
		$expected = array(
			'--' . $boundary,
			'Content-Disposition: form-data; name="test"',
			'',
			'value',
			'--' . $boundary,
			'Content-Disposition: form-data; name="int"',
			'',
			'1',
			'--' . $boundary,
			'Content-Disposition: form-data; name="float"',
			'',
			'2.3',
			'--' . $boundary . '--',
			'',
			'',
		);
		$this->assertEquals(implode("\r\n", $expected), $result);
	}

/**
 * Test adding parts that are arrays.
 *
 * @return void
 */
	public function testAddArray() {
		$data = new FormData();
		$data->add('Article', [
			'title' => 'first post',
			'published' => 'Y',
			'tags' => ['blog', 'cakephp']
		]);
		$boundary = $data->boundary();
		$result = (string)$data;

		$expected = array(
			'--' . $boundary,
			'Content-Disposition: form-data; name="Article[title]"',
			'',
			'first post',
			'--' . $boundary,
			'Content-Disposition: form-data; name="Article[published]"',
			'',
			'Y',
			'--' . $boundary,
			'Content-Disposition: form-data; name="Article[tags][0]"',
			'',
			'blog',
			'--' . $boundary,
			'Content-Disposition: form-data; name="Article[tags][1]"',
			'',
			'cakephp',
			'--' . $boundary . '--',
			'',
			'',
		);
		$this->assertEquals(implode("\r\n", $expected), $result);
	}

/**
 * Test adding a part with a file in it.
 */
	public function testAddFile() {

	}
}
