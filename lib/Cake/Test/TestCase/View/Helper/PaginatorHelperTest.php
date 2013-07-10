<?php
/**
 * PaginatorHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\FormHelper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\Helper\JsHelper;
use Cake\View\Helper\PaginatorHelper;
use Cake\View\View;

if (!defined('FULL_BASE_URL')) {
	define('FULL_BASE_URL', 'http://cakephp.org');
}

/**
 * PaginatorHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
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
		$controller = null;
		$this->View = new View($controller);
		$this->Paginator = new PaginatorHelper($this->View);
		$this->Paginator->Js = $this->getMock('Cake\View\Helper\PaginatorHelper', array(), array($this->View));
		$this->Paginator->request = new Request();
		$this->Paginator->request->addParams(array(
			'paging' => array(
				'Article' => array(
					'page' => 2,
					'current' => 9,
					'count' => 62,
					'prevPage' => false,
					'nextPage' => true,
					'pageCount' => 7,
					'order' => null,
					'limit' => 20,
					'options' => array(
						'page' => 1,
						'conditions' => array()
					),
				)
			)
		));
		$this->Paginator->Html = new HtmlHelper($this->View);

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
		unset($this->View, $this->Paginator);
	}

/**
 * testHasPrevious method
 *
 * @return void
 */
	public function testHasPrevious() {
		$this->assertSame($this->Paginator->hasPrev(), false);
		$this->Paginator->request->params['paging']['Article']['prevPage'] = true;
		$this->assertSame($this->Paginator->hasPrev(), true);
		$this->Paginator->request->params['paging']['Article']['prevPage'] = false;
	}

/**
 * testHasNext method
 *
 * @return void
 */
	public function testHasNext() {
		$this->assertSame($this->Paginator->hasNext(), true);
		$this->Paginator->request->params['paging']['Article']['nextPage'] = false;
		$this->assertSame($this->Paginator->hasNext(), false);
		$this->Paginator->request->params['paging']['Article']['nextPage'] = true;
	}

