<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\PaginatorComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Error;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

/**
 * PaginatorTestController class
 *
 */
class PaginatorTestController extends Controller {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Paginator');
}

class PaginatorComponentTest extends TestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.post', 'core.author');

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->request = new Request('controller_posts/index');
		$this->request->params['pass'] = array();
		$controller = new Controller($this->request);
		$registry = new ComponentRegistry($controller);
		$this->Paginator = new PaginatorComponent($registry, []);

		$this->Post = $this->getMock('Cake\ORM\Table', [], [], '', false);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Test that non-numeric values are rejected for page, and limit
 *
 * @return void
 */
	public function testPageParamCasting() {
		$this->Post->expects($this->any())
			->method('alias')
			->will($this->returnValue('Posts'));

		$query = $this->_getMockFindQuery();
		$this->Post->expects($this->any())
			->method('find')
			->will($this->returnValue($query));

		$this->request->query = array('page' => '1 " onclick="alert(\'xss\');">');
		$settings = array('limit' => 1, 'maxLimit' => 10);
		$this->Paginator->paginate($this->Post, $settings);
		$this->assertSame(1, $this->request->params['paging']['Posts']['page'], 'XSS exploit opened');
	}

/**
 * test that unknown keys in the default settings are
 * passed to the find operations.
 *
 * @return void
 */
	public function testPaginateExtraParams() {
		$this->request->query = array('page' => '-1');
		$settings = array(
			'PaginatorPosts' => array(
				'contain' => array('PaginatorAuthor'),
				'maxLimit' => 10,
				'group' => 'PaginatorPosts.published',
				'order' => array('PaginatorPosts.id' => 'ASC')
			),
		);
		$table = $this->_getMockPosts(['find']);
		$query = $this->_getMockFindQuery();
		$table->expects($this->once())
			->method('find')
			->with('all', [
				'conditions' => [],
				'contain' => ['PaginatorAuthor'],
				'fields' => null,
				'group' => 'PaginatorPosts.published',
				'limit' => 10,
				'order' => ['PaginatorPosts.id' => 'ASC'],
				'page' => 1,
			])
			->will($this->returnValue($query));

		$this->Paginator->paginate($table, $settings);
	}

/**
 * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
 *
 * @return void
 */
	public function testPaginateCustomFinder() {
		$settings = array(
			'PaginatorPosts' => array(
				'findType' => 'popular',
				'fields' => array('id', 'title'),
				'maxLimit' => 10,
			)
		);

		$table = $this->_getMockPosts(['findPopular']);
		$query = $this->_getMockFindQuery();

		$table->expects($this->any())
			->method('findPopular')
			->will($this->returnValue($query));

		$this->Paginator->paginate($table, $settings);
		$this->assertEquals('popular', $this->request->params['paging']['PaginatorPosts']['findType']);
	}

/**
 * test that flat default pagination parameters work.
 *
 * @return void
 */
	public function testDefaultPaginateParams() {
		$settings = array(
			'order' => ['PaginatorPosts.id' => 'DESC'],
			'maxLimit' => 10,
		);

		$table = $this->_getMockPosts(['find']);
		$query = $this->_getMockFindQuery();

		$table->expects($this->once())
			->method('find')
			->with('all', [
				'conditions' => [],
				'fields' => null,
				'limit' => 10,
				'page' => 1,
				'order' => ['PaginatorPosts.id' => 'DESC']
			])
			->will($this->returnValue($query));

		$this->Paginator->paginate($table, $settings);
	}

/**
 * test that default sort and default direction are injected into request
 *
 * @return void
 */
	public function testDefaultPaginateParamsIntoRequest() {
		$settings = array(
			'order' => ['PaginatorPosts.id' => 'DESC'],
			'maxLimit' => 10,
		);

		$table = $this->_getMockPosts(['find']);
		$query = $this->_getMockFindQuery();

		$table->expects($this->once())
			->method('find')
			->with('all', [
				'conditions' => [],
				'fields' => null,
				'limit' => 10,
				'page' => 1,
				'order' => ['PaginatorPosts.id' => 'DESC']
			])
			->will($this->returnValue($query));

		$this->Paginator->paginate($table, $settings);
		$this->assertEquals('PaginatorPosts.id', $this->request->params['paging']['PaginatorPosts']['sortDefault']);
		$this->assertEquals('DESC', $this->request->params['paging']['PaginatorPosts']['directionDefault']);
	}

