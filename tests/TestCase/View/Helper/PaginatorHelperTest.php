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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\PaginatorHelper;
use Cake\View\View;

/**
 * PaginatorHelperTest class
 *
 */
class PaginatorHelperTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Config.language', 'eng');
		$this->View = new View();
		$this->Paginator = new PaginatorHelper($this->View);
		$this->Paginator->Js = $this->getMock('Cake\View\Helper\PaginatorHelper', array(), array($this->View));
		$this->Paginator->request = new Request();
		$this->Paginator->request->addParams(array(
			'paging' => array(
				'Article' => array(
					'page' => 1,
					'current' => 9,
					'count' => 62,
					'prevPage' => false,
					'nextPage' => true,
					'pageCount' => 7,
					'sort' => null,
					'direction' => null,
					'limit' => null,
				)
			)
		));

		Configure::write('Routing.prefixes', array());
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::connect('/:plugin/:controller/:action/*');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->View, $this->Paginator);
	}

/**
 * Test the templates method.
 *
 * @return void
 */
	public function testTemplates() {
		$result = $this->Paginator->templates([
			'test' => 'val'
		]);
		$this->assertSame(
			$this->Paginator,
			$result,
			'Setting should return the same object'
		);

		$result = $this->Paginator->templates();
		$this->assertArrayHasKey('test', $result);
		$this->assertEquals('val', $result['test']);

		$this->assertEquals('val', $this->Paginator->templates('test'));
	}

/**
 * testHasPrevious method
 *
 * @return void
 */
	public function testHasPrevious() {
		$this->assertFalse($this->Paginator->hasPrev());
		$this->Paginator->request->params['paging']['Article']['prevPage'] = true;
		$this->assertTrue($this->Paginator->hasPrev());
		$this->Paginator->request->params['paging']['Article']['prevPage'] = false;
	}

/**
 * testHasNext method
 *
 * @return void
 */
	public function testHasNext() {
		$this->assertTrue($this->Paginator->hasNext());
		$this->Paginator->request->params['paging']['Article']['nextPage'] = false;
		$this->assertFalse($this->Paginator->hasNext());
		$this->Paginator->request->params['paging']['Article']['nextPage'] = true;
	}

/**
 * testSortLinks method
 *
 * @return void
 */
	public function testSortLinks() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'url' => array('url' => 'accounts/')),
			array('base' => '', 'here' => '/accounts/', 'webroot' => '/')
		));

		$this->Paginator->options(array('url' => array('param')));
		$this->Paginator->request['paging'] = array(
			'Article' => array(
				'current' => 9,
				'count' => 62,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 7,
				'sort' => 'date',
				'direction' => 'asc',
				'page' => 1,
			)
		);

		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', null, ['model' => 'Nope']);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', null, ['model' => 'Article']);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('date');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=date&amp;direction=desc', 'class' => 'asc'),
			'Date',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', 'TestTitle');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=asc'),
			'TestTitle',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', array('asc' => 'ascending', 'desc' => 'descending'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=asc'),
			'ascending',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'title';
		$result = $this->Paginator->sort('title', array('asc' => 'ascending', 'desc' => 'descending'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'),
			'descending',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'asc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'asc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';

		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test sort() with escape option
 */
	public function testSortEscape() {
		$result = $this->Paginator->sort('title', 'TestTitle >');
		$expected = array(
			'a' => array('href' => '/index?sort=title&amp;direction=asc'),
			'TestTitle &gt;',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', 'TestTitle >', ['escape' => true]);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', 'TestTitle >', ['escape' => false]);
		$expected = array(
			'a' => array('href' => '/index?sort=title&amp;direction=asc'),
			'TestTitle >',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that sort() works with virtual field order options.
 *
 * @return void
 */
	public function testSortLinkWithVirtualField() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array('url' => 'accounts/')),
			array('base' => '', 'here' => '/accounts/', 'webroot' => '/')
		));
		$this->Paginator->request->params['paging']['Article']['sort'] = 'full_name';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';

		$result = $this->Paginator->sort('Article.full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=Article.full_name&amp;direction=desc', 'class' => 'asc'),
			'Article Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=full_name&amp;direction=desc', 'class' => 'asc'),
			'Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'full_name';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sort('Article.full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=Article.full_name&amp;direction=asc', 'class' => 'desc'),
			'Article Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=full_name&amp;direction=asc', 'class' => 'desc'),
			'Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSortLinksUsingDirectionOption method
 *
 * @return void
 */
	public function testSortLinksUsingDirectionOption() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index',
				'url' => array('url' => 'accounts/', 'mod_rewrite' => 'true')),
			array('base' => '/', 'here' => '/accounts/', 'webroot' => '/')
		));
		$this->Paginator->options(array('url' => array('param')));

		$result = $this->Paginator->sort('title', 'TestTitle', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=desc'),
			'TestTitle',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', array('asc' => 'ascending', 'desc' => 'descending'), array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?sort=title&amp;direction=desc'),
			'descending',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSortLinksUsingDotNotation method
 *
 * @return void
 */
	public function testSortLinksUsingDotNotation() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array()),
			array('base' => '', 'here' => '/accounts/', 'webroot' => '/')
		));

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sort('Article.title');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=Article.title&amp;direction=asc', 'class' => 'desc'),
			'Article Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sort('Article.title', 'Title');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=Article.title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
		$result = $this->Paginator->sort('Article.title', 'Title');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=Article.title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Account.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index?sort=title&amp;direction=asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSortKey method
 *
 * @return void
 */
	public function testSortKey() {
		$result = $this->Paginator->sortKey('Article', array('sort' => 'Article.title'));
		$this->assertEquals('Article.title', $result);

		$result = $this->Paginator->sortKey('Article', array('sort' => 'Article'));
		$this->assertEquals('Article', $result);
	}

