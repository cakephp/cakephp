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

	public function testRenderString() {
		$template = 'The value of aVariable is: <%= $aVariable %>.';

		$this->View->set(['aVariable' => 123]);
		$result = $this->View->render($template);
		$expected = 'The value of aVariable is: 123.';

		$this->assertSame($expected, $result, 'variables in erb-style tags should be evaluated');
	}

	public function testRenderTemplate() {
		$this->View->set(['aVariable' => 123]);
		$result = $this->View->render('view_tests/simple');
		$expected = "The value of aVariable is: 123.\n";

		$this->assertSame($expected, $result, 'variables in erb-style tags should be evaluated');
	}

	public function testRenderIgnorePhpTags() {
		$template = 'The value of aVariable is: <%= $aVariable %>. Not <?php echo $aVariable ?>.';

		$this->View->set(['aVariable' => 123]);
		$result = $this->View->render($template);
		$expected = 'The value of aVariable is: 123. Not <?php echo $aVariable ?>.';

		$this->assertSame($expected, $result, 'variables in php tags should be treated as strings');
	}

	public function testRenderIgnorePhpShortTags() {
		$template = 'The value of aVariable is: <%= $aVariable %>. Not <?= $aVariable ?>.';

		$this->View->set(['aVariable' => 123]);
		$result = $this->View->render($template);
		$expected = 'The value of aVariable is: 123. Not <?= $aVariable ?>.';

		$this->assertSame($expected, $result, 'variables in php tags should be treated as strings');
	}

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
}
