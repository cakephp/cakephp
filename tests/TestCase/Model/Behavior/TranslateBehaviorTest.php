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
namespace Cake\Test\TestCase\Model\Behavior;

use Cake\Event\Event;
use Cake\Model\Behavior\TranslateBehavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Translate behavior test case
 */
class TranslateBehaviorTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = [
		'core.translate',
		'core.article'
	];

/**
 * Tests that fields from a translated model are overriden
 *
 * @return void
 */
	public function testFindSingleLocale() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$table->locale('eng');
		$results = $table->find()->combine('title', 'body', 'id')->toArray();
		$expected = [
			1 => ['Title #1' => 'Content #1'],
			2 => ['Title #2' => 'Content #2'],
			3 => ['Title #3' => 'Content #3'],
		];
		$this->assertSame($expected, $results);
	}

/**
 * Tests that overriding fields with the translate behavior works when
 * using conditions and that all other columns are preserved
 *
 * @return void
 */
	public function testFindSingleLocaleWithConditions() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$table->locale('eng');
		$results = $table->find()
			->where(['Articles.id' => 2])
			->all();

		$this->assertCount(1, $results);
		$row = $results->first();

		$expected = [
			'id' => 2,
			'title' => 'Title #2',
			'body' => 'Content #2',
			'author_id' => 3,
			'published' => 'Y',
			'_locale' => 'eng'
		];
		$this->assertEquals($expected, $row->toArray());
	}

}

