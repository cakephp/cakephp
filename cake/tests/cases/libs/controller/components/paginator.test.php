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
 * @package       cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'Controller', false);
App::import('Core', array('CakeRequest', 'CakeResponse'));

/**
 * PaginatorTestController class
 *
 * @package       cake.tests.cases.libs.controller.components
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
 * PaginatorControllerPost class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class PaginatorControllerPost extends CakeTestModel {

/**
 * name property
 *
 * @var string 'PaginatorControllerPost'
 * @access public
 */
	public $name = 'PaginatorControllerPost';

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
	function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
		if ($conditions == 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey .' > ' => '1');
			$options = Set::merge($fields, compact('conditions'));
			return parent::find('all', $options);
		}
		return parent::find($conditions, $fields);
	}
}

/**
 * ControllerPaginateModel class
 *
 * @package       cake.tests.cases.libs.controller.components
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
 * PaginatorControllerCommentclass
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class PaginatorControllerComment extends CakeTestModel {

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
 * @var string 'PaginatorControllerComment'
 * @access public
 */
	public $alias = 'PaginatorControllerComment';
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
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerComment'), '{n}.PaginatorControllerComment.id');
		$this->assertEqual($results, array(1, 2, 3, 4, 5, 6));

		$Controller->modelClass = null;

		$Controller->uses[0] = 'Plugin.PaginatorControllerPost';
		$results = Set::extract($Controller->Paginator->paginate(), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('page' => '-1');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('sort' => 'PaginatorControllerPost.id', 'direction' => 'asc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('sort' => 'PaginatorControllerPost.id', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(3, 2, 1));

		$Controller->passedArgs = array('sort' => 'id', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(3, 2, 1));

		$Controller->passedArgs = array('sort' => 'NotExisting.field', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1, 'Invalid field in query %s');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('sort' => 'PaginatorControllerPost.author_id', 'direction' => 'allYourBase');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->PaginatorControllerPost->lastQuery['order'][0], array('PaginatorControllerPost.author_id' => 'asc'));
		$this->assertEqual($results, array(1, 3, 2));

		$Controller->passedArgs = array('page' => '1 " onclick="alert(\'xss\');">');
		$Controller->Paginator->settings = array('limit' => 1);
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['page'], 1, 'XSS exploit opened %s');
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['options']['page'], 1, 'XSS exploit opened %s');

		$Controller->passedArgs = array();
		$Controller->Paginator->settings = array('limit' => 0);
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['nextPage'], true);

		$Controller->passedArgs = array();
		$Controller->Paginator->settings = array('limit' => 'garbage!');
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['nextPage'], true);

		$Controller->passedArgs = array();
		$Controller->Paginator->settings = array('limit' => '-1');
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['nextPage'], true);
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

		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->passedArgs = array('page' => '-1', 'contain' => array('PaginatorControllerComment'));
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(1, 2, 3));
		$this->assertTrue(!isset($Controller->PaginatorControllerPost->lastQuery['contain']));

		$Controller->passedArgs = array('page' => '-1');
		$Controller->Paginator->settings = array('PaginatorControllerPost' => array('contain' => array('PaginatorControllerComment')));
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(1, 2, 3));
		$this->assertTrue(isset($Controller->PaginatorControllerPost->lastQuery['contain']));

		$Controller->Paginator->settings = array('PaginatorControllerPost' => array('popular', 'fields' => array('id', 'title')));
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(2, 3));
		$this->assertEqual($Controller->PaginatorControllerPost->lastQuery['conditions'], array('PaginatorControllerPost.id > ' => '1'));

		$Controller->passedArgs = array('limit' => 12);
		$Controller->Paginator->settings = array('limit' => 30);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$paging = $Controller->params['paging']['PaginatorControllerPost'];

		$this->assertEqual($Controller->PaginatorControllerPost->lastQuery['limit'], 12);
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
		$Controller->uses = array('PaginatorControllerPost');
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
		$Controller->Paginator->paginate('PaginatorControllerPost',$conditions);

		$expected = array(
			'fields' => array(),
			'order' => '',
			'limit' => 5,
			'page' => 1,
			'recursive' => -1,
			'conditions' => array()
		);
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['options'],$expected);
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
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->Paginator->settings = array('PaginatorControllerPost' => array('popular', 'fields' => array('id', 'title')));
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');

		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(2, 3));
		$this->assertEqual($Controller->PaginatorControllerPost->lastQuery['conditions'], array('PaginatorControllerPost.id > ' => '1'));
		$this->assertFalse(isset($Controller->params['paging']['PaginatorControllerPost']['defaults'][0]));
		$this->assertFalse(isset($Controller->params['paging']['PaginatorControllerPost']['options'][0]));
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
		$Controller->modelClass = 'PaginatorControllerPost';
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array('order' => 'PaginatorControllerPost.id DESC');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['defaults']['order'], 'PaginatorControllerPost.id DESC');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['options']['order'], 'PaginatorControllerPost.id DESC');
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
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->virtualFields = array(
			'offset_test' => 'PaginatorControllerPost.id + 1'
		);

		$Controller->Paginator->settings = array(
			'fields' => array('id', 'title', 'offset_test'),
			'order' => array('offset_test' => 'DESC')
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.offset_test'), array(4, 3, 2));

		$Controller->passedArgs = array('sort' => 'offset_test', 'direction' => 'asc');
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.offset_test'), array(2, 3, 4));
	}

/**
 * Tests for missing models
 *
 * @expectedException MissingModelException
 */
	function testPaginateMissingModel() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new PaginatorTestController($request);
		$Controller->constructClasses();
		$Controller->Paginator->paginate('MissingModel');		
	}
}