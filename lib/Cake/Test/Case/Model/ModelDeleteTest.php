<?php
/**
 * ModelDeleteTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once dirname(__FILE__) . DS . 'ModelTestBase.php';

/**
 * ModelDeleteTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelDeleteTest extends BaseModelTest {

/**
 * testDeleteHabtmReferenceWithConditions method
 *
 * @return void
 */
	public function testDeleteHabtmReferenceWithConditions() {
		$this->loadFixtures('Portfolio', 'Item', 'ItemsPortfolio', 'Syfile', 'Image');

		$Portfolio = new Portfolio();
		$Portfolio->hasAndBelongsToMany['Item']['conditions'] = array('ItemsPortfolio.item_id >' => 1);

		$result = $Portfolio->find('first', array(
			'conditions' => array('Portfolio.id' => 1)
		));
		$expected = array(
			array(
				'id' => 3,
				'syfile_id' => 3,
				'published' => false,
				'name' => 'Item 3',
				'ItemsPortfolio' => array(
					'id' => 3,
					'item_id' => 3,
					'portfolio_id' => 1
			)),
			array(
				'id' => 4,
				'syfile_id' => 4,
				'published' => false,
				'name' => 'Item 4',
				'ItemsPortfolio' => array(
					'id' => 4,
					'item_id' => 4,
					'portfolio_id' => 1
			)),
			array(
				'id' => 5,
				'syfile_id' => 5,
				'published' => false,
				'name' => 'Item 5',
				'ItemsPortfolio' => array(
					'id' => 5,
					'item_id' => 5,
					'portfolio_id' => 1
		)));
		$this->assertEquals($result['Item'], $expected);

		$result = $Portfolio->ItemsPortfolio->find('all', array(
			'conditions' => array('ItemsPortfolio.portfolio_id' => 1)
		));
		$expected = array(
			array(
				'ItemsPortfolio' => array(
					'id' => 1,
					'item_id' => 1,
					'portfolio_id' => 1
			)),
			array(
				'ItemsPortfolio' => array(
					'id' => 3,
					'item_id' => 3,
					'portfolio_id' => 1
			)),
			array(
				'ItemsPortfolio' => array(
					'id' => 4,
					'item_id' => 4,
					'portfolio_id' => 1
			)),
			array(
				'ItemsPortfolio' => array(
					'id' => 5,
					'item_id' => 5,
					'portfolio_id' => 1
		)));
		$this->assertEquals($expected, $result);

		$Portfolio->delete(1);

		$result = $Portfolio->find('first', array(
			'conditions' => array('Portfolio.id' => 1)
		));
		$this->assertFalse($result);

		$result = $Portfolio->ItemsPortfolio->find('all', array(
			'conditions' => array('ItemsPortfolio.portfolio_id' => 1)
		));
		$this->assertEquals($result, array());
	}

/**
 * testDeleteArticleBLinks method
 *
 * @return void
 */
	public function testDeleteArticleBLinks() {
		$this->loadFixtures('Article', 'ArticlesTag', 'Tag', 'User');
		$TestModel = new ArticleB();

		$result = $TestModel->ArticlesTag->find('all');
		$expected = array(
			array('ArticlesTag' => array('article_id' => '1', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '1', 'tag_id' => '2')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '3'))
			);
		$this->assertEquals($expected, $result);

		$TestModel->delete(1);
		$result = $TestModel->ArticlesTag->find('all');

		$expected = array(
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '3'))
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testDeleteDependentWithConditions method
 *
 * @return void
 */
	public function testDeleteDependentWithConditions() {
		$this->loadFixtures('Cd','Book','OverallFavorite');

		$Cd = new Cd();
		$Book = new Book();
		$OverallFavorite = new OverallFavorite();

		$Cd->delete(1);

		$result = $OverallFavorite->find('all', array(
			'fields' => array('model_type', 'model_id', 'priority')
		));
		$expected = array(
			array(
				'OverallFavorite' => array(
					'model_type' => 'Book',
					'model_id' => 1,
					'priority' => 2
		)));

		$this->assertTrue(is_array($result));
		$this->assertEquals($expected, $result);

		$Book->delete(1);

		$result = $OverallFavorite->find('all', array(
			'fields' => array('model_type', 'model_id', 'priority')
		));
		$expected = array();

		$this->assertTrue(is_array($result));
		$this->assertEquals($expected, $result);
	}

