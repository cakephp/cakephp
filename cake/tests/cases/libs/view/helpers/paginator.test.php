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
	require_once LIBS.'../app_helper.php';
	require_once LIBS.DS.'view'.DS.'helper.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'html.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'ajax.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'javascript.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'form.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'paginator.php';
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class PaginatorTest extends UnitTestCase {

	function setUp() {
		$this->paginator = new PaginatorHelper();
		$this->paginator->params['paging'] = array(
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
		$this->paginator->Html =& new HtmlHelper();
		$this->paginator->Ajax =& new AjaxHelper();
		$this->paginator->Ajax->Html =& new HtmlHelper();
		$this->paginator->Ajax->Javascript =& new JavascriptHelper();
		$this->paginator->Ajax->Form =& new FormHelper();

	}

	function testHasPrevious() {
		$this->assertIdentical($this->paginator->hasPrev(), false);
		$this->paginator->params['paging']['Article']['prevPage'] = true;
		$this->assertIdentical($this->paginator->hasPrev(), true);
		$this->paginator->params['paging']['Article']['prevPage'] = false;
	}

	function testHasNext() {
		$this->assertIdentical($this->paginator->hasNext(), true);
		$this->paginator->params['paging']['Article']['nextPage'] = false;
		$this->assertIdentical($this->paginator->hasNext(), false);
		$this->paginator->params['paging']['Article']['nextPage'] = true;
	}

	function testSortLinks() {
		Router::reload();
		Router::setRequestInfo(array(
			array ('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array('url' => 'accounts/', 'mod_rewrite' => 'true'), 'bare' => 0, 'webservices' => null),
			array ('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)
		));
		$this->paginator->options(array('url' => array('param')));
		$result = $this->paginator->sort('title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc"\s*>Title<\/a>$/', $result);

		$result = $this->paginator->sort('date');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:date\/direction:desc"\s*>Date<\/a>$/', $result);

		$result = $this->paginator->numbers(array('modulus'=> '2', 'url'=> array('controller'=>'projects', 'action'=>'sort'),'update'=>'list'));
		$this->assertPattern('/\/projects\/sort\/page:2/', $result);
		$this->assertPattern('/<script type="text\/javascript">Event.observe/', $result);
	}

	function tearDown() {
		unset($this->paginator);
	}
}

?>