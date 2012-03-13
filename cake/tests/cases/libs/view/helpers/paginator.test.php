<?php
/**
 * PaginatorHelperTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Helper', array('Html', 'Paginator', 'Form', 'Ajax', 'Javascript', 'Js'));

Mock::generate('JsHelper', 'PaginatorMockJsHelper');

if (!defined('FULL_BASE_URL')) {
	define('FULL_BASE_URL', 'http://cakephp.org');
}

/**
 * PaginatorHelperTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class PaginatorHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Paginator = new PaginatorHelper(array('ajax' => 'Ajax'));
		$this->Paginator->params['paging'] = array(
			'Article' => array(
				'current' => 9,
				'count' => 62,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 7,
				'defaults' => array(
					'order' => array('Article.date' => 'asc'),
					'limit' => 9,
					'conditions' => array()
				),
				'options' => array(
					'order' => array('Article.date' => 'asc'),
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

		Configure::write('Routing.prefixes', array());
		Router::reload();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Paginator);
	}

/**
 * testHasPrevious method
 *
 * @access public
 * @return void
 */
	function testHasPrevious() {
		$this->assertIdentical($this->Paginator->hasPrev(), false);
		$this->Paginator->params['paging']['Article']['prevPage'] = true;
		$this->assertIdentical($this->Paginator->hasPrev(), true);
		$this->Paginator->params['paging']['Article']['prevPage'] = false;
	}

/**
 * testHasNext method
 *
 * @access public
 * @return void
 */
	function testHasNext() {
		$this->assertIdentical($this->Paginator->hasNext(), true);
		$this->Paginator->params['paging']['Article']['nextPage'] = false;
		$this->assertIdentical($this->Paginator->hasNext(), false);
		$this->Paginator->params['paging']['Article']['nextPage'] = true;
	}

/**
 * testDisabledLink method
 *
 * @access public
 * @return void
 */
	function testDisabledLink() {
		$this->Paginator->params['paging']['Article']['nextPage'] = false;
		$this->Paginator->params['paging']['Article']['page'] = 1;
		$result = $this->Paginator->next('Next', array(), true);
		$expected = '<span class="next">Next</span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging']['Article']['prevPage'] = false;
		$result = $this->Paginator->prev('prev', array('update' => 'theList', 'indicator' => 'loading', 'url' => array('controller' => 'posts')), null, array('class' => 'disabled', 'tag' => 'span'));
		$expected = array(
			'span' => array('class' => 'disabled'), 'prev', '/span'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSortLinks method
 *
 * @access public
 * @return void
 */
	function testSortLinks() {
		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array('url' => 'accounts/'), 'bare' => 0),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/', 'passedArgs' => array())
		));
		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/param/page:1/sort:title/direction:asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('date');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/param/page:1/sort:date/direction:desc', 'class' => 'asc'),
			'Date',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('modulus'=> '2', 'url'=> array('controller'=>'projects', 'action'=>'sort'),'update'=>'list'));
		$this->assertPattern('/\/projects\/sort\/page:2/', $result);
		$this->assertPattern('/<script type="text\/javascript">\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*Event.observe/', $result);

		$result = $this->Paginator->sort('TestTitle', 'title');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/param/page:1/sort:title/direction:asc'),
			'TestTitle',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort(array('asc' => 'ascending', 'desc' => 'descending'), 'title');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/param/page:1/sort:title/direction:asc'),
			'ascending',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['sort'] = 'title';
		$result = $this->Paginator->sort(array('asc' => 'ascending', 'desc' => 'descending'), 'title');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/param/page:1/sort:title/direction:desc', 'class' => 'asc'),
			'descending',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$this->Paginator->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc" class="desc">Title<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:desc" class="asc">Title<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$this->Paginator->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('Title', 'title', array('direction' => 'desc'));
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc" class="desc">Title<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$this->Paginator->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('Title', 'title', array('direction' => 'asc'));
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc" class="desc">Title<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('Title', 'title', array('direction' => 'asc'));
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:desc" class="asc">Title<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('Title', 'title', array('direction' => 'desc'));
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:desc" class="asc">Title<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->params['paging']['Article']['options']['sort'] = null;
		$result = $this->Paginator->sort('Title', 'title', array('direction' => 'desc', 'class' => 'foo'));
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:desc" class="foo asc">Title<\/a>$/', $result);
	}