/**
 * testDel method
 *
 * @return void
 */
	public function testDelete() {
		$this->loadFixtures('Article', 'Comment', 'Attachment');
		$TestModel = new Article();

		$result = $TestModel->delete(2);
		$this->assertTrue($result);

		$result = $TestModel->read(null, 2);
		$this->assertFalse($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array(
			'fields' => array('id', 'title')
		));
		$expected = array(
			array('Article' => array(
				'id' => 1,
				'title' => 'First Article'
			)),
			array('Article' => array(
				'id' => 3,
				'title' => 'Third Article'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->delete(3);
		$this->assertTrue($result);

		$result = $TestModel->read(null, 3);
		$this->assertFalse($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array(
			'fields' => array('id', 'title')
		));
		$expected = array(
			array('Article' => array(
				'id' => 1,
				'title' => 'First Article'
		)));

		$this->assertEquals($expected, $result);

		// make sure deleting a non-existent record doesn't break save()
		// ticket #6293
		$this->loadFixtures('Uuid');
		$Uuid = new Uuid();
		$data = array(
			'B607DAB9-88A2-46CF-B57C-842CA9E3B3B3',
			'52C8865C-10EE-4302-AE6C-6E7D8E12E2C8',
			'8208C7FE-E89C-47C5-B378-DED6C271F9B8');
		foreach ($data as $id) {
			$Uuid->save(array('id' => $id));
		}
		$Uuid->delete('52C8865C-10EE-4302-AE6C-6E7D8E12E2C8');
		$Uuid->delete('52C8865C-10EE-4302-AE6C-6E7D8E12E2C8');
		foreach ($data as $id) {
			$Uuid->save(array('id' => $id));
		}
		$result = $Uuid->find('all', array(
			'conditions' => array('id' => $data),
			'fields' => array('id'),
			'order' => 'id'));
		$expected = array(
			array('Uuid' => array(
				'id' => '52C8865C-10EE-4302-AE6C-6E7D8E12E2C8')),
			array('Uuid' => array(
				'id' => '8208C7FE-E89C-47C5-B378-DED6C271F9B8')),
			array('Uuid' => array(
				'id' => 'B607DAB9-88A2-46CF-B57C-842CA9E3B3B3')));
		$this->assertEquals($expected, $result);
	}


/**
 * test that delete() updates the correct records counterCache() records.
 *
 * @return void
 */
	public function testDeleteUpdatingCounterCacheCorrectly() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();

		$User->Post->delete(3);
		$result = $User->read(null, 301);
		$this->assertEquals($result['User']['post_count'], 0);

		$result = $User->read(null, 66);
		$this->assertEquals($result['User']['post_count'], 2);
	}

/**
 * testDeleteAll method
 *
 * @return void
 */
	public function testDeleteAll() {
		$this->loadFixtures('Article');
		$TestModel = new Article();

		$data = array('Article' => array(
			'user_id' => 2,
			'id' => 4,
			'title' => 'Fourth Article',
			'published' => 'N'
		));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$data = array('Article' => array(
			'user_id' => 2,
			'id' => 5,
			'title' => 'Fifth Article',
			'published' => 'Y'
		));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$data = array('Article' => array(
			'user_id' => 1,
			'id' => 6,
			'title' => 'Sixth Article',
			'published' => 'N'
		));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array(
			'fields' => array('id', 'user_id', 'title', 'published')
		));

		$expected = array(
			array('Article' => array(
				'id' => 1,
				'user_id' => 1,
				'title' => 'First Article',
				'published' => 'Y'
			)),
			array('Article' => array(
				'id' => 2,
				'user_id' => 3,
				'title' => 'Second Article',
				'published' => 'Y'
			)),
			array('Article' => array(
				'id' => 3,
				'user_id' => 1,
				'title' => 'Third Article',
				'published' => 'Y')),
			array('Article' => array(
				'id' => 4,
				'user_id' => 2,
				'title' => 'Fourth Article',
				'published' => 'N'
			)),
			array('Article' => array(
				'id' => 5,
				'user_id' => 2,
				'title' => 'Fifth Article',
				'published' => 'Y'
			)),
			array('Article' => array(
				'id' => 6,
				'user_id' => 1,
				'title' => 'Sixth Article',
				'published' => 'N'
		)));

		$this->assertEquals($expected, $result);

		$result = $TestModel->deleteAll(array('Article.published' => 'N'));
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array(
			'fields' => array('id', 'user_id', 'title', 'published')
		));
		$expected = array(
			array('Article' => array(
				'id' => 1,
				'user_id' => 1,
				'title' => 'First Article',
				'published' => 'Y'
			)),
			array('Article' => array(
				'id' => 2,
				'user_id' => 3,
				'title' => 'Second Article',
				'published' => 'Y'
			)),
			array('Article' => array(
				'id' => 3,
				'user_id' => 1,
				'title' => 'Third Article',
				'published' => 'Y'
			)),
			array('Article' => array(
				'id' => 5,
				'user_id' => 2,
				'title' => 'Fifth Article',
				'published' => 'Y'
		)));
		$this->assertEquals($expected, $result);

		$data = array('Article.user_id' => array(2, 3));
		$result = $TestModel->deleteAll($data, true, true);
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array(
			'fields' => array('id', 'user_id', 'title', 'published')
		));
		$expected = array(
			array('Article' => array(
				'id' => 1,
				'user_id' => 1,
				'title' => 'First Article',
				'published' => 'Y'
			)),
			array('Article' => array(
				'id' => 3,
				'user_id' => 1,
				'title' => 'Third Article',
				'published' => 'Y'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->deleteAll(array('Article.user_id' => 999));
		$this->assertTrue($result, 'deleteAll returned false when all no records matched conditions. %s');
	}

/**
 * testDeleteAllUnknownColumn method
 *
 * @expectedException PDOException
 * @return void
 */
	public function testDeleteAllUnknownColumn() {
		$this->loadFixtures('Article');
		$TestModel = new Article();
		$result = $TestModel->deleteAll(array('Article.non_existent_field' => 999));
		$this->assertFalse($result, 'deleteAll returned true when find query generated sql error. %s');
	}

/**
 * testRecursiveDel method
 *
 * @return void
 */
	public function testRecursiveDel() {
		$this->loadFixtures('Article', 'Comment', 'Attachment');
		$TestModel = new Article();

		$result = $TestModel->delete(2);
		$this->assertTrue($result);

		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 2);
		$this->assertFalse($result);

		$result = $TestModel->Comment->read(null, 5);
		$this->assertFalse($result);

		$result = $TestModel->Comment->read(null, 6);
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->read(null, 1);
		$this->assertFalse($result);

		$result = $TestModel->find('count');
		$this->assertEquals($result, 2);

		$result = $TestModel->Comment->find('count');
		$this->assertEquals($result, 4);

		$result = $TestModel->Comment->Attachment->find('count');
		$this->assertEquals($result, 0);
	}

/**
 * testDependentExclusiveDelete method
 *
 * @return void
 */
	public function testDependentExclusiveDelete() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article10();

		$result = $TestModel->find('all');
		$this->assertEquals(count($result[0]['Comment']), 4);
		$this->assertEquals(count($result[1]['Comment']), 2);
		$this->assertEquals($TestModel->Comment->find('count'), 6);

		$TestModel->delete(1);
		$this->assertEquals($TestModel->Comment->find('count'), 2);
	}

