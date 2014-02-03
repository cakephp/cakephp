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
 * @since         CakePHP(tm) v 3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Form;

use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Cake\View\Form\EntityContext;

/**
 * Entity context test case.
 */
class EntityContextTest extends TestCase {

/**
 * Fixtures to use.
 *
 * @var array
 */
	public $fixtures = ['core.article', 'core.comment'];

/**
 * setup method.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->request = new Request();
	}

/**
 * Test reading data.
 *
 * @return void
 */
	public function testValBasic() {
		$row = new Entity([
			'title' => 'Test entity',
			'body' => 'Something new'
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);
		$result = $context->val('Articles.title');
		$this->assertEquals($row->title, $result);

		$result = $context->val('title');
		$this->assertEquals($row->title, $result);

		$result = $context->val('Articles.body');
		$this->assertEquals($row->body, $result);

		$result = $context->val('body');
		$this->assertEquals($row->body, $result);

		$result = $context->val('Articles.nope');
		$this->assertNull($result);

		$result = $context->val('nope');
		$this->assertNull($result);
	}

/**
 * Test reading values from associated entities.
 *
 * @return void
 */
	public function testValAssociated() {
		$row = new Entity([
			'title' => 'Test entity',
			'user' => new Entity([
				'username' => 'mark',
				'fname' => 'Mark'
			]),
			'comments' => [
				new Entity(['comment' => 'Test comment']),
				new Entity(['comment' => 'Second comment']),
			]
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);

		$result = $context->val('Articles.user.fname');
		$this->assertEquals($row->user->fname, $result);

		$result = $context->val('user.fname');
		$this->assertEquals($row->user->fname, $result);

		$result = $context->val('Articles.comments.0.comment');
		$this->assertEquals($row->comments[0]->comment, $result);

		$result = $context->val('comments.0.comment');
		$this->assertEquals($row->comments[0]->comment, $result);

		$result = $context->val('Articles.comments.1.comment');
		$this->assertEquals($row->comments[1]->comment, $result);

		$result = $context->val('comments.1.comment');
		$this->assertEquals($row->comments[1]->comment, $result);

		$result = $context->val('Articles.comments.0.nope');
		$this->assertNull($result);

		$result = $context->val('Articles.comments.0.nope.no_way');
		$this->assertNull($result);
	}

/**
 * Test validator as a string.
 *
 * @return void
 */
	public function testIsRequiredStringValidator() {
		$this->_setupTables();

		$context = new EntityContext($this->request, [
			'entity' => new Entity(),
			'table' => 'Articles',
			'validator' => 'create',
		]);

		$this->assertTrue($context->isRequired('Articles.title'));
		$this->assertTrue($context->isRequired('title'));
		$this->assertFalse($context->isRequired('Articles.body'));
		$this->assertFalse($context->isRequired('body'));

		$this->assertFalse($context->isRequired('Herp.derp.derp'));
		$this->assertFalse($context->isRequired('nope'));
	}

/**
 * Test isRequired on associated entities.
 *
 * @return void
 */
	public function testIsRequiredAssociatedHasMany() {
		$this->_setupTables();

		$comments = TableRegistry::get('Comments');
		$validator = $comments->validator();
		$validator->add('user_id', 'number', [
			'rule' => 'numeric',
		]);

		$row = new Entity([
			'title' => 'My title',
			'comments' => [
				new Entity(['comment' => 'First comment']),
				new Entity(['comment' => 'Second comment']),
			]
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
			'validator' => 'default',
		]);

		$this->assertTrue($context->isRequired('comments.0.user_id'));
		$this->assertTrue($context->isRequired('Articles.comments.0.user_id'));

		$this->assertFalse($context->isRequired('comments.0.other'));
		$this->assertFalse($context->isRequired('Articles.comments.0.other'));
	}

/**
 * Test isRequired on associated entities with custom validators.
 *
 * @return void
 */
	public function testIsRequiredAssociatedValidator() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'comments' => [
				new Entity(['comment' => 'First comment']),
				new Entity(['comment' => 'Second comment']),
			]
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
			'validator' => [
				'Articles' => 'create',
				'Comments' => 'custom'
			]
		]);

		$this->assertTrue($context->isRequired('title'));
		$this->assertFalse($context->isRequired('body'));
		$this->assertTrue($context->isRequired('comments.0.comment'));
		$this->assertTrue($context->isRequired('comments.1.comment'));
	}