/**
 * Test that sortKey falls back to the default sorting options set
 * in the $params which are the default pagination options.
 *
 * @return void
 */
	public function testSortKeyFallbackToParams() {
		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.body';
		$result = $this->Paginator->sortKey();
		$this->assertEquals('Article.body', $result);

		$result = $this->Paginator->sortKey('Article');
		$this->assertEquals('Article.body', $result);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.body';
		$this->Paginator->request->params['paging']['Article']['order'] = 'DESC';
		$result = $this->Paginator->sortKey();
		$this->assertEquals('Article.body', $result);

		$result = $this->Paginator->sortKey('Article');
		$this->assertEquals('Article.body', $result);
	}

/**
 * testSortDir method
 *
 * @return void
 */
	public function testSortDir() {
		$result = $this->Paginator->sortDir();
		$expected = 'asc';
		$this->assertEquals($expected, $result);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sortDir();
		$this->assertEquals('desc', $result);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
		$result = $this->Paginator->sortDir();
		$this->assertEquals('asc', $result);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
		$result = $this->Paginator->sortDir();
		$this->assertEquals('desc', $result);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
		$result = $this->Paginator->sortDir();
		$this->assertEquals('asc', $result);

		unset($this->Paginator->request->params['paging']['Article']['direction']);
		$result = $this->Paginator->sortDir('Article', array('direction' => 'asc'));
		$this->assertEquals('asc', $result);

		$result = $this->Paginator->sortDir('Article', array('direction' => 'desc'));
		$this->assertEquals('desc', $result);

		$result = $this->Paginator->sortDir('Article', array('direction' => 'asc'));
		$this->assertEquals('asc', $result);
	}

/**
 * Test that sortDir falls back to the default sorting options set
 * in the $params which are the default pagination options.
 *
 * @return void
 */
	public function testSortDirFallbackToParams() {
		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.body';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';

		$result = $this->Paginator->sortDir();
		$this->assertEquals('asc', $result);

		$result = $this->Paginator->sortDir('Article');
		$this->assertEquals('asc', $result);

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.body';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'DESC';

		$result = $this->Paginator->sortDir();
		$this->assertEquals('desc', $result);

		$result = $this->Paginator->sortDir('Article');
		$this->assertEquals('desc', $result);
	}

