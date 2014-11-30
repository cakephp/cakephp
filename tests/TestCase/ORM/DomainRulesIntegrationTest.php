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
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests the integration between the ORM and the doamin rules cheker
 */
class DomainRulesIntegrationTest extends TestCase {

/**
 * Fixtures to be loaded
 *
 * @var array
 */
	public $fixtures = ['core.articles', 'core.authors', 'core.tags', 'core.articles_tags'];

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Tests saving belongsTo association and get a validation error
 *
 * @group save
 * @return void
 */
	public function testsSaveBelongsToWithValidationError() {
		$entity = new Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->author = new Entity([
			'name' => 'Jose'
		]);

		$table = TableRegistry::get('articles');
		$table->belongsTo('authors');
		$table->association('authors')
			->target()
			->domainRules()
			->add(function (Entity $author, array $options) use ($table) {
				$this->assertSame($options['repository'], $table->association('authors')->target());
				return false;
			}, ['errorField' => 'name', 'message' => 'This is an error']);

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertTrue($entity->author->isNew());
		$this->assertNull($entity->get('author_id'));
		$this->assertNotEmpty($entity->author->errors('name'));
	}

/**
 * Tests saving hasOne association and returning a validation error will
 * abort the saving process
 *
 * @group save
 * @return void
 */
	public function testSaveHasOneWithValidationError() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->article = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);

		$table = TableRegistry::get('authors');
		$table->hasOne('articles');
		$table->association('articles')
			->target()
			->domainRules()
			->add(function (Entity $entity) {
				return false;
			}, ['errorField' => 'title', 'message' => 'This is an error']);

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertTrue($entity->article->isNew());
		$this->assertNull($entity->article->id);
		$this->assertNull($entity->article->get('author_id'));
		$this->assertFalse($entity->article->dirty('author_id'));
		$this->assertNotEmpty($entity->article->errors('title'));
	}

/**
 * Tests saving multiple entities in a hasMany association and getting and
 * error while saving one of them. It should abort all the save operation
 * when options are set to defaults
 *
 * @return void
 */
	public function testSaveHasManyWithErrorsAtomic() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->articles = [
			new \Cake\ORM\Entity([
				'title' => '1',
				'body' => 'A body'
			]),
			new \Cake\ORM\Entity([
				'title' => 'Another Title',
				'body' => 'Another body'
			])
		];

		$table = TableRegistry::get('authors');
		$table->hasMany('articles');
		$table->association('articles')
			->target()
			->domainRules()
			->add(function (Entity $entity) {
				return $entity->title === '1';
			}, ['errorField' => 'title', 'message' => 'This is an error']);

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertTrue($entity->articles[0]->isNew());
		$this->assertTrue($entity->articles[1]->isNew());
		$this->assertNull($entity->articles[0]->id);
		$this->assertNull($entity->articles[1]->id);
		$this->assertNull($entity->articles[0]->author_id);
		$this->assertNull($entity->articles[1]->author_id);
		$this->assertEmpty($entity->articles[0]->errors());
		$this->assertNotEmpty($entity->articles[1]->errors());
	}

/**
 * Tests that it is possible to continue saving hasMany associations
 * even if any of the records fail validation when atomic is set
 * to false
 *
 * @return void
 */
	public function testSaveHasManyWithErrorsNonAtomic() {
		$entity = new \Cake\ORM\Entity([
			'name' => 'Jose'
		]);
		$entity->articles = [
			new \Cake\ORM\Entity([
				'title' => 'A title',
				'body' => 'A body'
			]),
			new \Cake\ORM\Entity([
				'title' => '1',
				'body' => 'Another body'
			])
		];

		$table = TableRegistry::get('authors');
		$table->hasMany('articles');
		$table->association('articles')
			->target()
			->domainRules()
			->add(function (Entity $article) {
				return is_numeric($article->title);
			}, ['errorField' => 'title', 'message' => 'This is an error']);

		$result = $table->save($entity, ['atomic' => false]);
		$this->assertSame($entity, $result);
		$this->assertFalse($entity->isNew());
		$this->assertTrue($entity->articles[0]->isNew());
		$this->assertFalse($entity->articles[1]->isNew());
		$this->assertEquals(4, $entity->articles[1]->id);
		$this->assertNull($entity->articles[0]->id);
		$this->assertNotEmpty($entity->articles[0]->errors('title'));
	}