/**
 * test that option merging prefers specific models
 *
 * @return void
 */
	public function testMergeOptionsModelSpecific() {
		$settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'Posts' => array(
				'page' => 1,
				'limit' => 10,
				'maxLimit' => 50,
			)
		);
		$result = $this->Paginator->mergeOptions('Silly', $settings);
		$this->assertEquals($settings, $result);

		$result = $this->Paginator->mergeOptions('Posts', $settings);
		$expected = array('page' => 1, 'limit' => 10, 'maxLimit' => 50);
		$this->assertEquals($expected, $result);
	}

/**
 * test mergeOptions with customFind key
 *
 * @return void
 */
	public function testMergeOptionsCustomFindKey() {
		$this->request->query = [
			'page' => 10,
			'limit' => 10
		];
		$settings = [
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'findType' => 'myCustomFind'
		];
		$result = $this->Paginator->mergeOptions('Post', $settings);
		$expected = array(
			'page' => 10,
			'limit' => 10,
			'maxLimit' => 100,
			'findType' => 'myCustomFind'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test merging options from the querystring.
 *
 * @return void
 */
	public function testMergeOptionsQueryString() {
		$this->request->query = array(
			'page' => 99,
			'limit' => 75
		);
		$settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
		);
		$result = $this->Paginator->mergeOptions('Post', $settings);
		$expected = array('page' => 99, 'limit' => 75, 'maxLimit' => 100);
		$this->assertEquals($expected, $result);
	}

/**
 * test that the default whitelist doesn't let people screw with things they should not be allowed to.
 *
 * @return void
 */
	public function testMergeOptionsDefaultWhiteList() {
		$this->request->query = array(
			'page' => 10,
			'limit' => 10,
			'fields' => array('bad.stuff'),
			'recursive' => 1000,
			'conditions' => array('bad.stuff'),
			'contain' => array('bad')
		);
		$settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
		);
		$result = $this->Paginator->mergeOptions('Post', $settings);
		$expected = array('page' => 10, 'limit' => 10, 'maxLimit' => 100);
		$this->assertEquals($expected, $result);
	}