/**
 * testSortAdminLinks method
 *
 * @return void
 */
	public function testSortAdminLinks() {
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));
		Router::setRequestInfo(array(
			array('controller' => 'users', 'plugin' => null, 'action' => 'index', 'prefix' => 'admin'),
			array('base' => '', 'here' => '/admin/users', 'webroot' => '/')
		));
		$this->Paginator->request->params['paging']['Article']['page'] = 1;
		$result = $this->Paginator->next('Next');
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('href' => '/admin/users/index?page=2', 'rel' => 'next'),
			'Next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/admin/users/index/param?sort=title&amp;direction=asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('Article.title', 'Title');
		$expected = array(
			'a' => array('href' => '/admin/users/index/param?sort=Article.title&amp;direction=asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that generated URLs work without sort defined within the request
 *
 * @return void
 */
	public function testDefaultSortAndNoSort() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'articles', 'action' => 'index'),
			array('base' => '/', 'here' => '/articles/', 'webroot' => '/')
		));
		$this->Paginator->request->params['paging'] = array(
			'Article' => array(
				'page' => 1, 'current' => 3, 'count' => 13,
				'prevPage' => false, 'nextPage' => true, 'pageCount' => 8,
				'sortDefault' => 'Article.title', 'directionDefault' => 'ASC',
				'sort' => null
			)
		);
		$result = $this->Paginator->next('Next');
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('rel' => 'next', 'href' => '/articles/index?page=2'),
			'Next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testUrlGeneration method
 *
 * @return void
 */
	public function testUrlGeneration() {
		$result = $this->Paginator->sort('controller');
		$expected = array(
			'a' => array('href' => '/index?sort=controller&amp;direction=asc'),
			'Controller',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->generateUrl();
		$this->assertEquals('/index', $result);

		$this->Paginator->request->params['paging']['Article']['page'] = 2;
		$result = $this->Paginator->generateUrl();
		$this->assertEquals('/index?page=2', $result);

		$options = array('sort' => 'Article', 'direction' => 'desc');
		$result = $this->Paginator->generateUrl($options);
		$this->assertEquals('/index?page=2&amp;sort=Article&amp;direction=desc', $result);

		$this->Paginator->request->params['paging']['Article']['page'] = 3;
		$options = array('sort' => 'Article.name', 'direction' => 'desc');
		$result = $this->Paginator->generateUrl($options);
		$this->assertEquals('/index?page=3&amp;sort=Article.name&amp;direction=desc', $result);
	}

/**
 * test URL generation with prefix routes
 *
 * @return void
 */
	public function testUrlGenerationWithPrefixes() {
		Configure::write('Routing.prefixes', array('members'));
		Router::reload();
		Router::connect('/members/:controller/:action/*', array('prefix' => 'members'));
		Router::connect('/:controller/:action/*');

		Router::setRequestInfo(array(
			array('controller' => 'posts', 'action' => 'index', 'plugin' => null),
			array('base' => '', 'here' => 'posts/index', 'webroot' => '/')
		));

		$this->Paginator->request->params['paging']['Article']['page'] = 2;
		$this->Paginator->request->params['paging']['Article']['prevPage'] = true;
		$options = array('prefix' => 'members');

		$result = $this->Paginator->generateUrl($options);
		$expected = '/members/posts/index?page=2';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->sort('name', null, array('url' => $options));
		$expected = array(
			'a' => array('href' => '/members/posts/index?page=2&amp;sort=name&amp;direction=asc'),
			'Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('next', array('url' => $options));
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('href' => '/members/posts/index?page=3', 'rel' => 'next'),
			'next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('prev', array('url' => $options));
		$expected = array(
			'li' => array('class' => 'prev'),
			'a' => array('href' => '/members/posts/index', 'rel' => 'prev'),
			'prev',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$options = array('prefix' => 'members', 'controller' => 'posts', 'sort' => 'name', 'direction' => 'desc');
		$result = $this->Paginator->generateUrl($options);
		$expected = '/members/posts/index?page=2&amp;sort=name&amp;direction=desc';
		$this->assertEquals($expected, $result);

		$options = array('controller' => 'posts', 'sort' => 'Article.name', 'direction' => 'desc');
		$result = $this->Paginator->generateUrl($options);
		$expected = '/posts/index?page=2&amp;sort=Article.name&amp;direction=desc';
		$this->assertEquals($expected, $result);
	}

/**
 * testOptions method
 *
 * @return void
 */
	public function testOptions() {
		$this->Paginator->options = array();
		$this->Paginator->request->params = array();

		$options = array('paging' => array('Article' => array(
			'direction' => 'desc',
			'sort' => 'title'
		)));
		$this->Paginator->options($options);

		$expected = array('Article' => array(
			'direction' => 'desc',
			'sort' => 'title'
		));
		$this->assertEquals($expected, $this->Paginator->request->params['paging']);

		$this->Paginator->options = array();
		$this->Paginator->request->params = array();

		$options = array('Article' => array(
			'direction' => 'desc',
			'sort' => 'title'
		));
		$this->Paginator->options($options);
		$this->assertEquals($expected, $this->Paginator->request->params['paging']);

		$options = array('paging' => array('Article' => array(
			'direction' => 'desc',
			'sort' => 'Article.title'
		)));
		$this->Paginator->options($options);

		$expected = array('Article' => array(
			'direction' => 'desc',
			'sort' => 'Article.title'
		));
		$this->assertEquals($expected, $this->Paginator->request->params['paging']);
	}

/**
 * testPassedArgsMergingWithUrlOptions method
 *
 * @return void
 */
	public function testPassedArgsMergingWithUrlOptions() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'pass' => array('2')),
			array('base' => '/', 'here' => '/articles/', 'webroot' => '/')
		));
		$this->Paginator->request->params['paging'] = array(
			'Article' => array(
				'page' => 1, 'current' => 3, 'count' => 13,
				'prevPage' => false, 'nextPage' => true, 'pageCount' => 8,
				'sort' => null, 'direction' => null,
			)
		);

		$this->Paginator->request->params['pass'] = array(2);
		$this->Paginator->request->query = array('page' => 1, 'foo' => 'bar', 'x' => 'y');
		$this->Paginator->beforeRender(null, 'posts/index');

		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/articles/index/2?foo=bar&amp;x=y&amp;sort=title&amp;direction=asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers();
		$expected = array(
			array('li' => array('class' => 'active')), '<span', '1', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/articles/index/2?page=2&amp;foo=bar&amp;x=y')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/articles/index/2?page=3&amp;foo=bar&amp;x=y')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/articles/index/2?page=4&amp;foo=bar&amp;x=y')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/articles/index/2?page=5&amp;foo=bar&amp;x=y')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/articles/index/2?page=6&amp;foo=bar&amp;x=y')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/articles/index/2?page=7&amp;foo=bar&amp;x=y')), '7', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('href' => '/articles/index/2?page=2&amp;foo=bar&amp;x=y', 'rel' => 'next'),
			'Next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that generated URLs don't include sort and direction parameters
 *
 * @return void
 */
	public function testDefaultSortRemovedFromUrl() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'articles', 'action' => 'index'),
			array('base' => '/', 'here' => '/articles/', 'webroot' => '/')
		));
		$this->Paginator->request->params['paging'] = array(
			'Article' => array(
				'page' => 1, 'current' => 3, 'count' => 13,
				'prevPage' => false, 'nextPage' => true, 'pageCount' => 8,
				'sort' => 'Article.title', 'direction' => 'ASC',
				'sortDefault' => 'Article.title', 'directionDefault' => 'ASC'
			)
		);
		$result = $this->Paginator->next('Next');
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('rel' => 'next', 'href' => '/articles/index?page=2'),
			'Next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test the prev() method.
 *
 * @return void
 */
	public function testPrev() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
			)
		);
		$result = $this->Paginator->prev('<< Previous');
		$expected = array(
			'li' => array('class' => 'prev disabled'),
			'span' => array(),
			'&lt;&lt; Previous',
			'/span',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', ['disabledTitle' => 'Prev']);
		$expected = array(
			'li' => array('class' => 'prev disabled'),
			'span' => array(),
			'Prev',
			'/span',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', ['disabledTitle' => false]);
		$this->assertEquals('', $result, 'disabled + no text = no link');

		$this->Paginator->request->params['paging']['Client']['page'] = 2;
		$this->Paginator->request->params['paging']['Client']['prevPage'] = true;
		$result = $this->Paginator->prev('<< Previous');
		$expected = array(
			'li' => array('class' => 'prev'),
			'a' => array('href' => '/index', 'rel' => 'prev'),
			'&lt;&lt; Previous',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that prev() and the shared implementation underneath picks up from optins
 *
 * @return void
 */
	public function testPrevWithOptions() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 2, 'current' => 1, 'count' => 13, 'prevPage' => true,
				'nextPage' => false, 'pageCount' => 2,
				'limit' => 10,
			)
		);
		$this->Paginator->options(array('url' => array(12, 'page' => 3)));
		$result = $this->Paginator->prev('Prev', array('url' => array('foo' => 'bar')));
		$expected = array(
			'li' => array('class' => 'prev'),
			'a' => array('href' => '/index/12?limit=10&amp;foo=bar', 'rel' => 'prev'),
			'Prev',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test the next() method.
 *
 * @return void
 */
	public function testNext() {
		$result = $this->Paginator->next('Next >>');
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('href' => '/index?page=2', 'rel' => 'next'),
			'Next &gt;&gt;',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next >>', ['escape' => false]);
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('href' => '/index?page=2', 'rel' => 'next'),
			'preg:/Next >>/',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test next() with disabled links
 *
 * @return void
 */
	public function testNextDisabled() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 5,
				'current' => 3,
				'count' => 13,
				'prevPage' => true,
				'nextPage' => false,
				'pageCount' => 5,
			)
		);
		$result = $this->Paginator->next('Next >>');
		$expected = array(
			'li' => array('class' => 'next disabled'),
			'span' => array(),
			'Next &gt;&gt;',
			'/span',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next >>', ['disabledTitle' => 'Next']);
		$expected = array(
			'li' => array('class' => 'next disabled'),
			'span' => array(),
			'Next',
			'/span',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next >>', ['disabledTitle' => false]);
		$this->assertEquals('', $result, 'disabled + no text = no link');
	}

/**
 * Test next() with a model argument.
 *
 * @return void
 */
	public function testNextAndPrevNonDefaultModel() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
			),
			'Server' => array(
				'page' => 5,
				'current' => 1,
				'count' => 5,
				'prevPage' => true,
				'nextPage' => false,
				'pageCount' => 5,
			)
		);
		$result = $this->Paginator->next('Next', [
			'model' => 'Client'
		]);
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('href' => '/index?page=2', 'rel' => 'next'),
			'Next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('Prev', [
			'model' => 'Client'
		]);
		$expected = '<li class="prev disabled"><span>Prev</span></li>';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->next('Next', [
			'model' => 'Server'
		]);
		$expected = '<li class="next disabled"><span>Next</span></li>';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->prev('Prev', [
			'model' => 'Server'
		]);
		$expected = array(
			'li' => array('class' => 'prev'),
			'a' => array('href' => '/index?page=4', 'rel' => 'prev'),
			'Prev',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testNumbers method
 *
 * @return void
 */
	public function testNumbers() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 8,
				'current' => 3,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 15,
			)
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '8', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 'first', 'last' => 'last'));
		$expected = array(
			array('li' => array('class' => 'first')), array('a' => array('href' => '/index')), 'first', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '8', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array('class' => 'last')), array('a' => array('href' => '/index?page=15')), 'last', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 15,
			)
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('li' => array('class' => 'active')), '<span', '1', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 14,
				'current' => 3,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 15,
			)
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=13')), '13', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '14', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=15')), '15', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 2,
				'current' => 3,
				'count' => 27,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 9,
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '2', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('last' => 1));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '2', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 15,
				'current' => 3,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 15,
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=13')), '13', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=14')), '14', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '15', '/span', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 10,
				'current' => 3,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 15,
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '10', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=13')), '13', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=14')), '14', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=15')), '15', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 6,
				'current' => 15,
				'count' => 623,
				'prevPage' => 1,
				'nextPage' => 1,
				'pageCount' => 42,
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '6', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=42')), '42', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 37,
				'current' => 15,
				'count' => 623,
				'prevPage' => 1,
				'nextPage' => 1,
				'pageCount' => 42,
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=33')), '33', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=34')), '34', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=35')), '35', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=36')), '36', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '37', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=38')), '38', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=39')), '39', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=40')), '40', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=41')), '41', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=42')), '42', '/a', '/li',
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test modulus option for numbers()
 *
 * @return void
 */
	public function testNumbersModulus() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 10,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 3,
			)
		);
		$options = array('modulus' => 10);
		$result = $this->Paginator->numbers($options);
		$expected = array(
			array('li' => array('class' => 'active')), '<span', '1', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('modulus' => 3));
		$expected = array(
			array('li' => array('class' => 'active')), '<span', '1', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 4895,
				'current' => 10,
				'count' => 48962,
				'prevPage' => 1,
				'nextPage' => 1,
				'pageCount' => 4897,
			)
		);

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '4895', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 3;

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '3', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '3', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 5, 'last' => 5));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '3', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4893')), '4893', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 4893;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4891')), '4891', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4892')), '4892', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '4893', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 58;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=56')), '56', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=57')), '57', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '58', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=59')), '59', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=60')), '60', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4893')), '4893', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 5;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '5', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4893')), '4893', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 3;
		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index')), '1', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '3', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array('class' => 'ellipsis')), '...', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/li',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test numbers() with routing parameters.
 *
 * @return void
 */
	public function testNumbersRouting() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 2,
				'current' => 2,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 3,
				'pageCount' => 3,
			)
		);

		$request = new Request();
		$request->addParams(array(
			'controller' => 'clients', 'action' => 'index', 'plugin' => null
		));
		$request->base = '';
		$request->here = '/clients/index?page=2';
		$request->webroot = '/';

		Router::setRequestInfo($request);

		$result = $this->Paginator->numbers();
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/clients/index')), '1', '/a', '/li',
			array('li' => array('class' => 'active')), '<span', '2', '/span', '/li',
			array('li' => array()), array('a' => array('href' => '/clients/index?page=3')), '3', '/a', '/li',
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that numbers() works with the non default model.
 *
 * @return void
 */
	public function testNumbersNonDefaultModel() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
			),
			'Server' => array(
				'page' => 5,
				'current' => 1,
				'count' => 5,
				'prevPage' => true,
				'nextPage' => false,
				'pageCount' => 5,
			)
		);
		$result = $this->Paginator->numbers(['model' => 'Server']);
		$this->assertContains('<li class="active"><span>5</span></li>', $result);
		$this->assertNotContains('<li class="active"><span>1</span></li>', $result);

		$result = $this->Paginator->numbers(['model' => 'Client']);
		$this->assertContains('<li class="active"><span>1</span></li>', $result);
		$this->assertNotContains('<li class="active"><span>5</span></li>', $result);
	}

