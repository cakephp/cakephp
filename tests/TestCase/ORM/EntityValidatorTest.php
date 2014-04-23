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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Entity;
use Cake\ORM\EntityValidator;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * EntityValidator test
 */
class EntityValidatorTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$articles = TableRegistry::get('Articles');
		$users = TableRegistry::get('Users');
		$articles->belongsTo('Users');
		$articles->hasMany('Comments');

		$comments = TableRegistry::get('Comments');
		$comments->belongsTo('Articles');
		$comments->belongsTo('Users');

		$this->articles = $articles;
		$this->comments = $comments;
		$this->users = $users;
	}

/**
 * Teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
		unset($this->articles, $this->comments, $this->users);
	}

/**
 * Test one() with successful validate
 *
 * @return void
 */
	public function testOneSuccess() {
		$entity = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$validator = $this->getMock('\Cake\Validation\Validator');
		$this->articles->validator('default', $validator);
		$entityValidator = new EntityValidator($this->articles);

		$validator->expects($this->once())
			->method('count')
			->will($this->returnValue(1));
		$entity->expects($this->once())
			->method('validate')
			->with($validator)
			->will($this->returnValue(true));

		$this->assertTrue($entityValidator->one($entity));
	}

/**
 * Test one() with failing validate
 *
 * @return void
 */
	public function testOneFail() {
		$entity = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$validator = $this->getMock('\Cake\Validation\Validator');
		$this->articles->validator('default', $validator);
		$entityValidator = new EntityValidator($this->articles);

		$validator->expects($this->once())
			->method('count')
			->will($this->returnValue(1));
		$entity->expects($this->once())
			->method('validate')
			->with($validator)
			->will($this->returnValue(false));

		$this->assertFalse($entityValidator->one($entity));
	}

/**
 * test one() with association data.
 *
 * @return void
 */
	public function testOneAssociationsSuccess() {
		$article = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$comment1 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$comment2 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$user = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$article->set('comments', [$comment1, $comment2]);
		$article->set('user', $user);

		$validator1 = $this->getMock('\Cake\Validation\Validator');
		$validator2 = $this->getMock('\Cake\Validation\Validator');
		$validator3 = $this->getMock('\Cake\Validation\Validator');

		$validator1->expects($this->once())
			->method('count')
			->will($this->returnValue(1));
		$validator2->expects($this->exactly(2))
			->method('count')
			->will($this->returnValue(1));
		$validator3->expects($this->once())
			->method('count')
			->will($this->returnValue(1));

		$this->articles->validator('default', $validator1);
		$this->comments->validator('default', $validator2);
		$this->users->validator('default', $validator3);

		$entityValidator = new EntityValidator($this->articles);

		$article->expects($this->once())
			->method('validate')
			->with($validator1)
			->will($this->returnValue(true));

		$comment1->expects($this->once())
			->method('validate')
			->with($validator2)
			->will($this->returnValue(true));

		$comment2->expects($this->once())
			->method('validate')
			->with($validator2)
			->will($this->returnValue(true));

		$user->expects($this->once())
			->method('validate')
			->with($validator3)
			->will($this->returnValue(true));

		$options = ['associated' => ['Comments', 'Users']];
		$this->assertTrue($entityValidator->one($article, $options));
	}

