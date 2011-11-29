<?php
/**
 * ClassRegistryTest file
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
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ClassRegistry', 'Utility');

/**
 * ClassRegisterModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class ClassRegisterModel extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;
}

/**
 * RegisterArticle class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticle extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticle'
 */
	public $name = 'RegisterArticle';
}

/**
 * RegisterArticleFeatured class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticleFeatured extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticleFeatured'
 */
	public $name = 'RegisterArticleFeatured';
}

/**
 * RegisterArticleTag class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticleTag extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticleTag'
 */
	public $name = 'RegisterArticleTag';
}

/**
 * RegistryPluginAppModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegistryPluginAppModel extends ClassRegisterModel {

/**
 * tablePrefix property
 *
 * @var string 'something_'
 */
	public $tablePrefix = 'something_';
}

/**
 * TestRegistryPluginModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class TestRegistryPluginModel extends RegistryPluginAppModel {

/**
 * name property
 *
 * @var string 'TestRegistryPluginModel'
 */
	public $name = 'TestRegistryPluginModel';
}

/**
 * RegisterCategory class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterCategory extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterCategory'
 */
	public $name = 'RegisterCategory';
}

/**
 * ClassRegistryTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class ClassRegistryTest extends CakeTestCase {

/**
 * testAddModel method
 *
 * @return void
 */
	public function testAddModel() {

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

		$this->assertNotEquals($Category->alias, $ParentCategory->alias);
		$this->assertEquals('RegisterCategory', $Category->alias);
		$this->assertEquals('ParentCategory', $ParentCategory->alias);
	}

/**
 * testClassRegistryFlush method
 *
 * @return void
 */
	public function testClassRegistryFlush() {
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
 * @return void
 */
	public function testAddMultipleModels() {
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
 * @return void
 */
	public function testPluginAppModel() {
		$TestRegistryPluginModel = ClassRegistry::isKeySet('TestRegistryPluginModel');
		$this->assertFalse($TestRegistryPluginModel);

		//Faking a plugin
		CakePlugin::load('RegistryPlugin', array('path' => '/fake/path'));
		$TestRegistryPluginModel = ClassRegistry::init('RegistryPlugin.TestRegistryPluginModel');
		$this->assertTrue(is_a($TestRegistryPluginModel, 'TestRegistryPluginModel'));

		$this->assertEquals($TestRegistryPluginModel->tablePrefix, 'something_');

		$PluginUser = ClassRegistry::init(array('class' => 'RegistryPlugin.RegisterUser', 'alias' => 'RegistryPluginUser', 'table' => false));
		$this->assertTrue(is_a($PluginUser, 'RegistryPluginAppModel'));

		$PluginUserCopy = ClassRegistry::getObject('RegistryPluginUser');
		$this->assertTrue(is_a($PluginUserCopy, 'RegistryPluginAppModel'));
		$this->assertSame($PluginUser, $PluginUserCopy);
		CakePlugin::unload();
	}

/**
 * Tests that passing the string parameter to init() will return false if the model does not exists
 *
 */
	public function testInitStrict() {
		$this->assertFalse(ClassRegistry::init('NonExistent', true));
	}
}
