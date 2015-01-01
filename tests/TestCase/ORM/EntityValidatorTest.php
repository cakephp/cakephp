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
class EntityValidatorTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
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
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
        unset($this->articles, $this->comments, $this->users);
    }

    /**
     * Test one() with successful validate
     *
     * @return void
     */
    public function testOneSuccess()
    {
        $entity = $this->getMock('TestApp\Model\Entity\ValidatableEntity', ['validate']);
        $validator = $this->getMock('\Cake\Validation\Validator');
        $this->articles->validator('default', $validator);
        $entityValidator = new EntityValidator($this->articles);

        $validator->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));
        $entity->expects($this->once())
            ->method('validate')
            ->with($validator)
            ->will($this->returnValue([]));

        $this->assertTrue($entityValidator->one($entity));
    }

    /**
     * Test one() with failing validate
     *
     * @return void
     */
    public function testOneFail()
    {
        $entity = $this->getMock('TestApp\Model\Entity\ValidatableEntity', ['validate']);
        $validator = $this->getMock('\Cake\Validation\Validator');
        $this->articles->validator('default', $validator);
        $entityValidator = new EntityValidator($this->articles);

        $validator->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));
        $entity->expects($this->once())
            ->method('validate')
            ->with($validator)
            ->will($this->returnValue(['one' => ['error']]));

        $this->assertFalse($entityValidator->one($entity));
    }

    /**
     * test one() with association data.
     *
     * @return void
     */
    public function testOneAssociationsSuccess()
    {
        $class = 'TestApp\Model\Entity\ValidatableEntity';
        $article = $this->getMock($class, ['validate']);
        $comment1 = $this->getMock($class, ['validate']);
        $comment2 = $this->getMock($class, ['validate']);
        $user = $this->getMock($class, ['validate']);
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
            ->will($this->returnValue([]));

        $comment1->expects($this->once())
            ->method('validate')
            ->with($validator2)
            ->will($this->returnValue([]));

        $comment2->expects($this->once())
            ->method('validate')
            ->with($validator2)
            ->will($this->returnValue([]));

        $user->expects($this->once())
            ->method('validate')
            ->with($validator3)
            ->will($this->returnValue([]));

        $options = ['associated' => ['Comments', 'Users']];
        $this->assertTrue($entityValidator->one($article, $options));
    }

    /**
     * test one() with associations that are not entities.
     *
     * This can happen when request data is not completely marshalled.
     * incomplete associations should not cause warnings or fatal errors.
     *
     * @return void
     */
    public function testOneAssociationsNoEntities()
    {
        $class = 'TestApp\Model\Entity\ValidatableEntity';
        $article = $this->getMock($class, ['validate']);
        $comment1 = ['comment' => 'test'];
        $comment2 = ['comment' => 'omg'];
        $user = $this->getMock($class, ['validate']);
        $article->set('comments', [$comment1, $comment2]);

        $validator1 = $this->getMock('\Cake\Validation\Validator');
        $validator2 = $this->getMock('\Cake\Validation\Validator');

        $validator1->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        // Should not be called as comments are not entities.
        $validator2->expects($this->never())
            ->method('count');

        $this->articles->validator('default', $validator1);
        $this->comments->validator('default', $validator2);

        $entityValidator = new EntityValidator($this->articles);

        $article->expects($this->once())
            ->method('validate')
            ->with($validator1)
            ->will($this->returnValue([]));

        $options = ['associated' => ['Comments']];
        $this->assertFalse($entityValidator->one($article, $options));
    }

    /**
     * test one() with association data and one of them failing  validation.
     *
     * @return void
     */
    public function testOneAssociationsFail()
    {
        $class = 'TestApp\Model\Entity\ValidatableEntity';
        $article = $this->getMock($class, ['validate']);
        $comment1 = $this->getMock($class, ['validate']);
        $comment2 = $this->getMock($class, ['validate']);
        $user = $this->getMock($class, ['validate']);
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
            ->will($this->returnValue([]));

        $comment1->expects($this->once())
            ->method('validate')
            ->with($validator2)
            ->will($this->returnValue([]));

        $comment2->expects($this->once())
            ->method('validate')
            ->with($validator2)
            ->will($this->returnValue(['some' => ['error']]));

        $user->expects($this->once())
            ->method('validate')
            ->with($validator3)
            ->will($this->returnValue([]));

        $options = ['associated' => ['Comments', 'Users']];
        $this->assertFalse($entityValidator->one($article, $options));
    }

    /**
     * Test one() with deeper associations and passing the name for custom
     * validators
     *
     * @return void
     */
    public function testOneDeepAssociationsAndCustomValidators()
    {
        $class = 'TestApp\Model\Entity\ValidatableEntity';
        $comment = $this->getMock($class, ['validate']);
        $article = $this->getMock($class, ['validate']);
        $user = $this->getMock($class, ['validate']);

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
            ->will($this->returnValue([]));

        $article->expects($this->once())
            ->method('validate')
            ->with($validator1)
            ->will($this->returnValue([]));

        $user->expects($this->once())
            ->method('validate')
            ->with($validator3)
            ->will($this->returnValue([]));

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
    public function testManySuccess()
    {
        $class = 'TestApp\Model\Entity\ValidatableEntity';
        $comment1 = $this->getMock($class, ['validate']);
        $comment2 = $this->getMock($class, ['validate']);
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
            ->will($this->returnValue([]));

        $comment2->expects($this->once())
            ->method('validate')
            ->with($validator)
            ->will($this->returnValue([]));

        $this->assertTrue($entityValidator->many($data));
    }

    /**
     * Test many() with failure
     *
     * @return void
     */
    public function testManyFailure()
    {
        $class = 'TestApp\Model\Entity\ValidatableEntity';
        $comment1 = $this->getMock($class, ['validate']);
        $comment2 = $this->getMock($class, ['validate']);
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
            ->will($this->returnValue(['some' => ['error']]));

        $comment2->expects($this->once())
            ->method('validate')
            ->with($validator)
            ->will($this->returnValue([]));

        $this->assertFalse($entityValidator->many($data));
    }
}