/**
 * test one() with association data and one of them failing  validation.
 *
 * @return void
 */
	public function testOneAssociationsFail() {
		$article = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$comment1 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$comment2 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$user = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$article->set('comments', [$comment1, $comment2]);
		$article->set('user', $user);

		$validator1 = $this->getMock('\Cake\Validation\Validator');
		$validator2 = $this->getMock('\Cake\Validation\Validator');
		$validator3 = $this->getMock('\Cake\Validation\Validator');

		$validator1->expects($this->once())
			->method('count')
			->will($this->returnValue(1));
		$validator2->expects($this->exactly(2))
			->method('count')
			->will($this->returnValue(1));
		$validator3->expects($this->once())
			->method('count')
			->will($this->returnValue(1));

		$this->articles->validator('default', $validator1);
		$this->comments->validator('default', $validator2);
		$this->users->validator('default', $validator3);

		$entityValidator = new EntityValidator($this->articles);

		$article->expects($this->once())
			->method('validate')
			->with($validator1)
			->will($this->returnValue(true));

		$comment1->expects($this->once())
			->method('validate')
			->with($validator2)
			->will($this->returnValue(true));

		$comment2->expects($this->once())
			->method('validate')
			->with($validator2)
			->will($this->returnValue(false));

		$user->expects($this->once())
			->method('validate')
			->with($validator3)
			->will($this->returnValue(true));

		$options = ['associated' => ['Comments', 'Users']];
		$this->assertFalse($entityValidator->one($article, $options));
	}

/**
 * Test one() with deeper associations and passing the name for custom
 * validators
 *
 * @return void
 */
	public function testOneDeepAssociationsAndCustomValidators() {
		$comment = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$article = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$user = $this->getMock('\Cake\ORM\Entity', ['validate']);

		$comment->set('article', $article);
		$article->set('user', $user);

		$validator1 = $this->getMock('\Cake\Validation\Validator');
		$validator2 = $this->getMock('\Cake\Validation\Validator');
		$validator3 = $this->getMock('\Cake\Validation\Validator');

		$validator1->expects($this->once())
			->method('count')
			->will($this->returnValue(1));
		$validator2->expects($this->once())
			->method('count')
			->will($this->returnValue(1));
		$validator3->expects($this->once())
			->method('count')
			->will($this->returnValue(1));

		$this->articles->validator('crazy', $validator1);
		$this->comments->validator('default', $validator2);
		$this->users->validator('funky', $validator3);

		$entityValidator = new EntityValidator($this->comments);
		$comment->expects($this->once())
			->method('validate')
			->with($validator2)
			->will($this->returnValue(true));

		$article->expects($this->once())
			->method('validate')
			->with($validator1)
			->will($this->returnValue(true));

		$user->expects($this->once())
			->method('validate')
			->with($validator3)
			->will($this->returnValue(true));

		$this->assertTrue($entityValidator->one($comment, [
			'associated' => [
				'Articles' => [
					'validate' => 'crazy',
					'associated' => ['Users' => ['validate' => 'funky']]
				]
			]
		]));
	}

/**
 * Test many() with successful validate
 *
 * @return void
 */
	public function testManySuccess() {
		$comment1 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$comment2 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$validator = $this->getMock('\Cake\Validation\Validator');
		$data = [$comment1, $comment2];
		$this->comments->validator('default', $validator);
		$entityValidator = new EntityValidator($this->comments);

		$validator->expects($this->exactly(2))
			->method('count')
			->will($this->returnValue(1));
		$comment1->expects($this->once())
			->method('validate')
			->with($validator)
			->will($this->returnValue(true));

		$comment2->expects($this->once())
			->method('validate')
			->with($validator)
			->will($this->returnValue(true));

		$this->assertTrue($entityValidator->many($data));
	}

/**
 * Test many() with failure
 *
 * @return void
 */
	public function testManyFailure() {
		$comment1 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$comment2 = $this->getMock('\Cake\ORM\Entity', ['validate']);
		$validator = $this->getMock('\Cake\Validation\Validator');
		$data = [$comment1, $comment2];
		$this->comments->validator('default', $validator);
		$entityValidator = new EntityValidator($this->comments);

		$validator->expects($this->exactly(2))
			->method('count')
			->will($this->returnValue(1));

		$comment1->expects($this->once())
			->method('validate')
			->with($validator)
			->will($this->returnValue(false));

		$comment2->expects($this->once())
			->method('validate')
			->with($validator)
			->will($this->returnValue(true));

		$this->assertFalse($entityValidator->many($data));
	}

}
