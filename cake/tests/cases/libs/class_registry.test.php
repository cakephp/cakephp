<?php
/**
 * ClassRegistryTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'ClassRegistry');

/**
 * ClassRegisterModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ClassRegisterModel extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
}

/**
 * RegisterArticle class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RegisterArticle extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticle'
 * @access public
 */
	var $name = 'RegisterArticle';
}

/**
 * RegisterArticleFeatured class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RegisterArticleFeatured extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticleFeatured'
 * @access public
 */
	var $name = 'RegisterArticleFeatured';
}

/**
 * RegisterArticleTag class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RegisterArticleTag extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterArticleTag'
 * @access public
 */
	var $name = 'RegisterArticleTag';
}

/**
 * RegistryPluginAppModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RegistryPluginAppModel extends ClassRegisterModel {

/**
 * tablePrefix property
 *
 * @var string 'something_'
 * @access public
 */
	var $tablePrefix = 'something_';
}

/**
 * TestRegistryPluginModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class TestRegistryPluginModel extends RegistryPluginAppModel {

/**
 * name property
 *
 * @var string 'TestRegistryPluginModel'
 * @access public
 */
	var $name = 'TestRegistryPluginModel';
}

/**
 * RegisterCategory class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RegisterCategory extends ClassRegisterModel {

/**
 * name property
 *
 * @var string 'RegisterCategory'
 * @access public
 */
	var $name = 'RegisterCategory';
}

/**
 * ClassRegistryTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ClassRegistryTest extends CakeTestCase {

/**
 * testAddModel method
 *
 * @access public
 * @return void
 */
	function testAddModel() {
		if (PHP5) {
			$Tag = ClassRegistry::init('RegisterArticleTag');
		} else {
			$Tag =& ClassRegistry::init('RegisterArticleTag');
		}
		$this->assertTrue(is_a($Tag, 'RegisterArticleTag'));

		$TagCopy = ClassRegistry::isKeySet('RegisterArticleTag');
		$this->assertTrue($TagCopy);

		$Tag->name = 'SomeNewName';

		if (PHP5) {
			$TagCopy = ClassRegistry::getObject('RegisterArticleTag');
		} else {
			$TagCopy =& ClassRegistry::getObject('RegisterArticleTag');
		}

		$this->assertTrue(is_a($TagCopy, 'RegisterArticleTag'));
		$this->assertIdentical($Tag, $TagCopy);

		if (PHP5) {
			$NewTag = ClassRegistry::init(array('class' => 'RegisterArticleTag', 'alias' => 'NewTag'));
		} else {
			$NewTag =& ClassRegistry::init(array('class' => 'RegisterArticleTag', 'alias' => 'NewTag'));
		}
		$this->assertTrue(is_a($Tag, 'RegisterArticleTag'));

		if (PHP5) {
			$NewTagCopy = ClassRegistry::init(array('class' => 'RegisterArticleTag', 'alias' => 'NewTag'));
		} else {
			$NewTagCopy =& ClassRegistry::init(array('class' => 'RegisterArticleTag', 'alias' => 'NewTag'));
		}

		$this->assertNotIdentical($Tag, $NewTag);
		$this->assertIdentical($NewTag, $NewTagCopy);

		$NewTag->name = 'SomeOtherName';
		$this->assertNotIdentical($Tag, $NewTag);
		$this->assertIdentical($NewTag, $NewTagCopy);

		$Tag->name = 'SomeOtherName';
		$this->assertNotIdentical($Tag, $NewTag);

		$this->assertTrue($TagCopy->name === 'SomeOtherName');

		if (PHP5) {
			$User = ClassRegistry::init(array('class' => 'RegisterUser', 'alias' => 'User', 'table' => false));
		} else {
			$User =& ClassRegistry::init(array('class' => 'RegisterUser', 'alias' => 'User', 'table' => false));
		}
		$this->assertTrue(is_a($User, 'AppModel'));

		if (PHP5) {
			$UserCopy = ClassRegistry::init(array('class' => 'RegisterUser', 'alias' => 'User', 'table' => false));
		} else {
			$UserCopy =& ClassRegistry::init(array('class' => 'RegisterUser', 'alias' => 'User', 'table' => false));
		}
		$this->assertTrue(is_a($UserCopy, 'AppModel'));
		$this->assertIdentical($User, $UserCopy);

		if (PHP5) {
			$Category = ClassRegistry::init(array('class' => 'RegisterCategory'));
		} else {
			$Category =& ClassRegistry::init(array('class' => 'RegisterCategory'));
		}
		$this->assertTrue(is_a($Category, 'RegisterCategory'));

		if (PHP5) {
			$ParentCategory = ClassRegistry::init(array('class' => 'RegisterCategory', 'alias' => 'ParentCategory'));
		} else {
			$ParentCategory =& ClassRegistry::init(array('class' => 'RegisterCategory', 'alias' => 'ParentCategory'));
		}
		$this->assertTrue(is_a($ParentCategory, 'RegisterCategory'));
		$this->assertNotIdentical($Category, $ParentCategory);

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

		if (PHP5) {
			$PluginUser = ClassRegistry::init(array('class' => 'RegistryPlugin.RegisterUser', 'alias' => 'RegistryPluginUser', 'table' => false));
		} else {
			$PluginUser =& ClassRegistry::init(array('class' => 'RegistryPlugin.RegisterUser', 'alias' => 'RegistryPluginUser', 'table' => false));
		}
		$this->assertTrue(is_a($PluginUser, 'RegistryPluginAppModel'));

		if (PHP5) {
			$PluginUserCopy = ClassRegistry::getObject('RegistryPluginUser');
		} else {
			$PluginUserCopy =& ClassRegistry::getObject('RegistryPluginUser');
		}
		$this->assertTrue(is_a($PluginUserCopy, 'RegistryPluginAppModel'));
		$this->assertIdentical($PluginUser, $PluginUserCopy);
	}
}
?>