/**
 * test first() and last() with tag options
 *
 * @return void
 */
	public function testFirstAndLastTag() {
		$this->Paginator->request->params['paging']['Article']['page'] = 2;
		$result = $this->Paginator->first('<<');
		$expected = array(
			'li' => array('class' => 'first'),
			'a' => array('href' => '/index'),
			'&lt;&lt;',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2);
		$expected = array(
			'<li',
			array('a' => array('href' => '/index?page=6')), '6', '/a',
			'/li',
			'<li',
			array('a' => array('href' => '/index?page=7')), '7', '/a',
			'/li',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that on the last page you don't get a link ot the last page.
 *
 * @return void
 */
	public function testLastNoOutput() {
		$this->Paginator->request->params['paging']['Article']['page'] = 15;
		$this->Paginator->request->params['paging']['Article']['pageCount'] = 15;

		$result = $this->Paginator->last();
		$expected = '';
		$this->assertEquals($expected, $result);
	}

/**
 * test first() with a the model parameter.
 *
 * @return void
 */
	public function testFirstNonDefaultModel() {
		$this->Paginator->request->params['paging']['Article']['page'] = 1;
		$this->Paginator->request->params['paging']['Client'] = array(
			'page' => 3,
			'current' => 3,
			'count' => 13,
			'prevPage' => false,
			'nextPage' => true,
			'pageCount' => 5,
		);

		$result = $this->Paginator->first('first', ['model' => 'Article']);
		$this->assertEquals('', $result);

		$result = $this->Paginator->first('first', ['model' => 'Client']);
		$expected = array(
			'li' => array('class' => 'first'),
			'a' => array('href' => '/index'),
			'first',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test first() on the first page.
 *
 * @return void
 */
	public function testFirstEmpty() {
		$this->Paginator->request->params['paging']['Article']['page'] = 1;

		$result = $this->Paginator->first();
		$expected = '';
		$this->assertEquals($expected, $result);
	}

/**
 * test first() and options()
 *
 * @return void
 */
	public function testFirstFullBaseUrl() {
		$this->Paginator->request->params['paging']['Article']['page'] = 3;
		$this->Paginator->request->params['paging']['Article']['direction'] = 'DESC';
		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';

		$this->Paginator->options(array('url' => array('_full' => true)));

		$result = $this->Paginator->first();
		$expected = array(
			'li' => ['class' => 'first'],
			array('a' => array(
				'href' => Configure::read('App.fullBaseUrl') . '/index?sort=Article.title&amp;direction=DESC'
			)),
			'&lt;&lt; first',
			'/a',
			'/li',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test first() on the fence-post
 *
 * @return void
 */
	public function testFirstBoundaries() {
		$this->Paginator->request->params['paging']['Article']['page'] = 3;
		$result = $this->Paginator->first();
		$expected = array(
			'li' => ['class' => 'first'],
			'a' => array('href' => '/index'),
			'&lt;&lt; first',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->first(2);
		$expected = array(
			'<li',
			array('a' => array('href' => '/index')), '1', '/a',
			'/li',
			'<li',
			array('a' => array('href' => '/index?page=2')), '2', '/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['page'] = 2;
		$result = $this->Paginator->first(3);
		$this->assertEquals('', $result, 'When inside the first links range, no links should be made');
	}

/**
 * test params() method
 *
 * @return void
 */
	public function testParams() {
		$result = $this->Paginator->params();
		$this->assertArrayHasKey('page', $result);
		$this->assertArrayHasKey('pageCount', $result);

		$result = $this->Paginator->params('Nope');
		$this->assertEquals([], $result);
	}

/**
 * test param() method
 *
 * @return void
 */
	public function testParam() {
		$result = $this->Paginator->param('count');
		$this->assertSame(62, $result);

		$result = $this->Paginator->param('imaginary');
		$this->assertNull($result);
	}

/**
 * test last() method
 *
 * @return void
 */
	public function testLast() {
		$result = $this->Paginator->last();
		$expected = array(
			'li' => ['class' => 'last'],
			'a' => array('href' => '/index?page=7'),
			'last &gt;&gt;',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = array(
			'<li',
			'a' => array('href' => '/index?page=7'),
			'7',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['page'] = 6;

		$result = $this->Paginator->last(2);
		$expected = array(
			'<li',
			array('a' => array('href' => '/index?page=6')), '6', '/a',
			'/li',
			'<li',
			array('a' => array('href' => '/index?page=7')), '7', '/a',
			'/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(3);
		$this->assertEquals('', $result, 'When inside the last links range, no links should be made');
	}

/**
 * test the options for last()
 *
 * @return void
 */
	public function testLastOptions() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 4,
				'current' => 3,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 15,
				'sort' => 'Client.name',
				'direction' => 'DESC',
			)
		);

		$result = $this->Paginator->last();
		$expected = array(
			'li' => ['class' => 'last'],
			'a' => array(
				'href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC',
			),
				'last &gt;&gt;', '/a',
			'/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = array(
			'<li',
			array('a' => array('href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC')), '15', '/a',
			'/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2);
		$expected = array(
			'<li',
			array('a' => array('href' => '/index?page=14&amp;sort=Client.name&amp;direction=DESC')), '14', '/a',
			'/li',
			'<li',
			array('a' => array('href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC')), '15', '/a',
			'/li',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test last() with a the model parameter.
 *
 * @return void
 */
	public function testLastNonDefaultModel() {
		$this->Paginator->request->params['paging']['Article']['page'] = 7;
		$this->Paginator->request->params['paging']['Client'] = array(
			'page' => 3,
			'current' => 3,
			'count' => 13,
			'prevPage' => false,
			'nextPage' => true,
			'pageCount' => 5,
		);

		$result = $this->Paginator->last('last', ['model' => 'Article']);
		$this->assertEquals('', $result);

		$result = $this->Paginator->last('last', ['model' => 'Client']);
		$expected = array(
			'li' => array('class' => 'last'),
			'a' => array('href' => '/index?page=5'),
			'last',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testCounter method
 *
 * @return void
 */
	public function testCounter() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'perPage' => 3,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'limit' => 3,
				'sort' => 'Client.name',
				'order' => 'DESC',
			)
		);
		$input = 'Page {{page}} of {{pages}}, showing {{current}} records out of {{count}} total, ';
		$input .= 'starting on record {{start}}, ending on {{end}}';

		$expected = 'Page 1 of 5, showing 3 records out of 13 total, starting on record 1, ';
		$expected .= 'ending on 3';
		$result = $this->Paginator->counter($input);
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->counter(array('format' => 'pages'));
		$expected = '1 of 5';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->counter(array('format' => 'range'));
		$expected = '1 - 3 of 13';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->counter('Showing {{page}} of {{pages}} {{model}}');
		$this->assertEquals('Showing 1 of 5 clients', $result);
	}

/**
 * testHasPage method
 *
 * @return void
 */
	public function testHasPage() {
		$result = $this->Paginator->hasPage('Article', 15);
		$this->assertFalse($result);

		$result = $this->Paginator->hasPage('UndefinedModel', 2);
		$this->assertFalse($result);

		$result = $this->Paginator->hasPage('Article', 2);
		$this->assertTrue($result);

		$result = $this->Paginator->hasPage(2);
		$this->assertTrue($result);
	}

/**
 * testNextLinkUsingDotNotation method
 *
 * @return void
 */
	public function testNextLinkUsingDotNotation() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array()),
			array('base' => '', 'here' => '/accounts/', 'webroot' => '/')
		));

		$this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
		$this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
		$this->Paginator->request->params['paging']['Article']['page'] = 1;

		$test = array('url' => array(
			'page' => '1',
			'sort' => 'Article.title',
			'direction' => 'asc',
		));
		$this->Paginator->options($test);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array(
				'href' => '/accounts/index?page=2&amp;sort=Article.title&amp;direction=asc',
				'rel' => 'next'
			),
			'Next',
			'/a',
			'/li',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test the current() method
 *
 * @return void
 */
	public function testCurrent() {
		$result = $this->Paginator->current();
		$this->assertEquals($this->Paginator->request->params['paging']['Article']['page'], $result);

		$result = $this->Paginator->current('Incorrect');
		$this->assertEquals(1, $result);
	}

/**
 * test the defaultModel() method
 *
 * @return void
 */
	public function testNoDefaultModel() {
		$this->Paginator->request = new Request();
		$this->assertNull($this->Paginator->defaultModel());
	}

/**
 * test the numbers() method when there is only one page
 *
 * @return void
 */
	public function testWithOnePage() {
		$this->Paginator->request['paging'] = array(
			'Article' => array(
				'page' => 1,
				'current' => 2,
				'count' => 2,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 1,
			)
		);
		$this->assertFalse($this->Paginator->numbers());
		$this->assertFalse($this->Paginator->first());
		$this->assertFalse($this->Paginator->last());
	}

/**
 * test the numbers() method when there is only one page
 *
 * @return void
 */
	public function testWithZeroPages() {
		$this->Paginator->request['paging'] = array(
			'Article' => array(
				'page' => 0,
				'current' => 0,
				'count' => 0,
				'perPage' => 10,
				'prevPage' => false,
				'nextPage' => false,
				'pageCount' => 0,
				'limit' => 10,
			)
		);

		$result = $this->Paginator->counter(array('format' => 'pages'));
		$expected = '0 of 1';
		$this->assertEquals($expected, $result);
	}
}