/**
 * Test isRequired on associated entities.
 *
 * @return void
 */
	public function testIsRequiredAssociatedBelongsTo() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'user' => new Entity(['username' => 'Mark']),
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
			'validator' => [
				'Articles' => 'create',
				'Users' => 'custom'
			]
		]);

		$this->assertTrue($context->isRequired('user.username'));
		$this->assertFalse($context->isRequired('user.first_name'));
	}

/**
 * Test type() basic
 *
 * @return void
 */
	public function testType() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'body' => 'Some content',
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);

		$this->assertEquals('string', $context->type('title'));
		$this->assertEquals('text', $context->type('body'));
		$this->assertEquals('integer', $context->type('user_id'));
		$this->assertNull($context->type('nope'));
	}

/**
 * Test getting types for associated records.
 *
 * @return void
 */
	public function testTypeAssociated() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'user' => new Entity(['username' => 'Mark']),
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);

		$this->assertEquals('string', $context->type('user.username'));
		$this->assertEquals('text', $context->type('user.bio'));
		$this->assertNull($context->type('user.nope'));
	}

/**
 * Test attributes for fields.
 *
 * @return void
 */
	public function testAttributes() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'user' => new Entity(['username' => 'Mark']),
		]);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);

		$expected = [
			'length' => 255, 'precision' => null
		];
		$this->assertEquals($expected, $context->attributes('title'));

		$expected = [
			'length' => null, 'precision' => null
		];
		$this->assertEquals($expected, $context->attributes('body'));

		$expected = [
			'length' => 10, 'precision' => 3
		];
		$this->assertEquals($expected, $context->attributes('user.rating'));
	}

/**
 * Test hasError
 *
 * @return void
 */
	public function testHasError() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'user' => new Entity(['username' => 'Mark']),
		]);
		$row->errors('title', []);
		$row->errors('body', 'Gotta have one');
		$row->errors('user_id', ['Required field']);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);

		$this->assertFalse($context->hasError('title'));
		$this->assertFalse($context->hasError('nope'));
		$this->assertTrue($context->hasError('body'));
		$this->assertTrue($context->hasError('user_id'));
	}

/**
 * Test hasError on associated records
 *
 * @return void
 */
	public function testHasErrorAssociated() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'user' => new Entity(['username' => 'Mark']),
		]);
		$row->errors('title', []);
		$row->errors('body', 'Gotta have one');
		$row->user->errors('username', ['Required']);
		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);

		$this->assertTrue($context->hasError('user.username'));
		$this->assertFalse($context->hasError('user.nope'));
		$this->assertFalse($context->hasError('no.nope'));
	}

/**
 * Test error
 *
 * @return void
 */
	public function testError() {
		$this->_setupTables();

		$row = new Entity([
			'title' => 'My title',
			'user' => new Entity(['username' => 'Mark']),
		]);
		$row->errors('title', []);
		$row->errors('body', 'Gotta have one');
		$row->errors('user_id', ['Required field']);

		$row->user->errors('username', ['Required']);

		$context = new EntityContext($this->request, [
			'entity' => $row,
			'table' => 'Articles',
		]);

		$this->assertEquals([], $context->error('title'));

		$expected = ['Gotta have one'];
		$this->assertEquals($expected, $context->error('body'));

		$expected = ['Required'];
		$this->assertEquals($expected, $context->error('user.username'));
	}

/**
 * Setup tables for tests.
 *
 * @return void
 */
	protected function _setupTables() {
		$articles = TableRegistry::get('Articles');
		$articles->belongsTo('Users');
		$articles->hasMany('Comments');

		$comments = TableRegistry::get('Comments');
		$users = TableRegistry::get('Users');

		$articles->schema([
			'id' => ['type' => 'integer', 'length' => 11, 'null' => false],
			'title' => ['type' => 'string', 'length' => 255],
			'user_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
			'body' => ['type' => 'text']
		]);
		$users->schema([
			'id' => ['type' => 'integer', 'length' => 11],
			'username' => ['type' => 'string', 'length' => 255],
			'bio' => ['type' => 'text'],
			'rating' => ['type' => 'decimal', 'length' => 10, 'precision' => 3],
		]);

		$validator = new Validator();
		$validator->add('title', 'minlength', [
			'rule' => ['minlength', 10]
		])
		->add('body', 'maxlength', [
			'rule' => ['maxlength', 1000]
		])->allowEmpty('body');
		$articles->validator('create', $validator);

		$validator = new Validator();
		$validator->add('username', 'length', [
			'rule' => ['minlength', 10]
		]);
		$users->validator('custom', $validator);

		$validator = new Validator();
		$validator->add('comment', 'length', [
			'rule' => ['minlength', 10]
		]);
		$comments->validator('custom', $validator);
	}

}
