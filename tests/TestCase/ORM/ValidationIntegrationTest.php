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
 * Tests the integration between the ORM and the validator
 */
class ValidationIntegrationTest extends TestCase {

/**
 * Fixtures to be loaded
 *
 * @var array
 */
	public $fixtures = ['core.articles'];

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
 * Tests that Table::validateUnique handle validation params correctly
 *
 * @return void
 */
	public function testValidateUnique() {
		$articles = TableRegistry::get('articles');
		$articles->validator()->add('title', [
			'unique' => [
				'rule' => 'validateUnique',
				'provider' => 'table',
				'message' => 'Y U NO WRITE UNIQUE?'
			]
		]);
		$entity = new Entity(['title' => 'First Article', 'body' => 'Foo']);
		$this->assertFalse($articles->validate($entity));
		$this->assertEquals('Y U NO WRITE UNIQUE?', $entity->errors()['title']['unique']);

		$entity = new Entity(['title' => 'New Article', 'body' => 'Foo']);
		$this->assertTrue($articles->validate($entity));
	}

/**
 * Tests that validateUnique can be scoped to another field in the provided data
 *
 * @return void
 */
	public function testValidateUniqueWithScope() {
		$articles = TableRegistry::get('articles');
		$articles->validator()->add('title', [
			'unique' => [
				'rule' => ['validateUnique', ['scope' => 'published']],
				'provider' => 'table'
			]
		]);
		$entity = new Entity(['title' => 'First Article', 'published' => 'N']);
		$this->assertTrue($articles->validate($entity));

		$entity->published = 'Y';
		$this->assertFalse($articles->validate($entity));
	}

/**
 * Tests that uniqueness validation excludes the same record when it exists
 *
 * @return void
 */
	public function testValidateUniqueUpdate() {
		$articles = TableRegistry::get('articles');
		$articles->validator()->add('title', [
			'unique' => [
				'rule' => 'validateUnique',
				'provider' => 'table'
			]
		]);
		$entity = new Entity(['id' => 1, 'title' => 'First Article'], ['markNew' => false]);
		$this->assertTrue($articles->validate($entity));

		$entity = new Entity(['id' => 2, 'title' => 'First Article'], ['markNew' => false]);
		$this->assertFalse($articles->validate($entity));
	}
}
