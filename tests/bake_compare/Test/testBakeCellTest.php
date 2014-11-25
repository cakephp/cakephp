<?php
namespace App\Test\TestCase\View\Cell;

use App\View\Cell\ArticlesCell;
use Cake\TestSuite\TestCase;

/**
 * App\View\Cell\ArticlesCell Test Case
 */
class ArticlesCellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->request = $this->getMock('Cake\Network\Request');
		$this->response = $this->getMock('Cake\Network\Response');
		$this->Articles = new ArticlesCell($this->request, $this->response);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Articles);

		parent::tearDown();
	}

/**
 * Test initial setup
 *
 * @return void
 */
	public function testInitialization() {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