/**
 * testDisabledLink method
 *
 * @return void
 */
	public function testDisabledLink() {
		$this->Paginator->request->params['paging']['Article']['nextPage'] = false;
		$this->Paginator->request->params['paging']['Article']['page'] = 1;
		$result = $this->Paginator->next('Next', array(), true);
		$expected = '<span class="next">Next</span>';
		$this->assertEquals($expected, $result);

		$this->Paginator->request->params['paging']['Article']['prevPage'] = false;
		$result = $this->Paginator->prev('prev', array('url' => array('controller' => 'posts')), null, array('class' => 'disabled', 'tag' => 'span'));
		$expected = array(
			'span' => array('class' => 'disabled'), 'prev', '/span'
		);
		$this->assertTags($result, $expected);
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
				'options' => array(
					'page' => 1,
					'order' => array('date' => 'asc'),
					'conditions' => array()
				),
			)
		);

		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('date');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=date&amp;direction=desc', 'class' => 'asc'),
			'Date',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', 'TestTitle');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=asc'),
			'TestTitle',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', array('asc' => 'ascending', 'desc' => 'descending'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=asc'),
			'ascending',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['sort'] = 'title';
		$result = $this->Paginator->sort('title', array('asc' => 'ascending', 'desc' => 'descending'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=desc', 'class' => 'asc'),
			'descending',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$this->Paginator->request->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->request->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$this->Paginator->request->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$this->Paginator->request->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'asc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->request->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'asc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->request->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->request->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title', 'Title', array('direction' => 'desc', 'class' => 'foo'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=desc', 'class' => 'foo asc'),
			'Title',
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
		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('full_name' => 'asc');

		$result = $this->Paginator->sort('Article.full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=Article.full_name&amp;direction=desc', 'class' => 'asc'),
			'Article Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=full_name&amp;direction=desc', 'class' => 'asc'),
			'Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('full_name' => 'desc');
		$result = $this->Paginator->sort('Article.full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=Article.full_name&amp;direction=asc', 'class' => 'desc'),
			'Article Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=full_name&amp;direction=asc', 'class' => 'desc'),
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
			array('base' => '/', 'here' => '/accounts/', 'webroot' => '/',)
		));
		$this->Paginator->options(array('url' => array('param')));

		$result = $this->Paginator->sort('title', 'TestTitle', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=desc'),
			'TestTitle',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('title', array('asc' => 'ascending', 'desc' => 'descending'), array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param?page=1&amp;sort=title&amp;direction=desc'),
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

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$result = $this->Paginator->sort('Article.title');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=Article.title&amp;direction=asc', 'class' => 'desc'),
			'Article Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$result = $this->Paginator->sort('Article.title', 'Title');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=Article.title&amp;direction=asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$result = $this->Paginator->sort('Article.title', 'Title');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=Article.title&amp;direction=desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Account.title' => 'asc');
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/accounts/index?page=1&amp;sort=title&amp;direction=asc'),
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
		$result = $this->Paginator->sortKey(null, array(
			'order' => array('Article.title' => 'desc'
		)));
		$this->assertEquals('Article.title', $result);

		$result = $this->Paginator->sortKey('Article', array('order' => 'Article.title'));
		$this->assertEquals('Article.title', $result);

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
		$this->Paginator->request->params['paging']['Article']['order'] = 'Article.body';
		$result = $this->Paginator->sortKey();
		$this->assertEquals('Article.body', $result);

		$result = $this->Paginator->sortKey('Article');
		$this->assertEquals('Article.body', $result);

		$this->Paginator->request->params['paging']['Article']['order'] = array(
			'Article.body' => 'DESC'
		);
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

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$result = $this->Paginator->sortDir();
		$expected = 'desc';

		$this->assertEquals($expected, $result);

		unset($this->Paginator->request->params['paging']['Article']['options']);
		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$result = $this->Paginator->sortDir();
		$expected = 'asc';

		$this->assertEquals($expected, $result);

		unset($this->Paginator->request->params['paging']['Article']['options']);
		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('title' => 'desc');
		$result = $this->Paginator->sortDir();
		$expected = 'desc';

		$this->assertEquals($expected, $result);

		unset($this->Paginator->request->params['paging']['Article']['options']);
		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('title' => 'asc');
		$result = $this->Paginator->sortDir();
		$expected = 'asc';

		$this->assertEquals($expected, $result);

		unset($this->Paginator->request->params['paging']['Article']['options']);
		$this->Paginator->request->params['paging']['Article']['options']['direction'] = 'asc';
		$result = $this->Paginator->sortDir();
		$expected = 'asc';

		$this->assertEquals($expected, $result);

		unset($this->Paginator->request->params['paging']['Article']['options']);
		$this->Paginator->request->params['paging']['Article']['options']['direction'] = 'desc';
		$result = $this->Paginator->sortDir();
		$expected = 'desc';

		$this->assertEquals($expected, $result);

		unset($this->Paginator->request->params['paging']['Article']['options']);
		$result = $this->Paginator->sortDir('Article', array('direction' => 'asc'));
		$expected = 'asc';

		$this->assertEquals($expected, $result);

		$result = $this->Paginator->sortDir('Article', array('direction' => 'desc'));
		$expected = 'desc';

		$this->assertEquals($expected, $result);

		$result = $this->Paginator->sortDir('Article', array('direction' => 'asc'));
		$expected = 'asc';

		$this->assertEquals($expected, $result);
	}

