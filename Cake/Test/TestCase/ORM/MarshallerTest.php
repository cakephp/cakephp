<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Marshaller;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Marshaller test case
 */
class MarshallerTest extends TestCase {

	public $fixtures = ['core.article', 'core.user', 'core.comment'];

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$articles = TableRegistry::get('Articles');
		$articles->belongsTo('Users');
		$articles->hasMany('Comments');

		$comments = TableRegistry::get('Comments');
		$comments->belongsTo('Articles');
		$comments->belongsTo('Users');

		$this->articles = $articles;
		$this->comments = $comments;
	}

/**
 * Teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
		unset($this->articles, $this->comments);
	}

/**
 * Test one() in a simple use.
 *
 * @return void
 */
	public function testOneSimple() {
		$data = [
			'title' => 'My title',
			'body' => 'My content',
			'author_id' => 1,
			'not_in_schema' => true
		];
		$marshall = new Marshaller($this->articles);
		$result = $marshall->one($data, []);

		$this->assertInstanceOf('Cake\ORM\Entity', $result);
		$this->assertEquals($data, $result->toArray());
		$this->assertTrue($result->dirty(), 'Should be a dirty entity.');
		$this->assertNull($result->isNew(), 'Should be detached');
	}

/**
 * test one() with association data.
 *
 * @return void
 */
	public function testOneAssociationsSingle() {
		$data = [
			'title' => 'My title',
			'body' => 'My content',
			'author_id' => 1,
			'Comments' => [
				['comment' => 'First post', 'user_id' => 2],
				['comment' => 'Second post', 'user_id' => 2],
			],
			'Users' => [
				'username' => 'mark',
				'password' => 'secret'
			]
		];
		$marshall = new Marshaller($this->articles);
		$result = $marshall->one($data, ['Users']);

		$this->assertEquals($data['title'], $result->title);
		$this->assertEquals($data['body'], $result->body);
		$this->assertEquals($data['author_id'], $result->author_id);

		$this->assertInternalType('array', $result->Comments);
		$this->assertCount(2, $result->Comments);
		$this->assertInternalType('array', $result->Comments[0]);
		$this->assertInternalType('array', $result->Comments[1]);

		$this->assertInstanceOf('Cake\ORM\Entity', $result->user);
		$this->assertEquals($data['Users']['username'], $result->user->username);
		$this->assertEquals($data['Users']['password'], $result->user->password);
	}

/**
 * test one() with association data.
 *
 * @return void
 */
	public function testOneAssociationsMany() {
		$this->markTestIncomplete('not done');
	}

	public function testOneDeepAssociations() {
		$this->markTestIncomplete('not done');
	}

	public function testManySimple() {
		$this->markTestIncomplete('not done');
	}

	public function testManyAssociations() {
		$this->markTestIncomplete('not done');
	}

	public function testManyDeepAssociations() {
		$this->markTestIncomplete('not done');
	}

}
