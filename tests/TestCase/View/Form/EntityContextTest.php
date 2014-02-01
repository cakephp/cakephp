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
		$articles = TableRegistry::get('Articles');

		$validator = $articles->validator();
		$validator->add('title', 'minlength', [
			'rule' => ['minlength', 10]
		])
		->add('body', 'maxlength', [
			'rule' => ['maxlength', 1000]
		])->allowEmpty('body');

		$context = new EntityContext($this->request, [
			'entity' => new Entity(),
			'table' => 'Articles',
			'validator' => 'default',
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
		$articles = TableRegistry::get('Articles');
		$articles->hasMany('Comments');
		$comments = TableRegistry::get('Comments');

		$validator = $articles->validator();
		$validator->add('title', 'minlength', [
			'rule' => ['minlength', 10]
		]);

		$validator = $comments->validator();
		$validator->add('comment', 'length', [
			'rule' => ['minlength', 10]
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

		// $this->assertTrue($context->isRequired('Articles.title'));
		// $this->assertFalse($context->isRequired('Articles.body'));

		$this->assertTrue($context->isRequired('comments.0.comment'));
		$this->assertTrue($context->isRequired('Articles.comments.0.comment'));

		$this->assertFalse($context->isRequired('comments.0.other'));
		$this->assertFalse($context->isRequired('Articles.comments.0.other'));
	}

/**
 * Test isRequired on associated entities with custom validators.
 *
 * @return void
 */
	public function testIsRequiredAssociatedValidator() {
		$articles = TableRegistry::get('Articles');
		$articles->hasMany('Comments');
		$comments = TableRegistry::get('Comments');

		$validator = new Validator();
		$validator->add('title', 'minlength', [
			'rule' => ['minlength', 10]
		]);
		$articles->validator('create', $validator);

		$validator = new Validator();
		$validator->add('comment', 'length', [
			'rule' => ['minlength', 10]
		]);
		$comments->validator('custom', $validator);

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
		$articles = TableRegistry::get('Articles');
		$articles->belongsTo('Users');
		$users = TableRegistry::get('Users');

		$validator = new Validator();
		$validator->add('title', 'minlength', [
			'rule' => ['minlength', 10]
		]);
		$articles->validator('create', $validator);

		$validator = new Validator();
		$validator->add('username', 'length', [
			'rule' => ['minlength', 10]
		]);
		$users->validator('custom', $validator);

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

}
