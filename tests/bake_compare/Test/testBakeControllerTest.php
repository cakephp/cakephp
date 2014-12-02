<?php
namespace TestApp\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;
use TestApp\Controller\PostsController;

/**
 * TestApp\Controller\PostsController Test Case
 */
class PostsControllerTest extends IntegrationTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = [
		'Posts' => 'app.posts'
	];

/**
 * Test index method
 *
 * @return void
 */
	public function testIndex() {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
