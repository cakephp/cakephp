<?php
/**
 * PaginatorComponentTest file
 *
 * Series of tests for paginator component.
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
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('PaginatorComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * PaginatorTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class PaginatorTestController extends Controller {
/**
 * name property
 *
 * @var string 'PaginatorTest'
 */
	public $name = 'PaginatorTest';

/**
 * uses property
 *
 * @var array
 */
	//public $uses = null;

/**
 * components property
 *
 * @var array
 */
	public $components = array('Paginator');
}

/**
 * PaginatorControllerPost class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class PaginatorControllerPost extends CakeTestModel {

/**
 * name property
 *
 * @var string 'PaginatorControllerPost'
 */
	public $name = 'PaginatorControllerPost';

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'posts';

/**
 * invalidFields property
 *
 * @var array
 */
	public $invalidFields = array('name' => 'error_msg');

/**
 * lastQueries property
 *
 * @var array
 */
	public $lastQueries = array();

/**
 * beforeFind method
 *
 * @param mixed $query
 * @return void
 */
	public function beforeFind($query) {
		array_unshift($this->lastQueries, $query);
	}

/**
 * find method
 *
 * @param mixed $type
 * @param array $options
 * @return void
 */
	public function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
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
 * @package       Cake.Test.Case.Controller.Component
 */
class ControllerPaginateModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerPaginateModel'
 */
	public $name = 'ControllerPaginateModel';

/**
 * useTable property
 *
 * @var string 'comments'
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
 * @return void
 */
	public function paginateCount($conditions, $recursive, $extra) {
		$this->extraCount = $extra;
	}
}

/**
 * PaginatorControllerCommentclass
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class PaginatorControllerComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 */
	public $useTable = 'comments';

/**
 * alias property
 *
 * @var string 'PaginatorControllerComment'
 */
	public $alias = 'PaginatorControllerComment';
}

class PaginatorComponentTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.post', 'core.comment');

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->request = new CakeRequest('controller_posts/index');
		$this->request->params['pass'] = $this->request->params['named'] = array();
		$this->Controller = new Controller($this->request);
		$this->Paginator = new PaginatorComponent($this->getMock('ComponentCollection'), array());
		$this->Paginator->Controller = $this->Controller;
		$this->Controller->Post = $this->getMock('Model');
		$this->Controller->Post->alias = 'Post';
	}

/**
 * testPaginate method
 *
 * @return void
 */
	public function testPaginate() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->request->params['pass'] = array('1');
		$Controller->request->query = array();
		$Controller->constructClasses();

		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerComment'), '{n}.PaginatorControllerComment.id');
		$this->assertEqual($results, array(1, 2, 3, 4, 5, 6));

		$Controller->modelClass = null;

		$Controller->uses[0] = 'Plugin.PaginatorControllerPost';
		$results = Set::extract($Controller->Paginator->paginate(), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->request->params['named'] = array('page' => '-1');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->request->params['named'] = array('sort' => 'PaginatorControllerPost.id', 'direction' => 'asc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->request->params['named'] = array('sort' => 'PaginatorControllerPost.id', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(3, 2, 1));

		$Controller->request->params['named'] = array('sort' => 'id', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual($results, array(3, 2, 1));

		$Controller->request->params['named'] = array('sort' => 'NotExisting.field', 'direction' => 'desc');
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1, 'Invalid field in query %s');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->request->params['named'] = array(
			'sort' => 'PaginatorControllerPost.author_id', 'direction' => 'allYourBase'
		);
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->PaginatorControllerPost->lastQueries[1]['order'][0], array('PaginatorControllerPost.author_id' => 'asc'));
		$this->assertEqual($results, array(1, 3, 2));

		$Controller->request->params['named'] = array();
		$Controller->Paginator->settings = array('limit' => 0, 'maxLimit' => 10, 'paramType' => 'named');
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['nextPage'], true);

		$Controller->request->params['named'] = array();
		$Controller->Paginator->settings = array('limit' => 'garbage!', 'maxLimit' => 10, 'paramType' => 'named');
		$Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['nextPage'], true);

		$Controller->request->params['named'] = array();
		$Controller->Paginator->settings = array('limit' => '-1', 'maxLimit' => 10, 'paramType' => 'named');
		$Controller->Paginator->paginate('PaginatorControllerPost');

		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['limit'], 1);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['PaginatorControllerPost']['nextPage'], true);
	}