/**
 * Test that sortDir falls back to the default sorting options set
 * in the $params which are the default pagination options.
 *
 * @return void
 */
	public function testSortDirFallbackToParams() {
		$this->Paginator->request->params['paging']['Article']['order'] = array(
			'Article.body' => 'ASC'
		);
		$result = $this->Paginator->sortDir();
		$this->assertEquals('asc', $result);

		$result = $this->Paginator->sortDir('Article');
		$this->assertEquals('asc', $result);

		$this->Paginator->request->params['paging']['Article']['order'] = array(
			'Article.body' => 'DESC'
		);
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
			'span' => array('class' => 'next'),
			'a' => array('href' => '/admin/users/index?page=2', 'rel' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/admin/users/index/param?page=1&amp;sort=title&amp;direction=asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('Article.title', 'Title');
		$expected = array(
			'a' => array('href' => '/admin/users/index/param?page=1&amp;sort=Article.title&amp;direction=asc'),
			'Title',
			'/a'
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
			'a' => array('href' => '/index?page=1&amp;sort=controller&amp;direction=asc'),
			'Controller',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->url();
		$this->assertEquals('/index?page=1', $result);

		$this->Paginator->request->params['paging']['Article']['options']['page'] = 2;
		$result = $this->Paginator->url();
		$this->assertEquals('/index?page=2', $result);

		$options = array('order' => array('Article' => 'desc'));
		$result = $this->Paginator->url($options);
		$this->assertEquals('/index?page=2&amp;sort=Article&amp;direction=desc', $result);

		$this->Paginator->request->params['paging']['Article']['options']['page'] = 3;
		$options = array('order' => array('Article.name' => 'desc'));
		$result = $this->Paginator->url($options);
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

		$this->Paginator->request->params['paging']['Article']['options']['page'] = 2;
		$this->Paginator->request->params['paging']['Article']['page'] = 2;
		$this->Paginator->request->params['paging']['Article']['prevPage'] = true;
		$options = array('prefix' => 'members');

		$result = $this->Paginator->url($options);
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
			'span' => array('class' => 'next'),
			'a' => array('href' => '/members/posts/index?page=3', 'rel' => 'next'),
			'next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('prev', array('url' => $options));
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array('href' => '/members/posts/index?page=1', 'rel' => 'prev'),
			'prev',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$options = array('prefix' => 'members', 'controller' => 'posts', 'order' => array('name' => 'desc'));
		$result = $this->Paginator->url($options);
		$expected = '/members/posts/index?page=2&amp;sort=name&amp;direction=desc';
		$this->assertEquals($expected, $result);

		$options = array('controller' => 'posts', 'order' => array('Article.name' => 'desc'));
		$result = $this->Paginator->url($options);
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
			'order' => 'desc',
			'sort' => 'title'
		)));
		$this->Paginator->options($options);

		$expected = array('Article' => array(
			'order' => 'desc',
			'sort' => 'title'
		));
		$this->assertEquals($expected, $this->Paginator->request->params['paging']);

		$this->Paginator->options = array();
		$this->Paginator->request->params = array();

		$options = array('Article' => array(
			'order' => 'desc',
			'sort' => 'title'
		));
		$this->Paginator->options($options);
		$this->assertEquals($expected, $this->Paginator->request->params['paging']);

		$options = array('paging' => array('Article' => array(
			'order' => 'desc',
			'sort' => 'Article.title'
		)));
		$this->Paginator->options($options);

		$expected = array('Article' => array(
			'order' => 'desc',
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
				'options' => array(
					'page' => 1,
					'order' => array(),
					'conditions' => array()
				),
			)
		);

		$this->Paginator->request->params['pass'] = array(2);
		$this->Paginator->request->query = array('foo' => 'bar', 'x' => 'y');
		$this->Paginator->beforeRender(null, 'posts/index');

		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/articles/index/2?page=1&amp;sort=title&amp;direction=asc&amp;foo=bar&amp;x=y'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array('class' => 'current')), '1', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2?page=2&amp;foo=bar&amp;x=y')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2?page=3&amp;foo=bar&amp;x=y')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2?page=4&amp;foo=bar&amp;x=y')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2?page=5&amp;foo=bar&amp;x=y')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2?page=6&amp;foo=bar&amp;x=y')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2?page=7&amp;foo=bar&amp;x=y')), '7', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'span' => array('class' => 'next'),
			'a' => array('href' => '/articles/index/2?page=2&amp;foo=bar&amp;x=y', 'rel' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testPagingLinks method
 *
 * @return void
 */
	public function testPagingLinks() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'options' => array(
					'page' => 1,
				),
			)
		);
		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled'));
		$expected = array(
			'span' => array('class' => 'disabled'),
			'&lt;&lt; Previous',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled', 'tag' => 'div'));
		$expected = array(
			'div' => array('class' => 'disabled'),
			'&lt;&lt; Previous',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 2;
		$this->Paginator->request->params['paging']['Client']['prevPage'] = true;
		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled'));
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array('href' => '/index?page=1', 'rel' => 'prev'),
			'&lt;&lt; Previous',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', array('tag' => false), null, array('class' => 'disabled'));
		$expected = array(
			'a' => array('href' => '/index?page=1', 'rel' => 'prev', 'class' => 'prev'),
			'&lt;&lt; Previous',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev(
			'<< Previous',
			array(),
			null,
			array('disabledTag' => 'span', 'class' => 'disabled')
		);
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array('href' => '/index?page=1', 'rel' => 'prev'),
			'&lt;&lt; Previous',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'span' => array('class' => 'next'),
			'a' => array('href' => '/index?page=3', 'rel' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next', array('tag' => 'li'));
		$expected = array(
			'li' => array('class' => 'next'),
			'a' => array('href' => '/index?page=3', 'rel' => 'next'),
			'Next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next', array('tag' => false));
		$expected = array(
			'a' => array('href' => '/index?page=3', 'rel' => 'next', 'class' => 'next'),
			'Next',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', array('escape' => true));
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array('href' => '/index?page=1', 'rel' => 'prev'),
			'&lt;&lt; Previous',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', array('escape' => false));
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array('href' => '/index?page=1', 'rel' => 'prev'),
			'preg:/<< Previous/',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 1,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'options' => array(
					'page' => 1,
				),
			)
		);

		$result = $this->Paginator->prev('<< Previous', null, '<strong>Disabled</strong>');
		$expected = array(
			'span' => array('class' => 'prev'),
			'&lt;strong&gt;Disabled&lt;/strong&gt;',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', null, '<strong>Disabled</strong>', array('escape' => true));
		$expected = array(
			'span' => array('class' => 'prev'),
			'&lt;strong&gt;Disabled&lt;/strong&gt;',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', null, '<strong>Disabled</strong>', array('escape' => false));
		$expected = array(
			'span' => array('class' => 'prev'),
			'<strong', 'Disabled', '/strong',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', array('tag' => false), '<strong>Disabled</strong>');
		$expected = array(
			'span' => array('class' => 'prev'),
			'&lt;strong&gt;Disabled&lt;/strong&gt;',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev(
			'<< Previous',
			array('tag' => 'li'),
			null,
			array('tag' => 'li', 'disabledTag' => 'span', 'class' => 'disabled')
		);
		$expected = array(
			'li' => array('class' => 'disabled'),
			'span' => array(),
			'&lt;&lt; Previous',
			'/span',
			'/li'
		);
		$this->assertTags($result, $expected);
		$result = $this->Paginator->prev(
			'<< Previous',
			array(),
			null,
			array('tag' => false, 'disabledTag' => 'span', 'class' => 'disabled')
		);
		$expected = array(
			'span' => array('class' => 'disabled'),
			'&lt;&lt; Previous',
			'/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'options' => array(
					'page' => 1,
					'limit' => 3,
					'order' => array('Client.name' => 'DESC'),
				),
			)
		);

		$this->Paginator->request->params['paging']['Client']['page'] = 2;
		$this->Paginator->request->params['paging']['Client']['prevPage'] = true;
		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled'));
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array(
				'href' => '/index?page=1&amp;limit=3&amp;sort=Client.name&amp;direction=DESC',
				'rel' => 'prev'
			),
			'&lt;&lt; Previous',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'span' => array('class' => 'next'),
			'a' => array(
				'href' => '/index?page=3&amp;limit=3&amp;sort=Client.name&amp;direction=DESC',
				'rel' => 'next'
			),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 2,
				'current' => 1,
				'count' => 13,
				'prevPage' => true,
				'nextPage' => false,
				'pageCount' => 2,
				'options' => array(
					'page' => 2,
					'limit' => 10,
					'order' => array(),
					'conditions' => array()
				),
			)
		);
		$result = $this->Paginator->prev('Prev');
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array('href' => '/index?page=1&amp;limit=10', 'rel' => 'prev'),
			'Prev',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next', array(), null, array('tag' => false));
		$expected = array(
			'span' => array('class' => 'next'),
			'Next',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 2, 'current' => 1, 'count' => 13, 'prevPage' => true,
				'nextPage' => false, 'pageCount' => 2,
				'defaults' => array(),
				'options' => array(
					'page' => 2, 'limit' => 10, 'order' => array(), 'conditions' => array()
				),
			)
		);
		$this->Paginator->options(array('url' => array(12, 'page' => 3)));
		$result = $this->Paginator->prev('Prev', array('url' => array('foo' => 'bar')));
		$expected = array(
			'span' => array('class' => 'prev'),
			'a' => array('href' => '/index/12?page=1&amp;limit=10&amp;foo=bar', 'rel' => 'prev'),
			'Prev',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that __pagingLink methods use $options when $disabledOptions is an empty value.
 * allowing you to use shortcut syntax
 *
 * @return void
 */
	public function testPagingLinksOptionsReplaceEmptyDisabledOptions() {
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'options' => array(
					'page' => 1,
				),
			)
		);
		$result = $this->Paginator->prev('<< Previous', array('escape' => false));
		$expected = array(
			'span' => array('class' => 'prev'),
			'preg:/<< Previous/',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next >>', array('escape' => false));
		$expected = array(
			'span' => array('class' => 'next'),
			'a' => array('href' => '/index?page=2', 'rel' => 'next'),
			'preg:/Next >>/',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testPagingLinksNotDefaultModel
 *
 * Test the creation of paging links when the non default model is used.
 *
 * @return void
 */
	public function testPagingLinksNotDefaultModel() {
		// Multiple Model Paginate
		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'options' => array(
					'page' => 1,
				),
			),
			'Server' => array(
				'page' => 1,
				'current' => 1,
				'count' => 5,
				'prevPage' => false,
				'nextPage' => false,
				'pageCount' => 5,
				'options' => array(
					'page' => 1,
				),
			)
		);
		$result = $this->Paginator->next('Next', array('model' => 'Client'));
		$expected = array(
			'span' => array('class' => 'next'),
			'a' => array('href' => '/index?page=2', 'rel' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next', array('model' => 'Server'), 'No Next', array('model' => 'Server'));
		$expected = array(
			'span' => array('class' => 'next'), 'No Next', '/span'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testGenericLinks method
 *
 * @return void
 */
	public function testGenericLinks() {
		$result = $this->Paginator->link('Sort by title on page 5', array('sort' => 'title', 'page' => 5, 'direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/index?page=5&amp;sort=title&amp;direction=desc'),
			'Sort by title on page 5',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['page'] = 2;
		$result = $this->Paginator->link('Sort by title', array('sort' => 'title', 'direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/index?page=2&amp;sort=title&amp;direction=desc'),
			'Sort by title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['options']['page'] = 4;
		$result = $this->Paginator->link('Sort by title on page 4', array('sort' => 'Article.title', 'direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/index?page=4&amp;sort=Article.title&amp;direction=desc'),
			'Sort by title on page 4',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests generation of generic links with preset options
 *
 * @return void
 */
	public function testGenericLinksWithPresetOptions() {
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array('a' => array('href' => '/index?page=1'), 'Foo!', '/a'));

		$this->Paginator->options(array('sort' => 'title', 'direction' => 'desc'));
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array(
			'a' => array(
				'href' => '/index?page=1',
				'sort' => 'title',
				'direction' => 'desc'
			),
			'Foo!',
			'/a'
		));

		$this->Paginator->options(array('sort' => null, 'direction' => null));
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array('a' => array('href' => '/index?page=1'), 'Foo!', '/a'));

		$this->Paginator->options(array('url' => array(
			'sort' => 'title',
			'direction' => 'desc'
		)));
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array(
			'a' => array('href' => '/index?page=1&amp;sort=title&amp;direction=desc'),
			'Foo!',
			'/a'
		));
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
				'options' => array(
					'page' => 1,
				),
			)
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '8', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('tag' => 'li'));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			' | ',
			array('li' => array('class' => 'current')), '8', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('tag' => 'li', 'separator' => false));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			array('li' => array('class' => 'current')), '8', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(true);
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1', 'rel' => 'first')), 'first', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '8', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=15', 'rel' => 'last')), 'last', '/a', '/span',
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
				'options' => array(
					'page' => 1,
				),
			)
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array('class' => 'current')), '1', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
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
				'options' => array(
					'page' => 1,
				),
			)
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=13')), '13', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '14', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=15')), '15', '/a', '/span',
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
				'options' => array(
					'page' => 1,
				),
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'class' => 'page-link'));
		$expected = array(
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current page-link')), '2', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 1, 'currentClass' => 'active'));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'active')), '2', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 1, 'tag' => 'li', 'currentClass' => 'active', 'currentTag' => 'a'));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/li',
			' | ',
			array('li' => array('class' => 'active')), array('a' => array()), '2', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 1, 'class' => 'page-link', 'currentClass' => 'active'));
		$expected = array(
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'active page-link')), '2', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '2', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
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
				'options' => array(
					'page' => 1,
				),
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=13')), '13', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=14')), '14', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '15', '/span',

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
				'options' => array(
					'page' => 1,
				),
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '10', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=13')), '13', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=14')), '14', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=15')), '15', '/a', '/span',
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
				'options' => array(
					'page' => 6,
				),
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '6', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=10')), '10', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=42')), '42', '/a', '/span',
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
				'options' => array(
					'page' => 37,
				),
			)
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=33')), '33', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=34')), '34', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=35')), '35', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=36')), '36', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '37', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=38')), '38', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=39')), '39', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=40')), '40', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=41')), '41', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=42')), '42', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 10,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 3,
				'options' => array(
					'page' => 1,
				),
			)
		);
		$options = array('modulus' => 10);
		$result = $this->Paginator->numbers($options);
		$expected = array(
			array('span' => array('class' => 'current')), '1', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('modulus' => 3, 'currentTag' => 'span', 'tag' => 'li'));
		$expected = array(
			array('li' => array('class' => 'current')), array('span' => array()), '1', '/span', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging'] = array(
			'Client' => array(
				'page' => 2,
				'current' => 10,
				'count' => 31,
				'prevPage' => true,
				'nextPage' => true,
				'pageCount' => 4,
				'options' => array(
					'page' => 1,
					'order' => array('Client.name' => 'DESC'),
				),
			)
		);
		$result = $this->Paginator->numbers(array('class' => 'page-link'));
		$expected = array(
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=1&amp;sort=Client.name&amp;direction=DESC')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current page-link')), '2', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=3&amp;sort=Client.name&amp;direction=DESC')), '3', '/a', '/span',
			' | ',
			array('span' => array('class' => 'page-link')), array('a' => array('href' => '/index?page=4&amp;sort=Client.name&amp;direction=DESC')), '4', '/a', '/span',
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
				'options' => array(
					'page' => 4894,
				),
			)
		);

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '4895', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 3;

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '3', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '3', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 5, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '3', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=4893')), '4893', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 4893;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=4891')), '4891', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4892')), '4892', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '4893', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 58;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=5')), '5', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=56')), '56', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=57')), '57', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '58', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=59')), '59', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=60')), '60', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=4893')), '4893', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 5;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=3')), '3', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '5', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=6')), '6', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=7')), '7', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index?page=4893')), '4893', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 3;
		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2, 'separator' => ' - ', 'ellipsis' => ' ~~~ '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '3', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			' ~~~ ',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Client']['page'] = 3;
		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2, 'separator' => ' - ', 'ellipsis' => '<span class="ellipsis">...</span>'));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index?page=1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=2')), '2', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '3', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4')), '4', '/a', '/span',
			array('span' => array('class' => 'ellipsis')), '...', '/span',
			array('span' => array()), array('a' => array('href' => '/index?page=4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index?page=4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test first() and last() with tag options
 *
 * @return void
 */
	public function testFirstAndLastTag() {
		$result = $this->Paginator->first('<<', array('tag' => 'li', 'class' => 'first'));
		$expected = array(
			'li' => array('class' => 'first'),
			'a' => array('href' => '/index?page=1', 'rel' => 'first'),
			'&lt;&lt;',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2, array('tag' => 'li', 'class' => 'last'));
		$expected = array(
			'...',
			'li' => array('class' => 'last'),
			array('a' => array('href' => '/index?page=6')), '6', '/a',
			'/li',
			' | ',
			array('li' => array('class' => 'last')),
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
		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'DESC');

		$this->Paginator->options(array('url' => array('_full' => true)));

		$result = $this->Paginator->first();
		$expected = array(
			'<span',
			array('a' => array(
				'href' => FULL_BASE_URL . '/index?page=1&amp;sort=Article.title&amp;direction=DESC', 'rel' => 'first'
			)),
			'&lt;&lt; first',
			'/a',
			'/span',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test first() on the fence-post
 *
 * @return void
 */
	public function testFirstBoundaries() {
		$result = $this->Paginator->first();
		$expected = array(
			'<span',
			'a' => array('href' => '/index?page=1', 'rel' => 'first'),
			'&lt;&lt; first',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->first(2);
		$expected = array(
			'<span',
			array('a' => array('href' => '/index?page=1')), '1', '/a',
			'/span',
			' | ',
			'<span',
			array('a' => array('href' => '/index?page=2')), '2', '/a',
			'/span'
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
	}

/**
 * test param() method
 *
 * @return void
 */
	public function testParam() {
		$result = $this->Paginator->param('count');
		$this->assertIdentical(62, $result);

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
			'<span',
			'a' => array('href' => '/index?page=7', 'rel' => 'last'),
			'last &gt;&gt;',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = array(
			'...',
			'<span',
			'a' => array('href' => '/index?page=7'),
			'7',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->request->params['paging']['Article']['page'] = 6;

		$result = $this->Paginator->last(2);
		$expected = array(
			'...',
			'<span',
			array('a' => array('href' => '/index?page=6')), '6', '/a',
			'/span',
			' | ',
			'<span',
			array('a' => array('href' => '/index?page=7')), '7', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(3);
		$this->assertEquals('', $result, 'When inside the last links range, no links should be made');
	}

/**
 * undocumented function
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
				'options' => array(
					'page' => 1,
					'order' => array('Client.name' => 'DESC'),
				),
			)
		);

		$result = $this->Paginator->last();
		$expected = array(
			'<span',
			array('a' => array(
				'href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC',
				'rel' => 'last'
			)),
				'last &gt;&gt;', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = array(
			'...',
			'<span',
			array('a' => array('href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC')), '15', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2);
		$expected = array(
			'...',
			'<span',
			array('a' => array('href' => '/index?page=14&amp;sort=Client.name&amp;direction=DESC')), '14', '/a',
			'/span',
			' | ',
			'<span',
			array('a' => array('href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC')), '15', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2, array('ellipsis' => '<span class="ellipsis">...</span>'));
		$expected = array(
			array('span' => array('class' => 'ellipsis')), '...', '/span',
			'<span',
			array('a' => array('href' => '/index?page=14&amp;sort=Client.name&amp;direction=DESC')), '14', '/a',
			'/span',
			' | ',
			'<span',
			array('a' => array('href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC')), '15', '/a',
			'/span',
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
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'limit' => 3,
				'options' => array(
					'page' => 1,
					'order' => array('Client.name' => 'DESC'),
				),
			)
		);
		$input = 'Page %page% of %pages%, showing %current% records out of %count% total, ';
		$input .= 'starting on record %start%, ending on %end%';
		$result = $this->Paginator->counter($input);
		$expected = 'Page 1 of 5, showing 3 records out of 13 total, starting on record 1, ';
		$expected .= 'ending on 3';
		$this->assertEquals($expected, $result);

		$input = 'Page {:page} of {:pages}, showing {:current} records out of {:count} total, ';
		$input .= 'starting on record {:start}, ending on {:end}';
		$result = $this->Paginator->counter($input);
		$this->assertEquals($expected, $result);

		$input = 'Page %page% of %pages%';
		$result = $this->Paginator->counter($input);
		$expected = 'Page 1 of 5';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->counter(array('format' => $input));
		$expected = 'Page 1 of 5';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->counter(array('format' => 'pages'));
		$expected = '1 of 5';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->counter(array('format' => 'range'));
		$expected = '1 - 3 of 13';
		$this->assertEquals($expected, $result);

		$result = $this->Paginator->counter('Showing %page% of %pages% %model%');
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
 * testWithPlugin method
 *
 * @return void
 */
	public function testWithPlugin() {
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'prefix' => null,
				'controller' => 'magazines', 'plugin' => 'my_plugin', 'action' => 'index',
			),
			array('base' => '', 'here' => '/my_plugin/magazines', 'webroot' => '/')
		));

		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/my_plugin/magazines/index?page=3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('action' => 'another_index')));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/my_plugin/magazines/another_index?page=3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('controller' => 'issues')));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/my_plugin/issues/index?page=3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('plugin' => null)));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/magazines/index?page=3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('plugin' => null, 'controller' => 'issues')));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/issues/index?page=3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);
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

		$this->Paginator->request->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->request->params['paging']['Article']['page'] = 1;

		$test = array('url' => array(
			'page' => '1',
			'sort' => 'Article.title',
			'direction' => 'asc',
		));
		$this->Paginator->options($test);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'span' => array('class' => 'next'),
			'a' => array(
				'href' => '/accounts/index?page=2&amp;sort=Article.title&amp;direction=asc',
				'rel' => 'next'
			),
			'Next',
			'/a',
			'/span',
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
				'options' => array(
					'page' => 1,
				),
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
				'prevPage' => false,
				'nextPage' => false,
				'pageCount' => 0,
				'limit' => 10,
				'options' => array(
					'page' => 0,
					'conditions' => array()
				),
			)
		);

		$result = $this->Paginator->counter(array('format' => 'pages'));
		$expected = '0 of 1';
		$this->assertEquals($expected, $result);
	}
}
