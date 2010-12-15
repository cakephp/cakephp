<?php
/**
 * PaginatorComponentTest file
 *
 * Series of tests for paginator component.
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
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'Controller', false);
App::import('Core', array('CakeRequest', 'CakeResponse'));

/**
 * PaginatorTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class PaginatorTestController extends Controller {
/**
 * name property
 *
 * @var string 'PaginatorTest'
 * @access public
 */
	public $name = 'PaginatorTest';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	//public $uses = null;

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Paginator');
}

/**
 * ControllerPost class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class ControllerPost extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerPost'
 * @access public
 */
	public $name = 'ControllerPost';

/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	public $useTable = 'posts';

/**
 * invalidFields property
 *
 * @var array
 * @access public
 */
	public $invalidFields = array('name' => 'error_msg');

/**
 * lastQuery property
 *
 * @var mixed null
 * @access public
 */
	public $lastQuery = null;

/**
 * beforeFind method
 *
 * @param mixed $query
 * @access public
 * @return void
 */
	function beforeFind($query) {
		$this->lastQuery = $query;
	}

/**
 * find method
 *
 * @param mixed $type
 * @param array $options
 * @access public
 * @return void
 */
	function find($type, $options = array()) {
		if ($type == 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey .' > ' => '1');
			$options = Set::merge($options, compact('conditions'));
			return parent::find('all', $options);
		}
		return parent::find($type, $options);
	}
}

/**
 * ControllerPaginateModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class ControllerPaginateModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerPaginateModel'
 * @access public
 */
	public $name = 'ControllerPaginateModel';

/**
 * useTable property
 *
 * @var string 'comments'
 * @access public
 */
	public $useTable = 'comments';

/**
 * paginate method
 *
 * @return void
 */
	public function paginate($conditions, $fields, $order, $limit, $page, $recursive, $extra) {
		$this->extra = $extra;
	}

/**
 * paginateCount
 *
 * @access public
 * @return void
 */
	function paginateCount($conditions, $recursive, $extra) {
		$this->extraCount = $extra;
	}
}

/**
 * ControllerComment class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class ControllerComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 * @access public
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 * @access public
 */
	public $useTable = 'comments';

/**
 * alias property
 *
 * @var string 'ControllerComment'
 * @access public
 */
	public $alias = 'ControllerComment';
}

class PaginatorTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.post', 'core.comment');

/**
 * testPaginate method
 *
 * @access public
 * @return void
 */
	function testPaginate() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$results = Set::extract($Controller->Paginator->paginate('ControllerComment'), '{n}.ControllerComment.id');
		$this->assertEqual($results, array(1, 2, 3, 4, 5, 6));

		$Controller->modelClass = null;

		$Controller->uses[0] = 'Plugin.ControllerPost';
		$results = Set::extract($Controller->Paginator->paginate(), '{n}.ControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('page' => '-1');
		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('sort' => 'ControllerPost.id', 'direction' => 'asc');
		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('sort' => 'ControllerPost.id', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual($results, array(3, 2, 1));

		$Controller->passedArgs = array('sort' => 'id', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual($results, array(3, 2, 1));

		$Controller->passedArgs = array('sort' => 'NotExisting.field', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1, 'Invalid field in query %s');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('sort' => 'ControllerPost.author_id', 'direction' => 'allYourBase');
		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->ControllerPost->lastQuery['order'][0], array('ControllerPost.author_id' => 'asc'));
		$this->assertEqual($results, array(1, 3, 2));

		$Controller->passedArgs = array('page' => '1 " onclick="alert(\'xss\');">');
		$Controller->Paginator->settings = array('limit' => 1);
		$Controller->Paginator->paginate('ControllerPost');
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['page'], 1, 'XSS exploit opened %s');
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['options']['page'], 1, 'XSS exploit opened %s');

		$Controller->passedArgs = array();
		$Controller->Paginator->settings = array('limit' => 0);
		$Controller->Paginator->paginate('ControllerPost');
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['nextPage'], true);

		$Controller->passedArgs = array();
		$Controller->Paginator->settings = array('limit' => 'garbage!');
		$Controller->Paginator->paginate('ControllerPost');
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['nextPage'], true);

		$Controller->passedArgs = array();
		$Controller->Paginator->settings = array('limit' => '-1');
		$Controller->Paginator->paginate('ControllerPost');
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['nextPage'], true);
	}

