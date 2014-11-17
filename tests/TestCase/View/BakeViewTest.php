<?php
/**
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
namespace Cake\Test\TestCase\View;

use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Cake\View\BakeView;

/**
 * BakeViewTest class
 *
 */
class BakeViewTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$request = new Request();
		$response = new Response();
		$this->View = new BakeView($request, $response);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->View);
	}

/**
 * test rendering a template file
 *
 * @return void
 */
	public function testRenderTemplate() {
		$this->View->set(['aVariable' => 123]);
		$result = $this->View->render('view_tests/simple');
		$expected = "The value of aVariable is: 123.\n";

		$this->assertSame($expected, $result, 'variables in erb-style tags should be evaluated');
	}

/**
 * verify that php tags are ignored
 *
 * @return void
 */
	public function testRenderIgnorePhpTags() {
		$this->View->set(['aVariable' => 123]);
		$result = $this->View->render('view_tests/simple_php');
		$expected = "The value of aVariable is: 123. Not <?php echo \$aVariable ?>.\n";

		$this->assertSame($expected, $result, 'variables in php tags should be treated as strings');
	}

/**
 * verify that short php tags are ignored
 *
 * @return void
 */
	public function testRenderIgnorePhpShortTags() {
		$this->View->set(['aVariable' => 123]);
		$result = $this->View->render('view_tests/simple_php_short_tags');
		$expected = "The value of aVariable is: 123. Not <?= \$aVariable ?>.\n";

		$this->assertSame($expected, $result, 'variables in php tags should be treated as strings');
	}

/**
 * Newlines after template tags should act predictably
 *
 * @return void
 */
	public function testRenderNewlines() {
		$result = $this->View->render('view_tests/newlines');
		$expected = "There should be a newline about here: \n";
		$expected .= "And this should be on the next line.\n";
		$expected .= "\n";
		$expected .= "There should be no new line after this";

		$this->assertSame(
			$expected,
			$result,
			'Tags at the end of a line should not swallow new lines when rendered'
		);
	}

/**
 * Verify that template tags with leading whitespace don't leave a mess
 *
 * @return void
 */
	public function testSwallowLeadingWhitespace() {
		$result = $this->View->render('view_tests/leading_whitespace');
		$expected = $this->_getCompareTemplate('leading_whitespace');

		$this->assertSame(
			$expected,
			$result,
			'Leading whitespace in bake templates should not result in leading/loose whitespace in rendered results'
		);
	}

/**
 * _getCompareTemplate
 *
 * @param string $template
 * @return string
 */
	protected function _getCompareTemplate($template) {
		return file_get_contents(dirname(dirname(__DIR__)) . "/test_app/TestApp/Template/Bake/view_tests_compare/$template.ctp");
	}
}