/**
 * Test that non-numeric values are rejected for page, and limit
 *
 * @return void
 */
	public function testPageParamCasting() {
		$this->Controller->Post->expects($this->at(0))
			->method('hasMethod')
			->with('paginate')
			->will($this->returnValue(false));

		$this->Controller->Post->expects($this->at(1))
			->method('find')
			->will($this->returnValue(array('stuff')));

		$this->Controller->Post->expects($this->at(2))
			->method('hasMethod')
			->with('paginateCount')
			->will($this->returnValue(false));

		$this->Controller->Post->expects($this->at(3))
			->method('find')
			->will($this->returnValue(2));

		$this->request->params['named'] = array('page' => '1 " onclick="alert(\'xss\');">');
		$this->Paginator->settings = array('limit' => 1, 'maxLimit' => 10, 'paramType' => 'named');
		$this->Paginator->paginate('Post');
		$this->assertSame(1, $this->request->params['paging']['Post']['page'], 'XSS exploit opened');
	}

/**
 * testPaginateExtraParams method
 *
 * @return void
 */
	public function testPaginateExtraParams() {
		$Controller = new PaginatorTestController($this->request);

		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->request->params['pass'] = array('1');
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->request->params['named'] = array('page' => '-1', 'contain' => array('PaginatorControllerComment'));
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(1, 2, 3));
		$this->assertTrue(!isset($Controller->PaginatorControllerPost->lastQueries[1]['contain']));

		$Controller->request->params['named'] = array('page' => '-1');
		$Controller->Paginator->settings = array(
			'PaginatorControllerPost' => array(
				'contain' => array('PaginatorControllerComment'),
				'maxLimit' => 10,
				'paramType' => 'named'
			),
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(1, 2, 3));
		$this->assertTrue(isset($Controller->PaginatorControllerPost->lastQueries[1]['contain']));

		$Controller->Paginator->settings = array(
			'PaginatorControllerPost' => array(
				'popular', 'fields' => array('id', 'title'), 'maxLimit' => 10, 'paramType' => 'named'
			),
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(2, 3));
		$this->assertEqual($Controller->PaginatorControllerPost->lastQueries[1]['conditions'], array('PaginatorControllerPost.id > ' => '1'));

		$Controller->request->params['named'] = array('limit' => 12);
		$Controller->Paginator->settings = array('limit' => 30, 'maxLimit' => 100, 'paramType' => 'named');
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$paging = $Controller->params['paging']['PaginatorControllerPost'];

		$this->assertEqual($Controller->PaginatorControllerPost->lastQueries[1]['limit'], 12);
		$this->assertEqual($paging['options']['limit'], 12);

		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('ControllerPaginateModel');
		$Controller->request->query = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array(
			'ControllerPaginateModel' => array(
				'contain' => array('ControllerPaginateModel'),
				'group' => 'Comment.author_id',
				'maxLimit' => 10,
				'paramType' => 'named'
			)
		);
		$result = $Controller->Paginator->paginate('ControllerPaginateModel');
		$expected = array(
			'contain' => array('ControllerPaginateModel'),
			'group' => 'Comment.author_id',
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$this->assertEqual($Controller->ControllerPaginateModel->extra, $expected);
		$this->assertEqual($Controller->ControllerPaginateModel->extraCount, $expected);

		$Controller->Paginator->settings = array(
			'ControllerPaginateModel' => array(
				'foo', 'contain' => array('ControllerPaginateModel'),
				'group' => 'Comment.author_id',
				'maxLimit' => 10,
				'paramType' => 'named'
			)
		);
		$Controller->Paginator->paginate('ControllerPaginateModel');
		$expected = array(
			'contain' => array('ControllerPaginateModel'),
			'group' => 'Comment.author_id',
			'type' => 'foo',
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$this->assertEqual($Controller->ControllerPaginateModel->extra, $expected);
		$this->assertEqual($Controller->ControllerPaginateModel->extraCount, $expected);
	}

/**
 * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
 *
 * @return void
 */
	public function testPaginateSpecialType() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->Paginator->settings = array(
			'PaginatorControllerPost' => array(
				'popular',
				'fields' => array('id', 'title'),
				'maxLimit' => 10,
				'paramType' => 'named'
			)
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');

		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.id'), array(2, 3));
		$this->assertEqual(
			$Controller->PaginatorControllerPost->lastQueries[1]['conditions'],
			array('PaginatorControllerPost.id > ' => '1')
		);
		$this->assertFalse(isset($Controller->params['paging']['PaginatorControllerPost']['options'][0]));
	}

/**
 * testDefaultPaginateParams method
 *
 * @return void
 */
	public function testDefaultPaginateParams() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->modelClass = 'PaginatorControllerPost';
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->Paginator->settings = array(
			'order' => 'PaginatorControllerPost.id DESC',
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$results = Set::extract($Controller->Paginator->paginate('PaginatorControllerPost'), '{n}.PaginatorControllerPost.id');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['order'], 'PaginatorControllerPost.id DESC');
		$this->assertEqual($results, array(3, 2, 1));
	}

