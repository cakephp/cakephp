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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE.'app_helper.php';
uses('view'.DS.'helper', 'view'.DS.'helpers'.DS.'html', 'view'.DS.'helpers'.DS.'form',
	'view'.DS.'helpers'.DS.'ajax', 'view'.DS.'helpers'.DS.'javascript', 'view'.DS.'helpers'.DS.'paginator');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class PaginatorTest extends UnitTestCase {

	function setUp() {
		$this->Paginator = new PaginatorHelper();
		$this->Paginator->params['paging'] = array(
			'Article' => array(
				'current' => 9,
				'count' => 62,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 7,
				'defaults' => array(
					'order' => 'Article.date ASC',
					'limit' => 9,
					'conditions' => array()
                ),
				'options' => array(
					'order' => 'Article.date ASC',
					'limit' => 9,
					'page' => 1,
					'conditions' => array()
				)
			)
		);
		$this->Paginator->Html =& new HtmlHelper();
		$this->Paginator->Ajax =& new AjaxHelper();
		$this->Paginator->Ajax->Html =& new HtmlHelper();
		$this->Paginator->Ajax->Javascript =& new JavascriptHelper();
		$this->Paginator->Ajax->Form =& new FormHelper();

	}

	function testHasPrevious() {
		$this->assertIdentical($this->Paginator->hasPrev(), false);
		$this->Paginator->params['paging']['Article']['prevPage'] = true;
		$this->assertIdentical($this->Paginator->hasPrev(), true);
		$this->Paginator->params['paging']['Article']['prevPage'] = false;
	}

	function testHasNext() {
		$this->assertIdentical($this->Paginator->hasNext(), true);
		$this->Paginator->params['paging']['Article']['nextPage'] = false;
		$this->assertIdentical($this->Paginator->hasNext(), false);
		$this->Paginator->params['paging']['Article']['nextPage'] = true;
	}

	function testDisabledLink() {
		$this->Paginator->params['paging']['Article']['nextPage'] = false;
		$this->Paginator->params['paging']['Article']['page'] = 1;
		$result = $this->Paginator->next('Next', array(), true);
		$expected = '<div>Next</div>';
		$this->assertEqual($result, $expected);
	}

	function testSortLinks() {
		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array('url' => 'accounts/', 'mod_rewrite' => 'true'), 'bare' => 0),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/', 'passedArgs' => array())
		));
		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc"\s*>Title<\/a>$/', $result);

		$result = $this->Paginator->sort('date');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:date\/direction:desc"\s*>Date<\/a>$/', $result);

		$result = $this->Paginator->numbers(array('modulus'=> '2', 'url'=> array('controller'=>'projects', 'action'=>'sort'),'update'=>'list'));
		$this->assertPattern('/\/projects\/sort\/page:2/', $result);
		$this->assertPattern('/<script type="text\/javascript">Event.observe/', $result);
	}

	function testUrlGeneration() {
		$result = $this->Paginator->sort('controller');
		$this->assertPattern('/\/page:1\//', $result);
		$this->assertPattern('/\/sort:controller\//', $result);
	}

	function tearDown() {
		unset($this->Paginator);
	}
}

?>