/**
 * test that sort() works with virtual field order options.
 *
 * @return void
 */
	function testSortLinkWithVirtualField() {
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array('url' => 'accounts/')),
			array('base' => '', 'here' => '/accounts/', 'webroot' => '/')
		));
		$this->Paginator->params['paging']['Article']['options']['order'] = array('full_name' => 'asc');

		$result = $this->Paginator->sort('Article.full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index/page:1/sort:Article.full_name/direction:desc', 'class' => 'asc'),
			'Article.full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index/page:1/sort:full_name/direction:desc', 'class' => 'asc'),
			'Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('full_name' => 'desc');
		$result = $this->Paginator->sort('Article.full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index/page:1/sort:Article.full_name/direction:asc', 'class' => 'desc'),
			'Article.full Name',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort('full_name');
		$expected = array(
			'a' => array('href' => '/accounts/index/page:1/sort:full_name/direction:asc', 'class' => 'desc'),
			'Full Name',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSortLinksUsingDirectionOption method
 *
 * @access public
 * @return void
 */
	function testSortLinksUsingDirectionOption(){
		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(),
				'form' => array(), 'url' => array('url' => 'accounts/', 'mod_rewrite' => 'true'), 'bare' => 0),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/', 'here' => '/accounts/',
				'webroot' => '/', 'passedArgs' => array())
		));
		$this->Paginator->options(array('url' => array('param')));

		$result = $this->Paginator->sort('TestTitle', 'title', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param/page:1/sort:title/direction:desc'),
			'TestTitle',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->sort(array('asc' => 'ascending', 'desc' => 'descending'), 'title', array('direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/accounts/index/param/page:1/sort:title/direction:desc'),
			'descending',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSortLinksUsingDotNotation method
 *
 * @access public
 * @return void
 */
	function testSortLinksUsingDotNotation() {
		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(),  'form' => array(), 'url' => array('url' => 'accounts/', 'mod_rewrite' => 'true'), 'bare' => 0),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/', 'passedArgs' => array())
		));

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$result = $this->Paginator->sort('Title','Article.title');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/page:1/sort:Article.title/direction:asc', 'class' => 'desc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$result = $this->Paginator->sort('Title','Article.title');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/page:1/sort:Article.title/direction:desc', 'class' => 'asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Account.title' => 'asc');
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/officespace/accounts/index/page:1/sort:title/direction:asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSortKey method
 *
 * @access public
 * @return void
 */
	function testSortKey() {
		$result = $this->Paginator->sortKey(null, array(
			'order' => array('Article.title' => 'desc'
		)));
		$this->assertEqual('Article.title', $result);

		$result = $this->Paginator->sortKey('Article', array('sort' => 'Article.title'));
		$this->assertEqual($result, 'Article.title');

		$result = $this->Paginator->sortKey('Article', array('sort' => 'Article'));
		$this->assertEqual($result, 'Article');
	}

/**
 * testSortDir method
 *
 * @access public
 * @return void
 */
	function testSortDir() {
		$result = $this->Paginator->sortDir();
		$expected = 'asc';

		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'desc');
		$result = $this->Paginator->sortDir();
		$expected = 'desc';

		$this->assertEqual($result, $expected);

		unset($this->Paginator->params['paging']['Article']['options']);
		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$result = $this->Paginator->sortDir();
		$expected = 'asc';

		$this->assertEqual($result, $expected);

		unset($this->Paginator->params['paging']['Article']['options']);
		$this->Paginator->params['paging']['Article']['options']['order'] = array('title' => 'desc');
		$result = $this->Paginator->sortDir();
		$expected = 'desc';

		$this->assertEqual($result, $expected);

		unset($this->Paginator->params['paging']['Article']['options']);
		$this->Paginator->params['paging']['Article']['options']['order'] = array('title' => 'asc');
		$result = $this->Paginator->sortDir();
		$expected = 'asc';

		$this->assertEqual($result, $expected);

		unset($this->Paginator->params['paging']['Article']['options']);
		$this->Paginator->params['paging']['Article']['options']['direction'] = 'asc';
		$result = $this->Paginator->sortDir();
		$expected = 'asc';

		$this->assertEqual($result, $expected);

		unset($this->Paginator->params['paging']['Article']['options']);
		$this->Paginator->params['paging']['Article']['options']['direction'] = 'desc';
		$result = $this->Paginator->sortDir();
		$expected = 'desc';

		$this->assertEqual($result, $expected);

		unset($this->Paginator->params['paging']['Article']['options']);
		$result = $this->Paginator->sortDir('Article', array('direction' => 'asc'));
		$expected = 'asc';

		$this->assertEqual($result, $expected);

		$result = $this->Paginator->sortDir('Article', array('direction' => 'desc'));
		$expected = 'desc';

		$this->assertEqual($result, $expected);

		$result = $this->Paginator->sortDir('Article', array('direction' => 'asc'));
		$expected = 'asc';

		$this->assertEqual($result, $expected);
	}

