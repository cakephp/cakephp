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
	public $fixtures = ['core.articles', 'core.authors'];

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
			->add(function (Entity $author) {
				$author->errors('name', ['This is an error']);
				return false;
			});

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
				$entity->errors('title', ['Some error']);
				return false;
			});

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
				if ($entity->title !== '1') {
					$entity->errors('title', ['an error']);
					return false;
				}
				return true;
			});

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

}