/**
 * test paginate() and virtualField interactions
 *
 * @return void
 */
	public function testPaginateOrderVirtualField() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->PaginatorControllerPost->virtualFields = array(
			'offset_test' => 'PaginatorControllerPost.id + 1'
		);

		$Controller->Paginator->settings = array(
			'fields' => array('id', 'title', 'offset_test'),
			'order' => array('offset_test' => 'DESC'),
			'maxLimit' => 10,
			'paramType' => 'named'
		);
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.offset_test'), array(4, 3, 2));

		$Controller->request->params['named'] = array('sort' => 'offset_test', 'direction' => 'asc');
		$result = $Controller->Paginator->paginate('PaginatorControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerPost.offset_test'), array(2, 3, 4));
	}

/**
 * Tests for missing models
 *
 * @expectedException MissingModelException
 */
	public function testPaginateMissingModel() {
		$Controller = new PaginatorTestController($this->request);
		$Controller->constructClasses();
		$Controller->Paginator->paginate('MissingModel');
	}

/**
 * test that option merging prefers specific models
 *
 * @return void
 */
	public function testMergeOptionsModelSpecific() {
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
			'Post' => array(
				'page' => 1,
				'limit' => 10,
				'maxLimit' => 50,
				'paramType' => 'named',
			)
		);
		$result = $this->Paginator->mergeOptions('Silly');
		$this->assertEquals($this->Paginator->settings, $result);

		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 1, 'limit' => 10, 'paramType' => 'named', 'maxLimit' => 50);
		$this->assertEquals($expected, $result);
	}

/**
 * test mergeOptions with named params.
 *
 * @return void
 */
	public function testMergeOptionsNamedParams() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 10, 'limit' => 10, 'maxLimit' => 100, 'paramType' => 'named');
		$this->assertEquals($expected, $result);
	}

/**
 * test merging options from the querystring.
 *
 * @return void
 */
	public function testMergeOptionsQueryString() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10
		);
		$this->request->query = array(
			'page' => 99,
			'limit' => 75
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'querystring',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 99, 'limit' => 75, 'maxLimit' => 100, 'paramType' => 'querystring');
		$this->assertEquals($expected, $result);
	}

/**
 * test that the default whitelist doesn't let people screw with things they should not be allowed to.
 *
 * @return void
 */
	public function testMergeOptionsDefaultWhiteList() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10,
			'fields' => array('bad.stuff'),
			'recursive' => 1000,
			'conditions' => array('bad.stuff'),
			'contain' => array('bad')
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array('page' => 10, 'limit' => 10, 'maxLimit' => 100, 'paramType' => 'named');
		$this->assertEquals($expected, $result);
	}

/**
 * test that modifying the whitelist works.
 *
 * @return void
 */
	public function testMergeOptionsExtraWhitelist() {
		$this->request->params['named'] = array(
			'page' => 10,
			'limit' => 10,
			'fields' => array('bad.stuff'),
			'recursive' => 1000,
			'conditions' => array('bad.stuff'),
			'contain' => array('bad')
		);
		$this->Paginator->settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
		);
		$this->Paginator->whitelist[] = 'fields';
		$result = $this->Paginator->mergeOptions('Post');
		$expected = array(
			'page' => 10, 'limit' => 10, 'maxLimit' => 100, 'paramType' => 'named', 'fields' => array('bad.stuff')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test that invalid directions are ignored.
 *
 * @return void
 */
	public function testValidateSortInvalidDirection() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('sort' => 'something', 'direction' => 'boogers');
		$result = $this->Paginator->validateSort($model, $options);

		$this->assertEquals('asc', $result['order']['model.something']);
	}