/**
 * testSortAdminLinks method
 *
 * @access public
 * @return void
 */
	function testSortAdminLinks() {
		Configure::write('Routing.prefixes', array('admin'));

		Router::reload();
		Router::setRequestInfo(array(
			array('pass' => array(), 'named' => array(), 'controller' => 'users', 'plugin' => null, 'action' => 'admin_index', 'prefix' => 'admin', 'admin' => true, 'url' => array('ext' => 'html', 'url' => 'admin/users'), 'form' => array()),
			array('base' => '', 'here' => '/admin/users', 'webroot' => '/')
		));
		Router::parse('/admin/users');
		$this->Paginator->params['paging']['Article']['page'] = 1;
		$result = $this->Paginator->next('Next');
		$expected = array(
			'<span',
			'a' => array('href' => '/admin/users/index/page:2', 'class' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		Router::reload();
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'test', 'action' => 'admin_index', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/test')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/test', 'webroot' => '/')
		));
		Router::parse('/');
		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/admin/test/index/param/page:1/sort:title/direction:asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('Title', 'Article.title');
		$expected = array(
			'a' => array('href' => '/admin/test/index/param/page:1/sort:Article.title/direction:asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testUrlGeneration method
 *
 * @access public
 * @return void
 */
	function testUrlGeneration() {
		$result = $this->Paginator->sort('controller');
		$expected = array(
			'a' => array('href' => '/index/page:1/sort:controller/direction:asc'),
			'Controller',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->url();
		$this->assertEqual($result, '/index/page:1');

		$this->Paginator->params['paging']['Article']['options']['page'] = 2;
		$result = $this->Paginator->url();
		$this->assertEqual($result, '/index/page:2');

		$options = array('order' => array('Article' => 'desc'));
		$result = $this->Paginator->url($options);
		$this->assertEqual($result, '/index/page:2/sort:Article/direction:desc');

		$this->Paginator->params['paging']['Article']['options']['page'] = 3;
		$options = array('order' => array('Article.name' => 'desc'));
		$result = $this->Paginator->url($options);
		$this->assertEqual($result, '/index/page:3/sort:Article.name/direction:desc');
	}

/**
 * test URL generation with prefix routes
 *
 * @access public
 * @return void
 */
	function testUrlGenerationWithPrefixes() {
		$_back = Configure::read('Routing');

		Configure::write('Routing.prefixes', array('members'));
		Router::reload();

		Router::parse('/');

		Router::setRequestInfo( array(
			array('controller' => 'posts', 'action' => 'index', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => 'posts/index', 'webroot' => '/')
		));

		$this->Paginator->params['paging']['Article']['options']['page'] = 2;
		$this->Paginator->params['paging']['Article']['page'] = 2;
		$this->Paginator->params['paging']['Article']['prevPage'] = true;
		$options = array('members' => true);

		$result = $this->Paginator->url($options);
		$expected = '/members/posts/index/page:2';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->sort('name', null, array('url' => $options));
		$expected = array(
			'a' => array('href' => '/members/posts/index/page:2/sort:name/direction:asc'),
			'Name',
			'/a'
		);
		$this->assertTags($result, $expected, true);

		$result = $this->Paginator->next('next', array('url' => $options));
		$expected = array(
			'<span',
			'a' => array('href' => '/members/posts/index/page:3', 'class' => 'next'),
			'next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('prev', array('url' => $options));
		$expected = array(
			'<span',
			'a' => array('href' => '/members/posts/index/page:1', 'class' => 'prev'),
			'prev',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$options = array('members' => true, 'controller' => 'posts', 'order' => array('name' => 'desc'));
		$result = $this->Paginator->url($options);
		$expected = '/members/posts/index/page:2/sort:name/direction:desc';
		$this->assertEqual($result, $expected);

		$options = array('controller' => 'posts', 'order' => array('Article.name' => 'desc'));
		$result = $this->Paginator->url($options);
		$expected = '/posts/index/page:2/sort:Article.name/direction:desc';
		$this->assertEqual($result, $expected);

		Configure::write('Routing', $_back);
	}

/**
 * testOptions method
 *
 * @access public
 * @return void
 */
	function testOptions() {
		$this->Paginator->options('myDiv');
		$this->assertEqual('myDiv', $this->Paginator->options['update']);

		$this->Paginator->options = array();
		$this->Paginator->params = array();

		$options = array('paging' => array('Article' => array(
			'order' => 'desc',
			'sort' => 'title'
		)));
		$this->Paginator->options($options);

		$expected = array('Article' => array(
			'order' => 'desc',
			'sort' => 'title'
		));
		$this->assertEqual($expected, $this->Paginator->params['paging']);

		$this->Paginator->options = array();
		$this->Paginator->params = array();

		$options = array('Article' => array(
			'order' => 'desc',
			'sort' => 'title'
		));
		$this->Paginator->options($options);
		$this->assertEqual($expected, $this->Paginator->params['paging']);

		$options = array('paging' => array('Article' => array(
			'order' => 'desc',
			'sort' => 'Article.title'
		)));
		$this->Paginator->options($options);

		$expected = array('Article' => array(
			'order' => 'desc',
			'sort' => 'Article.title'
		));
		$this->assertEqual($expected, $this->Paginator->params['paging']);
	}

/**
 * testPassedArgsMergingWithUrlOptions method
 *
 * @access public
 * @return void
 */
	function testPassedArgsMergingWithUrlOptions() {
		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'pass' => array('2'), 'named' => array('foo' => 'bar'), 'form' => array(), 'url' => array('url' => 'articles/index/2/foo:bar'), 'bare' => 0),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/', 'here' => '/articles/', 'webroot' => '/', 'passedArgs' => array(0 => '2', 'foo' => 'bar'))
		));
		$this->Paginator->params['paging'] = array(
			'Article' => array(
				'page' => 1, 'current' => 3, 'count' => 13,
				'prevPage' => false, 'nextPage' => true, 'pageCount' => 8,
				'defaults' => array(
					'limit' => 3, 'step' => 1, 'order' => array(), 'conditions' => array()
				),
				'options' => array(
					'page' => 1, 'limit' => 3, 'order' => array(), 'conditions' => array()
				)
			)
		);

		$this->Paginator->params['pass'] = array(2);
		$this->Paginator->params['named'] = array('foo' => 'bar');
		$this->Paginator->beforeRender();

		$result = $this->Paginator->sort('title');
		$expected = array(
			'a' => array('href' => '/articles/index/2/page:1/foo:bar/sort:title/direction:asc'),
			'Title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array('class' => 'current')), '1', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2/page:2/foo:bar')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2/page:3/foo:bar')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2/page:4/foo:bar')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2/page:5/foo:bar')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2/page:6/foo:bar')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/articles/index/2/page:7/foo:bar')), '7', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'<span',
			'a' => array('href' => '/articles/index/2/page:2/foo:bar', 'class' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testPagingLinks method
 *
 * @access public
 * @return void
 */
	function testPagingLinks() {
		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 1, 'current' => 3, 'count' => 13, 'prevPage' => false, 'nextPage' => true, 'pageCount' => 5,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
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

		$this->Paginator->params['paging']['Client']['page'] = 2;
		$this->Paginator->params['paging']['Client']['prevPage'] = true;
		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled'));
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:1', 'class' => 'prev'),
			'&lt;&lt; Previous',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:3', 'class' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->next('Next', array('tag' => 'li'));
		$expected = array(
			'<li',
			'a' => array('href' => '/index/page:3', 'class' => 'next'),
			'Next',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', array('escape' => true));
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:1', 'class' => 'prev'),
			'&lt;&lt; Previous',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->prev('<< Previous', array('escape' => false));
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:1', 'class' => 'prev'),
			'preg:/<< Previous/',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 1, 'current' => 1, 'count' => 13, 'prevPage' => false, 'nextPage' => true, 'pageCount' => 5,
			'defaults' => array(),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
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

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 1, 'current' => 3, 'count' => 13, 'prevPage' => false, 'nextPage' => true, 'pageCount' => 5,
			'defaults' => array(),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$this->Paginator->params['paging']['Client']['page'] = 2;
		$this->Paginator->params['paging']['Client']['prevPage'] = true;
		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled'));
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:1/limit:3/sort:Client.name/direction:DESC', 'class' => 'prev'),
			'&lt;&lt; Previous',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected, true);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:3/limit:3/sort:Client.name/direction:DESC', 'class' => 'next'),
			'Next',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 2, 'current' => 1, 'count' => 13, 'prevPage' => true, 'nextPage' => false, 'pageCount' => 2,
			'defaults' => array(),
			'options' => array('page' => 2, 'limit' => 10, 'order' => array(), 'conditions' => array())
		));
		$result = $this->Paginator->prev('Prev');
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:1/limit:10', 'class' => 'prev'),
			'Prev',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array(
			'Client' => array(
				'page' => 2, 'current' => 1, 'count' => 13, 'prevPage' => true,
				'nextPage' => false, 'pageCount' => 2,
				'defaults' => array(),
				'options' => array(
					'page' => 2, 'limit' => 10, 'order' => array(), 'conditions' => array()
				)
			)
		);
		$this->Paginator->options(array('url' => array(12, 'page' => 3)));
		$result = $this->Paginator->prev('Prev', array('url' => array('foo' => 'bar')));
		$expected = array(
			'<span',
			'a' => array('href' => '/index/12/page:1/limit:10/foo:bar', 'class' => 'prev'),
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
	function testPagingLinksOptionsReplaceEmptyDisabledOptions() {
		$this->Paginator->params['paging'] = array(
			'Client' => array(
				'page' => 1, 'current' => 3, 'count' => 13, 'prevPage' => false,
				'nextPage' => true, 'pageCount' => 5,
				'defaults' => array(
					'limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()
				),
				'options' => array(
					'page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()
				)
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
			'<span',
			'a' => array('href' => '/index/page:2', 'class' => 'next'),
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
 * @access public
 * @return void
 */
	function testPagingLinksNotDefaultModel() {
		// Multiple Model Paginate
		$this->Paginator->params['paging'] = array(
			'Client' => array(
				'page' => 1, 'current' => 3, 'count' => 13, 'prevPage' => false, 'nextPage' => true, 'pageCount' => 5,
				'defaults' => array( 'limit'=>3, 'order' => array('Client.name' => 'DESC')),
				'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array())
			),
			'Server' => array(
				'page' => 1, 'current' => 1, 'count' => 5, 'prevPage' => false, 'nextPage' => false, 'pageCount' => 5,
				'defaults' => array(),
				'options' => array('page' => 1, 'limit' => 5, 'order' => array('Server.name' => 'ASC'), 'conditions' => array())
			)
		);
		$result = $this->Paginator->next('Next', array('model' => 'Client'));
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:2', 'class' => 'next'),
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
 * @access public
 * @return void
 */
	function testGenericLinks() {
		$result = $this->Paginator->link('Sort by title on page 5', array('sort' => 'title', 'page' => 5, 'direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/index/page:5/sort:title/direction:desc'),
			'Sort by title on page 5',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['page'] = 2;
		$result = $this->Paginator->link('Sort by title', array('sort' => 'title', 'direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/index/page:2/sort:title/direction:desc'),
			'Sort by title',
			'/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Article']['options']['page'] = 4;
		$result = $this->Paginator->link('Sort by title on page 4', array('sort' => 'Article.title', 'direction' => 'desc'));
		$expected = array(
			'a' => array('href' => '/index/page:4/sort:Article.title/direction:desc'),
			'Sort by title on page 4',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests generation of generic links with preset options
 *
 * @access public
 * @return void
 */
	function testGenericLinksWithPresetOptions() {
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array('a' => array('href' => '/index/page:1'), 'Foo!', '/a'));

		$this->Paginator->options(array('sort' => 'title', 'direction' => 'desc'));
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array(
			'a' => array(
				'href' => '/index/page:1',
				'sort' => 'title',
				'direction' => 'desc'
			),
			'Foo!',
			'/a'
		));

		$this->Paginator->options(array('sort' => null, 'direction' => null));
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array('a' => array('href' => '/index/page:1'), 'Foo!', '/a'));

		$this->Paginator->options(array('url' => array(
			'sort' => 'title',
			'direction' => 'desc'
		)));
		$result = $this->Paginator->link('Foo!', array('page' => 1));
		$this->assertTags($result, array(
			'a' => array('href' => '/index/page:1/sort:title/direction:desc'),
			'Foo!',
			'/a'
		));
	}

/**
 * testNumbers method
 *
 * @access public
 * @return void
 */
	function testNumbers() {
		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 8, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '8', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:12')), '12', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('tag' => 'li'));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/li',
			' | ',
			array('li' => array('class' => 'current')), '8', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index/page:10')), '10', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index/page:11')), '11', '/a', '/li',
			' | ',
			array('li' => array()), array('a' => array('href' => '/index/page:12')), '12', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('tag' => 'li', 'separator' => false));
		$expected = array(
			array('li' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/li',
			array('li' => array('class' => 'current')), '8', '/li',
			array('li' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index/page:10')), '10', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index/page:11')), '11', '/a', '/li',
			array('li' => array()), array('a' => array('href' => '/index/page:12')), '12', '/a', '/li',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(true);
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), 'first', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '8', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:15')), 'last', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 1, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array('class' => 'current')), '1', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
		);
		$this->assertTags($result, $expected);


		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 14, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:13')), '13', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '14', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:15')), '15', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 2, 'current' => 3, 'count' => 27, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 9,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '2', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '2', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 15, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:10')), '10', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:13')), '13', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:14')), '14', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '15', '/span',

		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 10, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '10', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:11')), '11', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:12')), '12', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:13')), '13', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:14')), '14', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:15')), '15', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 6, 'current' => 15, 'count' => 623, 'prevPage' => 1, 'nextPage' => 1, 'pageCount' => 42,
			'defaults' => array('limit' => 15, 'step' => 1, 'page' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 6, 'limit' => 15, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '6', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:8')), '8', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:9')), '9', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:10')), '10', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:42')), '42', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 37, 'current' => 15, 'count' => 623, 'prevPage' => 1, 'nextPage' => 1, 'pageCount' => 42,
			'defaults' => array('limit' => 15, 'step' => 1, 'page' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 37, 'limit' => 15, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:33')), '33', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:34')), '34', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:35')), '35', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:36')), '36', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '37', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:38')), '38', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:39')), '39', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:40')), '40', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:41')), '41', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:42')), '42', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 10,
				'count' => 30,
				'prevPage' => false,
				'nextPage' => 2,
				'pageCount' => 3,
				'defaults' => array(
					'limit' => 3,
					'step' => 1,
					'order' => array('Client.name' => 'DESC'),
					'conditions' => array()
				),
				'options' => array(
					'page' => 1,
					'limit' => 3,
					'order' => array('Client.name' => 'DESC'),
					'conditions' => array()
				)
			)
		);
		$options = array('modulus' => 10);
		$result = $this->Paginator->numbers($options);
		$expected = array(
			array('span' => array('class' => 'current')), '1', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 2, 'current' => 10, 'count' => 31, 'prevPage' => true, 'nextPage' => true, 'pageCount' => 4,
			'defaults' => array('limit' => 10),
			'options' => array('page' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->numbers();
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1/sort:Client.name/direction:DESC')), '1', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '2', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:3/sort:Client.name/direction:DESC')), '3', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4/sort:Client.name/direction:DESC')), '4', '/a', '/span',
		);
		$this->assertTags($result, $expected);


		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 4895, 'current' => 10, 'count' => 48962, 'prevPage' => 1, 'nextPage' => 1, 'pageCount' => 4897,
			'defaults' => array('limit' => 10),
			'options' => array('page' => 4894, 'limit' => 10, 'order' => 'Client.name DESC', 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:4894')), '4894', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '4895', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4896')), '4896', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Client']['page'] = 3;

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' | ',
			array('span' => array('class' => 'current')), '3', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:4896')), '4896', '/a', '/span',
			' | ',
			array('span' => array()), array('a' => array('href' => '/index/page:4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 2, 'modulus' => 2, 'last' => 2, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '3', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 5, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '3', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:4893')), '4893', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Client']['page'] = 4893;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:4891')), '4891', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4892')), '4892', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '4893', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Client']['page'] = 58;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:5')), '5', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:56')), '56', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:57')), '57', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '58', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:59')), '59', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:60')), '60', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:4893')), '4893', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging']['Client']['page'] = 5;
		$result = $this->Paginator->numbers(array('first' => 5, 'modulus' => 4, 'last' => 5, 'separator' => ' - '));
		$expected = array(
			array('span' => array()), array('a' => array('href' => '/index/page:1')), '1', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:2')), '2', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:3')), '3', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4')), '4', '/a', '/span',
			' - ',
			array('span' => array('class' => 'current')), '5', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:6')), '6', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:7')), '7', '/a', '/span',
			'...',
			array('span' => array()), array('a' => array('href' => '/index/page:4893')), '4893', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4894')), '4894', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4895')), '4895', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4896')), '4896', '/a', '/span',
			' - ',
			array('span' => array()), array('a' => array('href' => '/index/page:4897')), '4897', '/a', '/span',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFirstAndLast method
 *
 * @access public
 * @return void
 */
	function testFirstAndLast() {
		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 1, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->first();
		$expected = '';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 4, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->first();
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:1'),
			'&lt;&lt; first',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->first('<<', array('tag' => 'li'));
		$expected = array(
			'<li',
			'a' => array('href' => '/index/page:1'),
			'&lt;&lt;',
			'/a',
			'/li'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last();
		$expected = array(
			'<span',
			'a' => array('href' => '/index/page:15'),
			'last &gt;&gt;',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = array(
			'...',
			'<span',
			'a' => array('href' => '/index/page:15'),
			'15',
			'/a',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2);
		$expected = array(
			'...',
			'<span',
			array('a' => array('href' => '/index/page:14')), '14', '/a',
			'/span',
			' | ',
			'<span',
			array('a' => array('href' => '/index/page:15')), '15', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2, array('tag' => 'li'));
		$expected = array(
			'...',
			'<li',
			array('a' => array('href' => '/index/page:14')), '14', '/a',
			'/li',
			' | ',
			'<li',
			array('a' => array('href' => '/index/page:15')), '15', '/a',
			'/li',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 15, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->last();
		$expected = '';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 4, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->first();
		$expected = array(
			'<span',
			array('a' => array('href' => '/index/page:1/sort:Client.name/direction:DESC')), '&lt;&lt; first', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last();
		$expected = array(
			'<span',
			array('a' => array('href' => '/index/page:15/sort:Client.name/direction:DESC')), 'last &gt;&gt;', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = array(
			'...',
			'<span',
			array('a' => array('href' => '/index/page:15/sort:Client.name/direction:DESC')), '15', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$result = $this->Paginator->last(2);
		$expected = array(
			'...',
			'<span',
			array('a' => array('href' => '/index/page:14/sort:Client.name/direction:DESC')), '14', '/a',
			'/span',
			' | ',
			'<span',
			array('a' => array('href' => '/index/page:15/sort:Client.name/direction:DESC')), '15', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('full_base' => true)));
		$result = $this->Paginator->first();

		$expected = array(
			'<span',
			array('a' => array('href' => FULL_BASE_URL . '/index/page:1/sort:Client.name/direction:DESC')), '&lt;&lt; first', '/a',
			'/span',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testCounter method
 *
 * @access public
 * @return void
 */
	function testCounter() {
		$this->Paginator->params['paging'] = array(
			'Client' => array(
				'page' => 1,
				'current' => 3,
				'count' => 13,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 5,
				'defaults' => array(
					'limit' => 3,
					'step' => 1,
					'order' => array('Client.name' => 'DESC'),
					'conditions' => array()
				),
				'options' => array(
					'page' => 1,
					'limit' => 3,
					'order' => array('Client.name' => 'DESC'),
					'conditions' => array(),
					'separator' => 'of'
				),
			)
		);
		$input = 'Page %page% of %pages%, showing %current% records out of %count% total, ';
		$input .= 'starting on record %start%, ending on %end%';
		$result = $this->Paginator->counter($input);
		$expected = 'Page 1 of 5, showing 3 records out of 13 total, starting on record 1, ';
		$expected .= 'ending on 3';
		$this->assertEqual($result, $expected);

		$input = 'Page {:page} of {:pages}, showing {:current} records out of {:count} total, ';
		$input .= 'starting on record {:start}, ending on {:end}';
		$result = $this->Paginator->counter($input);
		$this->assertEqual($result, $expected);

		$input = 'Page %page% of %pages%';
		$result = $this->Paginator->counter($input);
		$expected = 'Page 1 of 5';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->counter(array('format' => $input));
		$expected = 'Page 1 of 5';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->counter(array('format' => 'pages'));
		$expected = '1 of 5';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->counter(array('format' => 'range'));
		$expected = '1 - 3 of 13';
		$this->assertEqual($result, $expected);
	}

/**
 * testHasPage method
 *
 * @access public
 * @return void
 */
	function testHasPage() {
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
 * @access public
 * @return void
 */
	function testWithPlugin() {
		Router::reload();
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'named' => array(), 'prefix' => null, 'form' => array(),
				'controller' => 'magazines', 'plugin' => 'my_plugin', 'action' => 'index',
				'url' => array('ext' => 'html', 'url' => 'my_plugin/magazines')),
			array('base' => '', 'here' => '/my_plugin/magazines', 'webroot' => '/')
		));

		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/my_plugin/magazines/index/page:3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('action' => 'another_index')));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/my_plugin/magazines/another_index/page:3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('controller' => 'issues')));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/my_plugin/issues/index/page:3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);

		$this->Paginator->options(array('url' => array('plugin' => null)));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('/magazines/index/page:3'), 'Page 3', '/a'
		);

		$this->Paginator->options(array('url' => array('plugin' => null, 'controller' => 'issues')));
		$result = $this->Paginator->link('Page 3', array('page' => 3));
		$expected = array(
			'a' => array('href' => '/issues/index/page:3'), 'Page 3', '/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testNextLinkUsingDotNotation method
 *
 * @access public
 * @return void
 */
	function testNextLinkUsingDotNotation() {
		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array('url' => 'accounts/', 'mod_rewrite' => 'true'), 'bare' => 0),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/', 'passedArgs' => array())
		));

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$this->Paginator->params['paging']['Article']['page'] = 1;

		$test = array('url'=> array(
			'page'=> '1',
			'sort'=>'Article.title',
			'direction'=>'asc',
		));
		$this->Paginator->options($test);

		$result = $this->Paginator->next('Next');
		$expected = array(
			'<span',
			'a' => array('href' => '/officespace/accounts/index/page:2/sort:Article.title/direction:asc', 'class' => 'next'),
			'Next',
			'/a',
			'/span',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that mock classes injected into paginatorHelper are called when using link()
 *
 * @return void
 */
	function testMockAjaxProviderClassInjection() {
		$Paginator =& new PaginatorHelper(array('ajax' => 'PaginatorMockJs'));
		$Paginator->params['paging'] = array(
			'Article' => array(
				'current' => 9,
				'count' => 62,
				'prevPage' => false,
				'nextPage' => true,
				'pageCount' => 7,
				'defaults' => array(),
				'options' => array()
			)
		);
		$Paginator->PaginatorMockJs =& new PaginatorMockJsHelper();
		$Paginator->PaginatorMockJs->expectOnce('link');
		$result = $Paginator->link('Page 2', array('page' => 2), array('update' => '#content'));

		$this->expectError();
		$Paginator =& new PaginatorHelper(array('ajax' => 'Form'));
	}
}