/**
 * testPaginateExtraParams method
 *
 * @access public
 * @return void
 */
	function testPaginateExtraParams() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);

		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->passedArgs = array('page' => '-1', 'contain' => array('ControllerComment'));
		$result = $Controller->Paginator->paginate('ControllerPost');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.id'), array(1, 2, 3));
		$this->assertTrue(!isset($Controller->ControllerPost->lastQuery['contain']));

		$Controller->passedArgs = array('page' => '-1');
		$Controller->Paginator->settings = array('ControllerPost' => array('contain' => array('ControllerComment')));
		$result = $Controller->Paginator->paginate('ControllerPost');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.id'), array(1, 2, 3));
		$this->assertTrue(isset($Controller->ControllerPost->lastQuery['contain']));

		$Controller->Paginator->settings = array('ControllerPost' => array('popular', 'fields' => array('id', 'title')));
		$result = $Controller->Paginator->paginate('ControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.id'), array(2, 3));
		$this->assertEqual($Controller->ControllerPost->lastQuery['conditions'], array('ControllerPost.id > ' => '1'));

		$Controller->passedArgs = array('limit' => 12);
		$Controller->Paginator->settings = array('limit' => 30);
		$result = $Controller->Paginator->paginate('ControllerPost');
		$paging = $Controller->params['paging']['ControllerPost'];

		$this->assertEqual($Controller->ControllerPost->lastQuery['limit'], 12);
		$this->assertEqual($paging['options']['limit'], 12);

		$Controller = new PaginatorTestController($request);
		$Controller->uses = array('ControllerPaginateModel');
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array(
			'ControllerPaginateModel' => array('contain' => array('ControllerPaginateModel'), 'group' => 'Comment.author_id')
		);
		$result = $Controller->Paginator->paginate('ControllerPaginateModel');
		$expected = array('contain' => array('ControllerPaginateModel'), 'group' => 'Comment.author_id');
		$this->assertEqual($Controller->ControllerPaginateModel->extra, $expected);
		$this->assertEqual($Controller->ControllerPaginateModel->extraCount, $expected);

		$Controller->Paginator->settings = array(
			'ControllerPaginateModel' => array('foo', 'contain' => array('ControllerPaginateModel'), 'group' => 'Comment.author_id')
		);
		$Controller->Paginator->paginate('ControllerPaginateModel');
		$expected = array('contain' => array('ControllerPaginateModel'), 'group' => 'Comment.author_id', 'type' => 'foo');
		$this->assertEqual($Controller->ControllerPaginateModel->extra, $expected);
		$this->assertEqual($Controller->ControllerPaginateModel->extraCount, $expected);
	}

/**
 * testPaginatePassedArgs method
 *
 * @return void
 */
	public function testPaginatePassedArgs() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);
		$Controller->uses = array('ControllerPost');
		$Controller->passedArgs[] = array('1', '2', '3');
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->Paginator->settings = array(
			'fields' => array(),
			'order' => '',
			'limit' => 5,
			'page' => 1,
			'recursive' => -1
		);
		$conditions = array();
		$Controller->Paginator->paginate('ControllerPost',$conditions);

		$expected = array(
			'fields' => array(),
			'order' => '',
			'limit' => 5,
			'page' => 1,
			'recursive' => -1,
			'conditions' => array()
		);
		$this->assertEqual($Controller->params['paging']['ControllerPost']['options'],$expected);
	}

/**
 * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
 *
 * @return void
 */
	function testPaginateSpecialType() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->Paginator->settings = array('ControllerPost' => array('popular', 'fields' => array('id', 'title')));
		$result = $Controller->Paginator->paginate('ControllerPost');

		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.id'), array(2, 3));
		$this->assertEqual($Controller->ControllerPost->lastQuery['conditions'], array('ControllerPost.id > ' => '1'));
		$this->assertFalse(isset($Controller->params['paging']['ControllerPost']['defaults'][0]));
		$this->assertFalse(isset($Controller->params['paging']['ControllerPost']['options'][0]));
	}

/**
 * testDefaultPaginateParams method
 *
 * @access public
 * @return void
 */
	function testDefaultPaginateParams() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);
		$Controller->modelClass = 'ControllerPost';
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array('order' => 'ControllerPost.id DESC');		
		$results = Set::extract($Controller->Paginator->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['defaults']['order'], 'ControllerPost.id DESC');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['options']['order'], 'ControllerPost.id DESC');
		$this->assertEqual($results, array(3, 2, 1));
	}

/**
 * test paginate() and virtualField interactions
 *
 * @return void
 */
	function testPaginateOrderVirtualField() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->ControllerPost->virtualFields = array(
			'offset_test' => 'ControllerPost.id + 1'
		);

		$Controller->Paginator->settings = array(
			'fields' => array('id', 'title', 'offset_test'),
			'order' => array('offset_test' => 'DESC')
		);
		$result = $Controller->Paginator->paginate('ControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.offset_test'), array(4, 3, 2));

		$Controller->passedArgs = array('sort' => 'offset_test', 'direction' => 'asc');
		$result = $Controller->Paginator->paginate('ControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.offset_test'), array(2, 3, 4));
	}

	function testPaginateMissingModel() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);
		$Controller->constructClasses();
		$this->expectException('MissingModelException');
		$Controller->Paginator->paginate('MissingModel');		
	}
}