/**
 * test that fields not in whitelist won't be part of order conditions.
 *
 * @return void
 */
	public function testValidateSortWhitelistFailure() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('sort' => 'body', 'direction' => 'asc');
		$result = $this->Paginator->validateSort($model, $options, array('title', 'id'));

		$this->assertNull($result['order']);
	}

/**
 * test that virtual fields work.
 *
 * @return void
 */
	public function testValidateSortVirtualField() {
		$model = $this->getMock('Model');
		$model->alias = 'model';

		$model->expects($this->at(0))
			->method('hasField')
			->with('something')
			->will($this->returnValue(false));

		$model->expects($this->at(1))
			->method('hasField')
			->with('something', true)
			->will($this->returnValue(true));

		$options = array('sort' => 'something', 'direction' => 'desc');
		$result = $this->Paginator->validateSort($model, $options);

		$this->assertEquals('desc', $result['order']['something']);
	}

/**
 * test that multiple sort works.
 *
 * @return void
 */
	public function testValidateSortMultiple() {
		$model = $this->getMock('Model');
		$model->alias = 'model';
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('order' => array(
			'author_id' => 'asc',
			'title' => 'asc'
		));
		$result = $this->Paginator->validateSort($model, $options);
		$expected = array(
			'model.author_id' => 'asc',
			'model.title' => 'asc'
		);

		$this->assertEquals($expected, $result['order']);
	}

/**
 * test that maxLimit is respected
 *
 * @return void
 */
	public function testCheckLimit() {
		$result = $this->Paginator->checkLimit(array('limit' => 1000000, 'maxLimit' => 100));
		$this->assertEquals(100, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => 'sheep!', 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => '-1', 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => null, 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);

		$result = $this->Paginator->checkLimit(array('limit' => 0, 'maxLimit' => 100));
		$this->assertEquals(1, $result['limit']);
	}

/**
 * testPaginateMaxLimit
 *
 * @return void
 */
	public function testPaginateMaxLimit() {
		$Controller = new Controller($this->request);

		$Controller->uses = array('PaginatorControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->constructClasses();

		$Controller->request->params['named'] = array(
			'contain' => array('ControllerComment'), 'limit' => '1000'
		);
		$result = $Controller->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['options']['limit'], 100);

		$Controller->request->params['named'] = array(
			'contain' => array('ControllerComment'), 'limit' => '1000', 'maxLimit' => 1000
		);
		$result = $Controller->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['options']['limit'], 100);

		$Controller->request->params['named'] = array('contain' => array('ControllerComment'), 'limit' => '10');
		$result = $Controller->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['options']['limit'], 10);

		$Controller->request->params['named'] = array('contain' => array('ControllerComment'), 'limit' => '1000');
		$Controller->paginate = array('maxLimit' => 2000, 'paramType' => 'named');
		$result = $Controller->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['options']['limit'], 1000);

		$Controller->request->params['named'] = array('contain' => array('ControllerComment'), 'limit' => '5000');
		$result = $Controller->paginate('PaginatorControllerPost');
		$this->assertEqual($Controller->params['paging']['PaginatorControllerPost']['options']['limit'], 2000);
	 }

/**
 * test paginate() and virtualField overlapping with real fields.
 *
 * @return void
 */
	function testPaginateOrderVirtualFieldSharedWithRealField() {
		$Controller =& new Controller($this->request);
		$Controller->uses = array('PaginatorControllerPost', 'PaginatorControllerComment');
		$Controller->constructClasses();
		$Controller->PaginatorControllerComment->virtualFields = array(
			'title' => 'PaginatorControllerComment.comment'
		);
		$Controller->PaginatorControllerComment->bindModel(array(
			'belongsTo' => array(
				'PaginatorControllerPost' => array(
					'className' => 'PaginatorControllerPost',
					'foreignKey' => 'article_id'
				)
			)
		), false);

		$Controller->paginate = array(
			'fields' => array('PaginatorControllerComment.id', 'title', 'PaginatorControllerPost.title'),
		);
		$Controller->passedArgs = array('sort' => 'PaginatorControllerPost.title', 'dir' => 'asc');
		$result = $Controller->paginate('PaginatorControllerComment');
		$this->assertEqual(Set::extract($result, '{n}.PaginatorControllerComment.id'), array(1, 2, 3, 4, 5, 6));
	}

}