/**
 * Tests saving belongsToMany records with a validation error in a joint entity
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToManyWithValidationErrorInJointEntity() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->tags = [
			new \Cake\ORM\Entity([
				'name' => 'Something New'
			]),
			new \Cake\ORM\Entity([
				'name' => '100'
			])
		];
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$table->association('tags')
			->junction()
			->domainRules()
			->add(function (Entity $entity) {
				return $entity->article_id > 4;
			});

		$this->assertFalse($table->save($entity));
		$this->assertTrue($entity->isNew());
		$this->assertTrue($entity->tags[0]->isNew());
		$this->assertTrue($entity->tags[1]->isNew());
		$this->assertNull($entity->tags[0]->id);
		$this->assertNull($entity->tags[1]->id);
		$this->assertNull($entity->tags[0]->_joinData);
		$this->assertNull($entity->tags[1]->_joinData);
	}

/**
 * Tests saving belongsToMany records with a validation error in a joint entity
 * and atomic set to false
 *
 * @group save
 * @return void
 */
	public function testSaveBelongsToManyWithValidationErrorInJointEntityNonAtomic() {
		$entity = new \Cake\ORM\Entity([
			'title' => 'A Title',
			'body' => 'A body'
		]);
		$entity->tags = [
			new \Cake\ORM\Entity([
				'name' => 'Something New'
			]),
			new \Cake\ORM\Entity([
				'name' => 'New one'
			])
		];
		$table = TableRegistry::get('articles');
		$table->belongsToMany('tags');
		$table->association('tags')
			->junction()
			->domainRules()
			->add(function (Entity $entity) {
				return $entity->tag_id > 4;
			});

		$this->assertSame($entity, $table->save($entity, ['atomic' => false]));
		$this->assertFalse($entity->isNew());
		$this->assertFalse($entity->tags[0]->isNew());
		$this->assertFalse($entity->tags[1]->isNew());
		$this->assertEquals(4, $entity->tags[0]->id);
		$this->assertEquals(5, $entity->tags[1]->id);
		$this->assertTrue($entity->tags[0]->_joinData->isNew());
		$this->assertEquals(4, $entity->tags[1]->_joinData->article_id);
		$this->assertEquals(5, $entity->tags[1]->_joinData->tag_id);
	}

/**
 * Tests the isUnique domain rule
 *
 * @group save
 * @return void
 */
	public function testIsUniqueDomainRule() {
		$entity = new Entity([
			'name' => 'larry'
		]);

		$table = TableRegistry::get('Authors');
		$rules = $table->domainRules();
		$rules->add($rules->isUnique(['name']));

		$this->assertFalse($table->save($entity));
		$this->assertEquals(['This value is already in use'], $entity->errors('name'));

		$entity->name = 'jose';
		$this->assertSame($entity, $table->save($entity));

		$entity = $table->get(1);
		$entity->dirty('name', true);
		$this->assertSame($entity, $table->save($entity));
	}

/**
 * Tests isUnique with multiple fields
 *
 * @group save
 * @return void
 */
	public function testIsUniqueMultipleFields() {
		$entity = new Entity([
			'author_id' => 1,
			'title' => 'First Article'
		]);

		$table = TableRegistry::get('Articles');
		$rules = $table->domainRules();
		$rules->add($rules->isUnique(['title', 'author_id']));

		$this->assertFalse($table->save($entity));
		$this->assertEquals(['title' => ['This value is already in use']], $entity->errors());

		$entity->clean();
		$entity->author_id = 2;
		$this->assertSame($entity, $table->save($entity));
	}

}
