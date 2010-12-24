<?php
/**
 * ClassRegistryTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ClassRegistry');

/**
 * ClassRegisterModel class
 *
 * @package       cake.tests.cases.libs
 */
class ClassRegisterModel extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;
}

/**
 * RegisterArticle class
 *
 * @package       cake.tests.cases.libs
 */
class RegisterArticle extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticle'
 * @access public
 */
	public $name = 'RegisterArticle';
}

/**
 * RegisterArticleFeatured class
 *
 * @package       cake.tests.cases.libs
 */
class RegisterArticleFeatured extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticleFeatured'
 * @access public
 */
	public $name = 'RegisterArticleFeatured';
}

/**
 * RegisterArticleTag class
 *
 * @package       cake.tests.cases.libs
 */
class RegisterArticleTag extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticleTag'
 * @access public
 */
	public $name = 'RegisterArticleTag';
}

/**
 * RegistryPluginAppModel class
 *
 * @package       cake.tests.cases.libs
 */
class RegistryPluginAppModel extends ClassRegisterModel {

/**
 * tablePrefix property
 *
 * @var string 'something_'
 * @access public
 */
	public $tablePrefix = 'something_';
}

/**
 * TestRegistryPluginModel class
 *
 * @package       cake.tests.cases.libs
 */
class TestRegistryPluginModel extends RegistryPluginAppModel {

/**
 * name property
 *
 * @var string 'TestRegistryPluginModel'
 * @access public
 */
	public $name = 'TestRegistryPluginModel';
}

/**
 * RegisterCategory class
 *
 * @package       cake.tests.cases.libs
 */
class RegisterCategory extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterCategory'
 * @access public
 */
	public $name = 'RegisterCategory';
}

/**
 * ClassRegistryTest class
 *
 * @package       cake.tests.cases.libs
 */
class ClassRegistryTest extends CakeTestCase {

/**
 * testAddModel method
 *
 * @access public
 * @return void
 */
	function testAddModel() {

		$Tag = ClassRegistry::init('RegisterArticleTag');
		$this->assertTrue(is_a($Tag, 'RegisterArticleTag'));

		$TagCopy = ClassRegistry::isKeySet('RegisterArticleTag');
		$this->assertTrue($TagCopy);

		$Tag->name = 'SomeNewName';

		$TagCopy = ClassRegistry::getObject('RegisterArticleTag');

		$this->assertTrue(is_a($TagCopy, 'RegisterArticleTag'));
		$this->assertSame($Tag, $TagCopy);

		$NewTag = ClassRegistry::init(array('class' => 'RegisterArticleTag', 'alias' => 'NewTag'));
		$this->assertTrue(is_a($Tag, 'RegisterArticleTag'));


		$NewTagCopy = ClassRegistry::init(array('class' => 'RegisterArticleTag', 'alias' => 'NewTag'));

		$this->assertNotSame($Tag, $NewTag);
		$this->assertSame($NewTag, $NewTagCopy);

		$NewTag->name = 'SomeOtherName';
		$this->assertNotSame($Tag, $NewTag);
		$this->assertSame($NewTag, $NewTagCopy);

		$Tag->name = 'SomeOtherName';
		$this->assertNotSame($Tag, $NewTag);

		$this->assertTrue($TagCopy->name === 'SomeOtherName');

		$User = ClassRegistry::init(array('class' => 'RegisterUser', 'alias' => 'User', 'table' => false));
		$this->assertTrue(is_a($User, 'AppModel'));

		$UserCopy = ClassRegistry::init(array('class' => 'RegisterUser', 'alias' => 'User', 'table' => false));
		$this->assertTrue(is_a($UserCopy, 'AppModel'));
		$this->assertEquals($User, $UserCopy);

		$Category = ClassRegistry::init(array('class' => 'RegisterCategory'));
		$this->assertTrue(is_a($Category, 'RegisterCategory'));

		$ParentCategory = ClassRegistry::init(array('class' => 'RegisterCategory', 'alias' => 'ParentCategory'));
		$this->assertTrue(is_a($ParentCategory, 'RegisterCategory'));
		$this->assertNotSame($Category, $ParentCategory);

		$this->assertNotEqual($Category->alias, $ParentCategory->alias);
		$this->assertEqual('RegisterCategory', $Category->alias);
		$this->assertEqual('ParentCategory', $ParentCategory->alias);
	}

/**
 * testClassRegistryFlush method
 *
 * @access public
 * @return void
 */
	function testClassRegistryFlush() {
		$Tag = ClassRegistry::init('RegisterArticleTag');

		$ArticleTag = ClassRegistry::getObject('RegisterArticleTag');
		$this->assertTrue(is_a($ArticleTag, 'RegisterArticleTag'));
		ClassRegistry::flush();

		$NoArticleTag = ClassRegistry::isKeySet('RegisterArticleTag');
		$this->assertFalse($NoArticleTag);
		$this->assertTrue(is_a($ArticleTag, 'RegisterArticleTag'));
	}

/**
 * testAddMultipleModels method
 *
 * @access public
 * @return void
 */
	function testAddMultipleModels() {
		$Article = ClassRegistry::isKeySet('Article');
		$this->assertFalse($Article);

		$Featured = ClassRegistry::isKeySet('Featured');
		$this->assertFalse($Featured);

		$Tag = ClassRegistry::isKeySet('Tag');
		$this->assertFalse($Tag);

		$models = array(array('class' => 'RegisterArticle', 'alias' => 'Article'),
				array('class' => 'RegisterArticleFeatured', 'alias' => 'Featured'),
				array('class' => 'RegisterArticleTag', 'alias' => 'Tag'));

		$added = ClassRegistry::init($models);
		$this->assertTrue($added);

		$Article = ClassRegistry::isKeySet('Article');
		$this->assertTrue($Article);

		$Featured = ClassRegistry::isKeySet('Featured');
		$this->assertTrue($Featured);

		$Tag = ClassRegistry::isKeySet('Tag');
		$this->assertTrue($Tag);

		$Article = ClassRegistry::getObject('Article');
		$this->assertTrue(is_a($Article, 'RegisterArticle'));

		$Featured = ClassRegistry::getObject('Featured');
		$this->assertTrue(is_a($Featured, 'RegisterArticleFeatured'));

		$Tag = ClassRegistry::getObject('Tag');
		$this->assertTrue(is_a($Tag, 'RegisterArticleTag'));
	}

/**
 * testPluginAppModel method
 *
 * @access public
 * @return void
 */
	function testPluginAppModel() {
		$TestRegistryPluginModel = ClassRegistry::isKeySet('TestRegistryPluginModel');
		$this->assertFalse($TestRegistryPluginModel);

		$TestRegistryPluginModel = ClassRegistry::init('RegistryPlugin.TestRegistryPluginModel');
		$this->assertTrue(is_a($TestRegistryPluginModel, 'TestRegistryPluginModel'));

		$this->assertEqual($TestRegistryPluginModel->tablePrefix, 'something_');

		$PluginUser = ClassRegistry::init(array('class' => 'RegistryPlugin.RegisterUser', 'alias' => 'RegistryPluginUser', 'table' => false));
		$this->assertTrue(is_a($PluginUser, 'RegistryPluginAppModel'));

		$PluginUserCopy = ClassRegistry::getObject('RegistryPluginUser');
		$this->assertTrue(is_a($PluginUserCopy, 'RegistryPluginAppModel'));
		$this->assertSame($PluginUser, $PluginUserCopy);
	}
}