/**
 * test that modifying the whitelist works.
 *
 * @return void
 */
	public function testMergeOptionsExtraWhitelist() {
		$this->request->query = array(
			'page' => 10,
			'limit' => 10,
			'fields' => array('bad.stuff'),
			'recursive' => 1000,
			'conditions' => array('bad.stuff'),
			'contain' => array('bad')
		);
		$settings = array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
		);
		$this->Paginator->whitelist[] = 'fields';
		$result = $this->Paginator->mergeOptions('Post', $settings);
		$expected = array(
			'page' => 10, 'limit' => 10, 'maxLimit' => 100, 'fields' => array('bad.stuff')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test mergeOptions with limit > maxLimit in code.
 *
 * @return void
 */
	public function testMergeOptionsMaxLimit() {
		$settings = array(
			'limit' => 200,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post', $settings);
		$expected = array('page' => 1, 'limit' => 200, 'maxLimit' => 200, 'paramType' => 'named');
		$this->assertEquals($expected, $result);

		$settings = array(
			'maxLimit' => 10,
			'paramType' => 'named',
		);
		$result = $this->Paginator->mergeOptions('Post', $settings);
		$expected = array('page' => 1, 'limit' => 20, 'maxLimit' => 10, 'paramType' => 'named');
		$this->assertEquals($expected, $result);
	}

/**
 * Integration test to ensure that validateSort is being used by paginate()
 *
 * @return void
 */
	public function testValidateSortInvalid() {
		$table = $this->_getMockPosts(['find']);
		$query = $this->_getMockFindQuery();

		$table->expects($this->once())
			->method('find')
			->with('all', [
				'fields' => null,
				'limit' => 20,
				'conditions' => [],
				'page' => 1,
				'order' => ['PaginatorPosts.id' => 'asc'],
			])
			->will($this->returnValue($query));

		$this->request->query = [
			'page' => 1,
			'sort' => 'id',
			'direction' => 'herp'
		];
		$this->Paginator->paginate($table);
		$this->assertEquals('PaginatorPosts.id', $this->request->params['paging']['PaginatorPosts']['sort']);
		$this->assertEquals('asc', $this->request->params['paging']['PaginatorPosts']['direction']);
	}

/**
 * test that invalid directions are ignored.
 *
 * @return void
 */
	public function testValidateSortInvalidDirection() {
		$model = $this->getMock('Cake\ORM\Table');
		$model->expects($this->any())
			->method('alias')
			->will($this->returnValue('model'));
		$model->expects($this->any())
			->method('hasField')
			->will($this->returnValue(true));

		$options = array('sort' => 'something', 'direction' => 'boogers');
		$result = $this->Paginator->validateSort($model, $options);

		$this->assertEquals('asc', $result['order']['model.something']);
	}

/**
 * Test that a really large page number gets clamped to the max page size.
 *
 * @return void
 */
	public function testOutOfRangePageNumberGetsClamped() {
		$this->request->query['page'] = 3000;

		$table = TableRegistry::get('PaginatorPosts');
		try {
			$this->Paginator->paginate($table);
			$this->fail('No exception raised');
		} catch (\Cake\Error\NotFoundException $e) {
			$this->assertEquals(
				1,
				$this->request->params['paging']['PaginatorPosts']['page'],
				'Page number should not be 0'
			);
		}
	}

/**
 * Test that a really REALLY large page number gets clamped to the max page size.
 *
 * @expectedException \Cake\Error\NotFoundException
 * @return void
 */
	public function testOutOfVeryBigPageNumberGetsClamped() {
		$this->request->query = [
			'page' => '3000000000000000000000000',
		];

		$table = TableRegistry::get('PaginatorPosts');
		$this->Paginator->paginate($table);
	}

/**
 * test that fields not in whitelist won't be part of order conditions.
 *
 * @return void
 */
	public function testValidateSortWhitelistFailure() {
		$model = $this->getMock('Cake\ORM\Table');
		$model->expects($this->any())
			->method('alias')
			->will($this->returnValue('model'));
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array(
			'sort' => 'body',
			'direction' => 'asc',
			'sortWhitelist' => ['title', 'id']
		);
		$result = $this->Paginator->validateSort($model, $options);

		$this->assertEquals([], $result['order']);
	}

/**
 * test that fields in the whitelist are not validated
 *
 * @return void
 */
	public function testValidateSortWhitelistTrusted() {
		$model = $this->getMock('Cake\ORM\Table');
		$model->expects($this->any())
			->method('alias')
			->will($this->returnValue('model'));
		$model->expects($this->never())->method('hasField');

		$options = array(
			'sort' => 'body',
			'direction' => 'asc',
			'sortWhitelist' => ['body']
		);
		$result = $this->Paginator->validateSort($model, $options);

		$expected = array('body' => 'asc');
		$this->assertEquals($expected, $result['order']);
	}

/**
 * test that multiple sort works.
 *
 * @return void
 */
	public function testValidateSortMultiple() {
		$model = $this->getMock('Cake\ORM\Table');
		$model->expects($this->any())
			->method('alias')
			->will($this->returnValue('model'));
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array(
			'order' => array(
				'author_id' => 'asc',
				'title' => 'asc'
			)
		);
		$result = $this->Paginator->validateSort($model, $options);
		$expected = array(
			'model.author_id' => 'asc',
			'model.title' => 'asc'
		);

		$this->assertEquals($expected, $result['order']);
	}

/**
 * Tests that order strings can used by Paginator
 *
 * @return void
 */
	public function testValidateSortWithString() {
		$model = $this->getMock('Cake\ORM\Table');
		$model->expects($this->any())
			->method('alias')
			->will($this->returnValue('model'));
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array(
			'order' => 'model.author_id DESC'
		);
		$result = $this->Paginator->validateSort($model, $options);
		$expected = 'model.author_id DESC';

		$this->assertEquals($expected, $result['order']);
	}

/**
 * Test that no sort doesn't trigger an error.
 *
 * @return void
 */
	public function testValidateSortNoSort() {
		$model = $this->getMock('Cake\ORM\Table');
		$model->expects($this->any())
			->method('alias')
			->will($this->returnValue('model'));
		$model->expects($this->any())->method('hasField')
			->will($this->returnValue(true));

		$options = array(
			'direction' => 'asc',
			'sortWhitelist' => ['title', 'id'],
		);
		$result = $this->Paginator->validateSort($model, $options);
		$this->assertEquals([], $result['order']);
	}

/**
 * Test sorting with incorrect aliases on valid fields.
 *
 * @return void
 */
	public function testValidateSortInvalidAlias() {
		$model = $this->getMock('Cake\ORM\Table');
		$model->expects($this->any())
			->method('alias')
			->will($this->returnValue('model'));
		$model->expects($this->any())->method('hasField')->will($this->returnValue(true));

		$options = array('sort' => 'Derp.id');
		$result = $this->Paginator->validateSort($model, $options);
		$this->assertEquals(array(), $result['order']);
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
 * Integration test for checkLimit() being applied inside paginate()
 *
 * @return void
 */
	public function testPaginateMaxLimit() {
		$table = TableRegistry::get('PaginatorPosts');

		$settings = [
			'maxLimit' => 100,
		];
		$this->request->query = [
			'limit' => '1000'
		];
		$this->Paginator->paginate($table, $settings);
		$this->assertEquals(100, $this->request->params['paging']['PaginatorPosts']['limit']);

		$this->request->query = [
			'limit' => '10'
		];
		$this->Paginator->paginate($table, $settings);
		$this->assertEquals(10, $this->request->params['paging']['PaginatorPosts']['limit']);
	}

/**
 * test paginate() and custom find, to make sure the correct count is returned.
 *
 * @return void
 */
	public function testPaginateCustomFind() {
		$idExtractor = function ($result) {
			$ids = [];
			foreach ($result as $record) {
				$ids[] = $record->id;
			}
			return $ids;
		};

		$table = TableRegistry::get('PaginatorPosts');
		$data = array('author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$result = $table->save(new \Cake\ORM\Entity($data));
		$this->assertNotEmpty($result);

		$result = $this->Paginator->paginate($table);
		$this->assertCount(4, $result, '4 rows should come back');
		$this->assertEquals(array(1, 2, 3, 4), $idExtractor($result));

		$result = $this->request->params['paging']['PaginatorPosts'];
		$this->assertEquals(4, $result['current']);
		$this->assertEquals(4, $result['count']);

		$settings = array('findType' => 'published');
		$result = $this->Paginator->paginate($table, $settings);
		$this->assertCount(3, $result, '3 rows should come back');
		$this->assertEquals(array(1, 2, 3), $idExtractor($result));

		$result = $this->request->params['paging']['PaginatorPosts'];
		$this->assertEquals(3, $result['current']);
		$this->assertEquals(3, $result['count']);

		$settings = array('findType' => 'published', 'limit' => 2);
		$result = $this->Paginator->paginate($table, $settings);
		$this->assertCount(2, $result, '2 rows should come back');
		$this->assertEquals(array(1, 2), $idExtractor($result));

		$result = $this->request->params['paging']['PaginatorPosts'];
		$this->assertEquals(2, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertTrue($result['nextPage']);
		$this->assertFalse($result['prevPage']);
	}

/**
 * test paginate() and custom find with fields array, to make sure the correct count is returned.
 *
 * @return void
 */
	public function testPaginateCustomFindFieldsArray() {
		$table = TableRegistry::get('PaginatorPosts');
		$data = array('author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$table->save(new \Cake\ORM\Entity($data));

		$settings = [
			'findType' => 'list',
			'conditions' => array('PaginatorPosts.published' => 'Y'),
			'limit' => 2
		];
		$results = $this->Paginator->paginate($table, $settings);

		$result = $results->toArray();
		$expected = array(
			1 => 'First Post',
			2 => 'Second Post',
		);
		$this->assertEquals($expected, $result);

		$result = $this->request->params['paging']['PaginatorPosts'];
		$this->assertEquals(2, $result['current']);
		$this->assertEquals(3, $result['count']);
		$this->assertEquals(2, $result['pageCount']);
		$this->assertTrue($result['nextPage']);
		$this->assertFalse($result['prevPage']);
	}

/**
 * test paginate() and custom finders to ensure the count + find
 * use the custom type.
 *
 * @return void
 */
	public function testPaginateCustomFindCount() {
		$settings = array(
			'findType' => 'published',
			'limit' => 2
		);
		$table = $this->_getMockPosts(['find']);
		$query = $this->_getMockFindQuery();
		$table->expects($this->once())
			->method('find')
			->with('published', [
				'conditions' => [],
				'order' => [],
				'limit' => 2,
				'fields' => null,
				'page' => 1,
			])
			->will($this->returnValue($query));

		$this->Paginator->paginate($table, $settings);
	}

/**
 * Helper method for making mocks.
 *
 * @param array $methods
 * @return Table
 */
	protected function _getMockPosts($methods = []) {
		return $this->getMock(
			'TestApp\Model\Table\PaginatorPostsTable',
			$methods,
			[['connection' => ConnectionManager::get('test'), 'alias' => 'PaginatorPosts']]
		);
	}

/**
 * Helper method for mocking queries.
 *
 * @return Query
 */
	protected function _getMockFindQuery() {
		$query = $this->getMockBuilder('Cake\ORM\Query')
			->setMethods(['total', 'all', 'count'])
			->disableOriginalConstructor()
			->getMock();

		$results = $this->getMock('Cake\ORM\ResultSet', [], [], '', false);
		$query->expects($this->any())
			->method('count')
			->will($this->returnValue(2));

		$query->expects($this->any())
			->method('all')
			->will($this->returnValue($results));

		$query->expects($this->any())
			->method('count')
			->will($this->returnValue(2));

		return $query;
	}

}
