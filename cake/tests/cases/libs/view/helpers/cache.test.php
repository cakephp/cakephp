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
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Core', array('Controller', 'Model', 'View'));
App::import('Helper', 'Cache');

/**
 * Test Cache Helper
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class TestCacheHelper extends CacheHelper {

}

/**
 * Test Cache Helper
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class CacheTestController extends Controller {
	var $helpers = array('Html', 'Cache');
	
	function cache_parsing() {
		$this->viewPath = 'posts';
		$this->layout = 'cache_layout';
		$this->set('variable', 'variableValue');
		$this->set('superman', 'clark kent');
	}
}

/**
 * Cache Helper Test Case
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class CacheHelperTest extends CakeTestCase {
/**
 * setUp method
 * 
 * @access public
 * @return void
 */
	function setUp() {
		$this->Controller = new CacheTestController();
		$this->Cache = new TestCacheHelper();
		Configure::write('Cache.check', true);
		Configure::write('Cache.disable', false);
	}
	
/**
 * Start Case - switch view paths
 *
 * @access public
 * @return void
 */	
	function startCase() {
		$this->_viewPaths = Configure::read('viewPaths');
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS));
	}
	
/**
 * test cache parsing with no cake:nocache tags in view file.
 *
 */
	function testLayoutCacheParsingNoTagsInView() {
		$this->Controller->cache_parsing();
		$this->Controller->cacheAction = 21600;
		$this->Controller->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';
		
		$View = new View($this->Controller);
		$result = $View->render('index');	
		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);
		
		$filename = CACHE . 'views' . DS . 'cacheTest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		
		$contents = file_get_contents($filename);
		$this->assertPattern('/php echo \$variable/', $contents);
		$this->assertPattern('/php echo microtime()/', $contents);
		$this->assertPattern('/clark kent/', $result);
		
		@unlink($filename);
	}
	
/**
 * Test cache parsing with cake:nocache tags in view file.
 *
 */
	function testLayoutCacheParsingWithTagsInView() {
		$this->Controller->cache_parsing();
		$this->Controller->cacheAction = 21600;
		$this->Controller->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';
		
		$View = new View($this->Controller);
		$result = $View->render('test_nocache_tags');	
		$this->assertNoPattern('/cake:nocache/', $result);
		$this->assertNoPattern('/php echo/', $result);

		$filename = CACHE . 'views' . DS . 'cacheTest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		
		$contents = file_get_contents($filename);
		$this->assertPattern('/if \(is_writable\(TMP\)\)\:/', $contents);
		$this->assertPattern('/php echo \$variable/', $contents);
		$this->assertPattern('/php echo microtime()/', $contents);
		
		@unlink($filename);
	}
/**
 * End Case - restore view Paths
 *
 * @access public
 * @return void
 */
	function endCase() {
		Configure::write('viewPaths', $this->_viewPaths);
	}
/**
 * tearDown method
 * 
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Cache);
	}
}

?>