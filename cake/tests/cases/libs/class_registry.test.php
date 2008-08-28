<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'ClassRegistry');
/**
 * ClassRegisterModel class
 *
 * @package              cake.tests
 * @subpackage           cake.tests.cases.libs
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
 * @package              cake.tests
 * @subpackage           cake.tests.cases.libs
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
 * @package              cake.tests
 * @subpackage           cake.tests.cases.libs
 */
class RegisterArticleFeatured extends ClassRegisterModel {
/**
 * name property
 *
 * @var string 'RegisterArticlFeatured'
 * @access public
 */
	var $name = 'RegisterArticlFeatured';
}
/**
 * RegisterArticleTag class
 *
 * @package              cake.tests
 * @subpackage           cake.tests.cases.libs
 */
class RegisterArticleTag extends ClassRegisterModel {
/**
 * name property
 *
 * @var string 'RegisterArticlTag'
 * @access public
 */
	var $name = 'RegisterArticlTag';
}
/**
 * RegistryPluginAppModel class
 *
 * @package              cake.tests
 * @subpackage           cake.tests.cases.libs
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
 * @package              cake.tests
 * @subpackage           cake.tests.cases.libs
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
 * ClassRegistryTest class
 *
 * @package              cake.tests
 * @subpackage           cake.tests.cases.libs
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

		$NewTag = ClassRegistry::init(array('class' => 'RegisterArticleTag', 'alias' => 'NewTag'));
		$this->assertTrue(is_a($Tag, 'RegisterArticleTag'));

		$this->assertNotIdentical($Tag, $NewTag);

		$NewTag->name = 'SomeOtherName';
		$this->assertNotIdentical($Tag, $NewTag);

		$Tag->name = 'SomeOtherName';
		$this->assertNotIdentical($Tag, $NewTag);

		$this->assertTrue($TagCopy->name === 'SomeOtherName');
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
	}
}
?>