/**
 * testDeleteLinks method
 *
 * @return void
 */
	public function testDeleteLinks() {
		$this->loadFixtures('Article', 'ArticlesTag', 'Tag');
		$TestModel = new Article();

		$result = $TestModel->ArticlesTag->find('all');
		$expected = array(
			array('ArticlesTag' => array(
				'article_id' => '1',
				'tag_id' => '1'
			)),
			array('ArticlesTag' => array(
				'article_id' => '1',
				'tag_id' => '2'
			)),
			array('ArticlesTag' => array(
				'article_id' => '2',
				'tag_id' => '1'
			)),
			array('ArticlesTag' => array(
				'article_id' => '2',
				'tag_id' => '3'
		)));
		$this->assertEquals($expected, $result);

		$TestModel->delete(1);
		$result = $TestModel->ArticlesTag->find('all');

		$expected = array(
			array('ArticlesTag' => array(
				'article_id' => '2',
				'tag_id' => '1'
			)),
			array('ArticlesTag' => array(
				'article_id' => '2',
				'tag_id' => '3'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->deleteAll(array('Article.user_id' => 999));
		$this->assertTrue($result, 'deleteAll returned false when all no records matched conditions. %s');
	}

/**
 * test that a plugin model as the 'with' model doesn't have issues
 *
 * @return void
 */
	public function testDeleteLinksWithPLuginJoinModel() {
		$this->loadFixtures('Article', 'ArticlesTag', 'Tag');
		$Article = new Article();
		$Article->unbindModel(array('hasAndBelongsToMany' => array('Tag')), false);
		unset($Article->Tag, $Article->ArticleTags);
		$Article->bindModel(array('hasAndBelongsToMany' => array(
			'Tag' => array('with' => 'TestPlugin.ArticlesTag')
		)), false);

		$this->assertTrue($Article->delete(1));
	}

/**
 * testDeleteDependent method
 *
 * @return void
 */
	public function testDeleteDependent() {
		$this->loadFixtures('Bidding', 'BiddingMessage');
		$Bidding = new Bidding();
		$result = $Bidding->find('all');
		$expected = array(
			array(
				'Bidding' => array('id' => 1, 'bid' => 'One', 'name' => 'Bid 1'),
				'BiddingMessage' => array('bidding' => 'One', 'name' => 'Message 1'),
			),
			array(
				'Bidding' => array('id' => 2, 'bid' => 'Two', 'name' => 'Bid 2'),
				'BiddingMessage' => array('bidding' => 'Two', 'name' => 'Message 2'),
			),
			array(
				'Bidding' => array('id' => 3, 'bid' => 'Three', 'name' => 'Bid 3'),
				'BiddingMessage' => array('bidding' => 'Three', 'name' => 'Message 3'),
			),
			array(
				'Bidding' => array('id' => 4, 'bid' => 'Five', 'name' => 'Bid 5'),
				'BiddingMessage' => array('bidding' => '', 'name' => ''),
			),
		);
		$this->assertEquals($expected, $result);

		$Bidding->delete(4, true);
		$result = $Bidding->find('all');
		$expected = array(
			array(
				'Bidding' => array('id' => 1, 'bid' => 'One', 'name' => 'Bid 1'),
				'BiddingMessage' => array('bidding' => 'One', 'name' => 'Message 1'),
			),
			array(
				'Bidding' => array('id' => 2, 'bid' => 'Two', 'name' => 'Bid 2'),
				'BiddingMessage' => array('bidding' => 'Two', 'name' => 'Message 2'),
			),
			array(
				'Bidding' => array('id' => 3, 'bid' => 'Three', 'name' => 'Bid 3'),
				'BiddingMessage' => array('bidding' => 'Three', 'name' => 'Message 3'),
			),
		);
		$this->assertEquals($expected, $result);

		$Bidding->delete(2, true);
		$result = $Bidding->find('all');
		$expected = array(
			array(
				'Bidding' => array('id' => 1, 'bid' => 'One', 'name' => 'Bid 1'),
				'BiddingMessage' => array('bidding' => 'One', 'name' => 'Message 1'),
			),
			array(
				'Bidding' => array('id' => 3, 'bid' => 'Three', 'name' => 'Bid 3'),
				'BiddingMessage' => array('bidding' => 'Three', 'name' => 'Message 3'),
			),
		);
		$this->assertEquals($expected, $result);

		$result = $Bidding->BiddingMessage->find('all', array('order' => array('BiddingMessage.name' => 'ASC')));
		$expected = array(
			array(
				'BiddingMessage' => array('bidding' => 'One', 'name' => 'Message 1'),
				'Bidding' => array('id' => 1, 'bid' => 'One', 'name' => 'Bid 1'),
			),
			array(
				'BiddingMessage' => array('bidding' => 'Three', 'name' => 'Message 3'),
				'Bidding' => array('id' => 3, 'bid' => 'Three', 'name' => 'Bid 3'),
			),
			array(
				'BiddingMessage' => array('bidding' => 'Four', 'name' => 'Message 4'),
				'Bidding' => array('id' => '', 'bid' => '', 'name' => ''),
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test deleteLinks with Multiple habtm associations
 *
 * @return void
 */
	public function testDeleteLinksWithMultipleHabtmAssociations() {
		$this->loadFixtures('JoinA', 'JoinB', 'JoinC', 'JoinAB', 'JoinAC');
		$JoinA = new JoinA();

		//create two new join records to expose the issue.
		$JoinA->JoinAsJoinC->create(array(
			'join_a_id' => 1,
			'join_c_id' => 2,
		));
		$JoinA->JoinAsJoinC->save();
		$JoinA->JoinAsJoinB->create(array(
			'join_a_id' => 1,
			'join_b_id' => 2,
		));
		$JoinA->JoinAsJoinB->save();

		$result = $JoinA->delete(1);
		$this->assertTrue($result, 'Delete failed %s');

		$joinedBs = $JoinA->JoinAsJoinB->find('count', array(
			'conditions' => array('JoinAsJoinB.join_a_id' => 1)
		));
		$this->assertEquals($joinedBs, 0, 'JoinA/JoinB link records left over. %s');

		$joinedBs = $JoinA->JoinAsJoinC->find('count', array(
			'conditions' => array('JoinAsJoinC.join_a_id' => 1)
		));
		$this->assertEquals($joinedBs, 0, 'JoinA/JoinC link records left over. %s');
	}

/**
 * testHabtmDeleteLinksWhenNoPrimaryKeyInJoinTable method
 *
 * @return void
 */
	public function testHabtmDeleteLinksWhenNoPrimaryKeyInJoinTable() {

		$this->loadFixtures('Apple', 'Device', 'ThePaperMonkies');
		$ThePaper = new ThePaper();
		$ThePaper->id = 1;
		$ThePaper->save(array('Monkey' => array(2, 3)));

		$result = $ThePaper->findById(1);
		$expected = array(
			array(
				'id' => '2',
				'device_type_id' => '1',
				'name' => 'Device 2',
				'typ' => '1'
			),
			array(
				'id' => '3',
				'device_type_id' => '1',
				'name' => 'Device 3',
				'typ' => '2'
		));
		$this->assertEquals($result['Monkey'], $expected);

		$ThePaper = new ThePaper();
		$ThePaper->id = 2;
		$ThePaper->save(array('Monkey' => array(2, 3)));

		$result = $ThePaper->findById(2);
		$expected = array(
			array(
				'id' => '2',
				'device_type_id' => '1',
				'name' => 'Device 2',
				'typ' => '1'
			),
			array(
				'id' => '3',
				'device_type_id' => '1',
				'name' => 'Device 3',
				'typ' => '2'
		));
		$this->assertEquals($result['Monkey'], $expected);

		$ThePaper->delete(1);
		$result = $ThePaper->findById(2);
		$expected = array(
			array(
				'id' => '2',
				'device_type_id' => '1',
				'name' => 'Device 2',
				'typ' => '1'
			),
			array(
				'id' => '3',
				'device_type_id' => '1',
				'name' => 'Device 3',
				'typ' => '2'
		));
		$this->assertEquals($result['Monkey'], $expected);
	}

/**
 * test that beforeDelete returning false can abort deletion.
 *
 * @return void
 */
	public function testBeforeDeleteDeleteAbortion() {
		$this->loadFixtures('Post');
		$Model = new CallbackPostTestModel();
		$Model->beforeDeleteReturn = false;

		$result = $Model->delete(1);
		$this->assertFalse($result);

		$exists = $Model->findById(1);
		$this->assertTrue(is_array($exists));
	}

/**
 * test for a habtm deletion error that occurs in postgres but should not.
 * And should not occur in any dbo.
 *
 * @return void
 */
	public function testDeleteHabtmPostgresFailure() {
		$this->loadFixtures('Article', 'Tag', 'ArticlesTag');

		$Article = ClassRegistry::init('Article');
		$Article->hasAndBelongsToMany['Tag']['unique'] = true;

		$Tag = ClassRegistry::init('Tag');
		$Tag->bindModel(array('hasAndBelongsToMany' => array(
			'Article' => array(
				'className' => 'Article',
				'unique' => true
			)
		)), true);

		// Article 1 should have Tag.1 and Tag.2
	    $before = $Article->find("all", array(
			"conditions" => array("Article.id" => 1),
		));
		$this->assertEquals(count($before[0]['Tag']), 2, 'Tag count for Article.id = 1 is incorrect, should be 2 %s');

		// From now on, Tag #1 is only associated with Post #1
		$submitted_data = array(
			"Tag" => array("id" => 1, 'tag' => 'tag1'),
			"Article" => array(
				"Article" => array(1)
			)
		);
		$Tag->save($submitted_data);

	    // One more submission (The other way around) to make sure the reverse save looks good.
	    $submitted_data = array(
			"Article" => array("id" => 2, 'title' => 'second article'),
			"Tag" => array(
				"Tag" => array(2, 3)
			)
		);
	    // ERROR:
	    // Postgresql: DELETE FROM "articles_tags" WHERE tag_id IN ('1', '3')
	    // MySQL: DELETE `ArticlesTag` FROM `articles_tags` AS `ArticlesTag` WHERE `ArticlesTag`.`article_id` = 2 AND `ArticlesTag`.`tag_id` IN (1, 3)
	    $Article->save($submitted_data);

		// Want to make sure Article #1 has Tag #1 and Tag #2 still.
		$after = $Article->find("all", array(
			"conditions" => array("Article.id" => 1),
		));

		// Removing Article #2 from Tag #1 is all that should have happened.
		$this->assertEquals(count($before[0]["Tag"]), count($after[0]["Tag"]));
	}

/**
 * test that deleting records inside the beforeDelete doesn't truncate the table.
 *
 * @return void
 */
	public function testBeforeDeleteWipingTable() {
		$this->loadFixtures('Comment');

		$Comment = new BeforeDeleteComment();
		// Delete 3 records.
		$Comment->delete(4);
		$result = $Comment->find('count');

		$this->assertTrue($result > 1, 'Comments are all gone.');
		$Comment->create(array(
			'article_id' => 1,
			'user_id' => 2,
			'comment' => 'new record',
			'published' => 'Y'
		));
		$Comment->save();

		$Comment->delete(5);
		$result = $Comment->find('count');

		$this->assertTrue($result > 1, 'Comments are all gone.');
	}

/**
 * test that deleting the same record from the beforeDelete and the delete doesn't truncate the table.
 *
 * @return void
 */
	public function testBeforeDeleteWipingTableWithDuplicateDelete() {
		$this->loadFixtures('Comment');

		$Comment = new BeforeDeleteComment();
		$Comment->delete(1);

		$result = $Comment->find('count');
		$this->assertTrue($result > 1, 'Comments are all gone.');
	}
}
