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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests the features related to proxying methods from the Association
 * class to the Table class
 *
 */
class AssociationProxyTest extends TestCase {

/**
 * Fixtures to be loaded
 *
 * @var array
 */
	public $fixtures = [
		'core.article', 'core.author', 'core.comment'
	];

/**
 * Tests that it is possible to get associations as a property
 *
 * @return void
 */
	public function testAssociationAsProperty() {
		$articles = TableRegistry::get('articles');
		$articles->hasMany('comments');
		$articles->belongsTo('authors');
		$this->assertTrue(isset($articles->authors));
		$this->assertTrue(isset($articles->comments));
		$this->assertFalse(isset($articles->posts));
		$this->assertSame($articles->association('authors'), $articles->authors);
		$this->assertSame($articles->association('comments'), $articles->comments);
	}

/**
 * Tests that getting a bad property throws exception
 *
 * @expectedException \RuntimeException
 * @expectedExceptionMessage Table "TestApp\Model\Table\ArticlesTable" is not associated with "posts"
 * @return void
 */
	public function testGetBadAssociation() {
		$articles = TableRegistry::get('articles');
		$articles->posts;
	}

}
