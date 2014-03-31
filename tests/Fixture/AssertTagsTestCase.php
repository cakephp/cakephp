<?php
namespace Cake\Test\Fixture;

use Cake\TestSuite\TestCase;

/**
 * This class helps in indirectly testing the functionalities of CakeTestCase::assertTags
 *
 */
class AssertTagsTestCase extends TestCase {

/**
 * test that assertTags knows how to handle correct quoting.
 *
 * @return void
 */
	public function testAssertTagsQuotes() {
		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTags($input, $pattern);

		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTags($input, $pattern);

		$input = "<a href='/test.html' class='active'>My link</a>";
		$pattern = array(
			'a' => array('href' => 'preg:/.*\.html/', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTags($input, $pattern);
	}

/**
 * testNumericValuesInExpectationForAssertTags
 *
 * @return void
 */
	public function testNumericValuesInExpectationForAssertTags() {
		$value = 220985;

		$input = '<p><strong>' . $value . '</strong></p>';
		$pattern = array(
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p'
		);
		$this->assertTags($input, $pattern);

		$input = '<p><strong>' . $value . '</strong></p><p><strong>' . $value . '</strong></p>';
		$pattern = array(
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p',
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p',
		);
		$this->assertTags($input, $pattern);

		$input = '<p><strong>' . $value . '</strong></p><p id="' . $value . '"><strong>' . $value . '</strong></p>';
		$pattern = array(
			'<p',
				'<strong',
					$value,
				'/strong',
			'/p',
			'p' => array('id' => $value),
				'<strong',
					$value,
				'/strong',
			'/p',
		);
		$this->assertTags($input, $pattern);
	}

/**
 * testBadAssertTags
 *
 * @return void
 */
	public function testBadAssertTags() {
		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('hRef' => '/test.html', 'clAss' => 'active'),
			'My link2',
			'/a'
		);
		$this->assertTags($input, $pattern);
	}

/**
 * testBadAssertTags
 *
 * @return void
 */
	public function testBadAssertTags2() {
		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'<a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTags($input, $pattern);
	}

}
