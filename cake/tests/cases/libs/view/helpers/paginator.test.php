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
App::import('Helper', array('Html', 'Paginator', 'Form', 'Ajax', 'Javascript'));
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class PaginatorTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
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

		Configure::write('Routing.admin', '');
		Router::reload();
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
		$expected = '<div>Next</div>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging']['Article']['prevPage'] = false;
		$result = $this->Paginator->prev('prev', array('update'=> 'theList', 'indicator'=> 'loading', 'url'=> array('controller' => 'posts')), null, array('class' => 'disabled', 'tag' => 'span'));
		$expected = '<span class="disabled">prev</span>';
		$this->assertEqual($result, $expected);
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
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array('url' => 'accounts/', 'mod_rewrite' => 'true'), 'bare' => 0),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/', 'passedArgs' => array())
		));
		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc">Title<\/a>$/', $result);

		$result = $this->Paginator->sort('date');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:date\/direction:desc">Date<\/a>$/', $result);

		$result = $this->Paginator->numbers(array('modulus'=> '2', 'url'=> array('controller'=>'projects', 'action'=>'sort'),'update'=>'list'));
		$this->assertPattern('/\/projects\/sort\/page:2/', $result);
		$this->assertPattern('/<script type="text\/javascript">\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*Event.observe/', $result);

		$result = $this->Paginator->sort('TestTitle', 'title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc">TestTitle<\/a>$/', $result);

		$result = $this->Paginator->sort(array('asc' => 'ascending', 'desc' => 'descending'), 'title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:asc">ascending<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['sort'] = 'title';
		$result = $this->Paginator->sort(array('asc' => 'ascending', 'desc' => 'descending'), 'title');
		$this->assertPattern('/\/accounts\/index\/param\/page:1\/sort:title\/direction:desc">descending<\/a>$/', $result);
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
		$this->assertPattern('/\/accounts\/index\/page:1\/sort:Article.title\/direction:asc">Title<\/a>$/', $result);

		$this->Paginator->params['paging']['Article']['options']['order'] = array('Article.title' => 'asc');
		$result = $this->Paginator->sort('Title','Article.title');
		$this->assertPattern('/\/accounts\/index\/page:1\/sort:Article.title\/direction:desc">Title<\/a>$/', $result);

	}
/**
 * testSortAdminLinks method
 *
 * @access public
 * @return void
 */
	function testSortAdminLinks() {
		Configure::write('Routing.admin', 'admin');

		Router::reload();
		Router::setRequestInfo(array(
			array('pass' => array(), 'named' => array(), 'controller' => 'users', 'plugin' => null, 'action' => 'admin_index', 'prefix' => 'admin', 'admin' => true, 'url' => array('ext' => 'html', 'url' => 'admin/users'), 'form' => array()),
			array('base' => '', 'here' => '/admin/users', 'webroot' => '/')
		));
		Router::parse('/admin/users');
		$this->Paginator->params['paging']['Article']['page'] = 1;
		$result = $this->Paginator->next('Next');
		$this->assertPattern('/^<a[^<>]+>Next<\/a>$/', $result);
		$this->assertPattern('/href="\/admin\/users\/index\/page:2"/', $result);

		Router::reload();
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'test', 'action' => 'admin_index', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/test')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/test', 'webroot' => '/')
		));
		Router::parse('/');
		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('title');
		$this->assertPattern('/\/admin\/test\/index\/param\/page:1\/sort:title\/direction:asc"\s*>Title<\/a>$/', $result);

		$this->Paginator->options(array('url' => array('param')));
		$result = $this->Paginator->sort('Title', 'Article.title');
		$this->assertPattern('/\/admin\/test\/index\/param\/page:1\/sort:Article.title\/direction:asc"\s*>Title<\/a>$/', $result);

	}
/**
 * testUrlGeneration method
 *
 * @access public
 * @return void
 */
	function testUrlGeneration() {
		$result = $this->Paginator->sort('controller');
		$this->assertPattern('/\/page:1\//', $result);
		$this->assertPattern('/\/sort:controller\//', $result);

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
		$memberPrefixes = array('prefix' => 'members', 'members' => true);
		Router::connect('/members/:controller/:action/*', $memberPrefixes);
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
		$expected = '<a href="/members/posts/index/page:2/sort:name/direction:asc">Name</a>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->next('next', array('url' => $options));
		$expected = '<a href="/members/posts/index/page:3">next</a>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->prev('prev', array('url' => $options));
		$expected = '<a href="/members/posts/index/page:1">prev</a>';
		$this->assertEqual($result, $expected);

		$options = array('members' => true, 'controller' => 'posts', 'order' => array('name' => 'desc'));
		$result = $this->Paginator->url($options);
		$expected = '/members/posts/index/page:2/sort:name/direction:desc';
		$this->assertEqual($result, $expected);

		$options = array('controller' => 'posts', 'order' => array('Article.name' => 'desc'));
		$result = $this->Paginator->url($options);
		$expected = '/posts/index/page:2/sort:Article.name/direction:desc';
		$this->assertEqual($result, $expected);
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
		$expected = '<div class="disabled">&lt;&lt; Previous</div>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled', 'tag' => 'span'));
		$expected = '<span class="disabled">&lt;&lt; Previous</span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging']['Client']['page'] = 2;
		$this->Paginator->params['paging']['Client']['prevPage'] = true;
		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled'));
		$this->assertPattern('/^<a[^<>]+>&lt;&lt; Previous<\/a>$/', $result);
		$this->assertPattern('/href="\/index\/page:1"/', $result);

		$result = $this->Paginator->next('Next');
		$this->assertPattern('/^<a[^<>]+>Next<\/a>$/', $result);
		$this->assertPattern('/href="\/index\/page:3"/', $result);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 1, 'current' => 3, 'count' => 13, 'prevPage' => false, 'nextPage' => true, 'pageCount' => 5,
			'defaults' => array(),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$this->Paginator->params['paging']['Client']['page'] = 2;
		$this->Paginator->params['paging']['Client']['prevPage'] = true;
		$result = $this->Paginator->prev('<< Previous', null, null, array('class' => 'disabled'));
		$this->assertPattern('/\/sort:Client.name\/direction:DESC"/', $result);

		$result = $this->Paginator->next('Next');
		$this->assertPattern('/\/sort:Client.name\/direction:DESC"/', $result);

	}
/**
 * testGenericLinks method
 *
 * @access public
 * @return void
 */
	function testGenericLinks() {
		$result = $this->Paginator->link('Sort by title on page 5', array('sort' => 'title', 'page' => 5, 'direction' => 'desc'));
		$this->assertPattern('/^<a href=".+"[^<>]*>Sort by title on page 5<\/a>$/', $result);
		$this->assertPattern('/\/page:5/', $result);
		$this->assertPattern('/\/sort:title/', $result);
		$this->assertPattern('/\/direction:desc/', $result);

		$this->Paginator->params['paging']['Article']['options']['page'] = 2;
		$result = $this->Paginator->link('Sort by title', array('sort' => 'title', 'direction' => 'desc'));
		$this->assertPattern('/^<a href=".+"[^<>]*>Sort by title<\/a>$/', $result);
		$this->assertPattern('/\/page:2/', $result);
		$this->assertPattern('/\/sort:title/', $result);
		$this->assertPattern('/\/direction:desc/', $result);

		$this->Paginator->params['paging']['Article']['options']['page'] = 4;
		$result = $this->Paginator->link('Sort by title on page 4', array('sort' => 'Article.title', 'direction' => 'desc'));
		$this->assertPattern('/^<a href=".+"[^<>]*>Sort by title on page 4<\/a>$/', $result);
		$this->assertPattern('/\/page:4/', $result);
		$this->assertPattern('/\/sort:Article.title/', $result);
		$this->assertPattern('/\/direction:desc/', $result);
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
		$expected = '<span><a href="/index/page:4">4</a></span> | <span><a href="/index/page:5">5</a></span> | <span><a href="/index/page:6">6</a></span> | <span><a href="/index/page:7">7</a></span> | <span class="current">8</span> | <span><a href="/index/page:9">9</a></span> | <span><a href="/index/page:10">10</a></span> | <span><a href="/index/page:11">11</a></span> | <span><a href="/index/page:12">12</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->numbers(array('tag' => 'li'));
		$expected = '<li><a href="/index/page:4">4</a></li> | <li><a href="/index/page:5">5</a></li> | <li><a href="/index/page:6">6</a></li> | <li><a href="/index/page:7">7</a></li> | <li class="current">8</li> | <li><a href="/index/page:9">9</a></li> | <li><a href="/index/page:10">10</a></li> | <li><a href="/index/page:11">11</a></li> | <li><a href="/index/page:12">12</a></li>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->numbers(array('tag' => 'li', 'separator' => false));
		$expected = '<li><a href="/index/page:4">4</a></li><li><a href="/index/page:5">5</a></li><li><a href="/index/page:6">6</a></li><li><a href="/index/page:7">7</a></li><li class="current">8</li><li><a href="/index/page:9">9</a></li><li><a href="/index/page:10">10</a></li><li><a href="/index/page:11">11</a></li><li><a href="/index/page:12">12</a></li>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->numbers(true);
		$expected = '<span><a href="/index/page:1">first</a></span> | <span><a href="/index/page:4">4</a></span> | <span><a href="/index/page:5">5</a></span> | <span><a href="/index/page:6">6</a></span> | <span><a href="/index/page:7">7</a></span> | <span class="current">8</span> | <span><a href="/index/page:9">9</a></span> | <span><a href="/index/page:10">10</a></span> | <span><a href="/index/page:11">11</a></span> | <span><a href="/index/page:12">12</a></span> | <span><a href="/index/page:15">last</a></span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 1, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->numbers();
		$expected = '<span class="current">1</span> | <span><a href="/index/page:2">2</a></span> | <span><a href="/index/page:3">3</a></span> | <span><a href="/index/page:4">4</a></span> | <span><a href="/index/page:5">5</a></span> | <span><a href="/index/page:6">6</a></span> | <span><a href="/index/page:7">7</a></span> | <span><a href="/index/page:8">8</a></span> | <span><a href="/index/page:9">9</a></span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 14, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->numbers();
		$expected = '<span><a href="/index/page:7">7</a></span> | <span><a href="/index/page:8">8</a></span> | <span><a href="/index/page:9">9</a></span> | <span><a href="/index/page:10">10</a></span> | <span><a href="/index/page:11">11</a></span> | <span><a href="/index/page:12">12</a></span> | <span><a href="/index/page:13">13</a></span> | <span class="current">14</span> | <span><a href="/index/page:15">15</a></span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 2, 'current' => 3, 'count' => 27, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 9,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1));
		$expected = '<span><a href="/index/page:1">1</a></span> | <span class="current">2</span> | <span><a href="/index/page:3">3</a></span> | <span><a href="/index/page:4">4</a></span> | <span><a href="/index/page:5">5</a></span> | <span><a href="/index/page:6">6</a></span> | <span><a href="/index/page:7">7</a></span> | <span><a href="/index/page:8">8</a></span> | <span><a href="/index/page:9">9</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->numbers(array('last' => 1));
		$expected = '<span><a href="/index/page:1">1</a></span> | <span class="current">2</span> | <span><a href="/index/page:3">3</a></span> | <span><a href="/index/page:4">4</a></span> | <span><a href="/index/page:5">5</a></span> | <span><a href="/index/page:6">6</a></span> | <span><a href="/index/page:7">7</a></span> | <span><a href="/index/page:8">8</a></span> | <span><a href="/index/page:9">9</a></span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 15, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1));
		$expected = '<span><a href="/index/page:1">1</a></span>...<span><a href="/index/page:7">7</a></span> | <span><a href="/index/page:8">8</a></span> | <span><a href="/index/page:9">9</a></span> | <span><a href="/index/page:10">10</a></span> | <span><a href="/index/page:11">11</a></span> | <span><a href="/index/page:12">12</a></span> | <span><a href="/index/page:13">13</a></span> | <span><a href="/index/page:14">14</a></span> | <span class="current">15</span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 10, 'current' => 3, 'count' => 30, 'prevPage' => false, 'nextPage' => 2, 'pageCount' => 15,
			'defaults' => array('limit' => 3, 'step' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 1, 'limit' => 3, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = '<span><a href="/index/page:1">1</a></span>...<span><a href="/index/page:6">6</a></span> | <span><a href="/index/page:7">7</a></span> | <span><a href="/index/page:8">8</a></span> | <span><a href="/index/page:9">9</a></span> | <span class="current">10</span> | <span><a href="/index/page:11">11</a></span> | <span><a href="/index/page:12">12</a></span> | <span><a href="/index/page:13">13</a></span> | <span><a href="/index/page:14">14</a></span> | <span><a href="/index/page:15">15</a></span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 6, 'current' => 15, 'count' => 623, 'prevPage' => 1, 'nextPage' => 1, 'pageCount' => 42,
			'defaults' => array('limit' => 15, 'step' => 1, 'page' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 6, 'limit' => 15, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = '<span><a href="/index/page:1">1</a></span> | <span><a href="/index/page:2">2</a></span> | <span><a href="/index/page:3">3</a></span> | <span><a href="/index/page:4">4</a></span> | <span><a href="/index/page:5">5</a></span> | <span class="current">6</span> | <span><a href="/index/page:7">7</a></span> | <span><a href="/index/page:8">8</a></span> | <span><a href="/index/page:9">9</a></span> | <span><a href="/index/page:10">10</a></span>...<span><a href="/index/page:42">42</a></span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 37, 'current' => 15, 'count' => 623, 'prevPage' => 1, 'nextPage' => 1, 'pageCount' => 42,
			'defaults' => array('limit' => 15, 'step' => 1, 'page' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()),
			'options' => array('page' => 37, 'limit' => 15, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);

		$result = $this->Paginator->numbers(array('first' => 1, 'last' => 1));
		$expected = '<span><a href="/index/page:1">1</a></span>...<span><a href="/index/page:33">33</a></span> | <span><a href="/index/page:34">34</a></span> | <span><a href="/index/page:35">35</a></span> | <span><a href="/index/page:36">36</a></span> | <span class="current">37</span> | <span><a href="/index/page:38">38</a></span> | <span><a href="/index/page:39">39</a></span> | <span><a href="/index/page:40">40</a></span> | <span><a href="/index/page:41">41</a></span> | <span><a href="/index/page:42">42</a></span>';
		$this->assertEqual($result, $expected);

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
		$expected = '<span class="current">1</span> | <span><a href="/index/page:2">2</a></span> | <span><a href="/index/page:3">3</a></span>';
		$this->assertEqual($result, $expected);

		$this->Paginator->params['paging'] = array('Client' => array(
			'page' => 2, 'current' => 10, 'count' => 31, 'prevPage' => true, 'nextPage' => true, 'pageCount' => 4,
			'defaults' => array('limit' => 10),
			'options' => array('page' => 1, 'order' => array('Client.name' => 'DESC'), 'conditions' => array()))
		);
		$result = $this->Paginator->numbers();
		$expected = '<span><a href="/index/page:1/sort:Client.name/direction:DESC">1</a></span> | <span class="current">2</span> | <span><a href="/index/page:3/sort:Client.name/direction:DESC">3</a></span> | <span><a href="/index/page:4/sort:Client.name/direction:DESC">4</a></span>';
		$this->assertEqual($result, $expected);
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
		$expected = '<span><a href="/index/page:1">&lt;&lt; first</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->first('<<', array('tag' => 'li'));
		$expected = '<li><a href="/index/page:1">&lt;&lt;</a></li>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->last();
		$expected = '<span><a href="/index/page:15">last &gt;&gt;</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = '...<span><a href="/index/page:15">15</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->last(2);
		$expected = '...<span><a href="/index/page:14">14</a></span> | <span><a href="/index/page:15">15</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->last(2, array('tag' => 'li'));
		$expected = '...<li><a href="/index/page:14">14</a></li> | <li><a href="/index/page:15">15</a></li>';
		$this->assertEqual($result, $expected);

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
		$expected = '<span><a href="/index/page:1/sort:Client.name/direction:DESC">&lt;&lt; first</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->last();
		$expected = '<span><a href="/index/page:15/sort:Client.name/direction:DESC">last &gt;&gt;</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->last(1);
		$expected = '...<span><a href="/index/page:15/sort:Client.name/direction:DESC">15</a></span>';
		$this->assertEqual($result, $expected);

		$result = $this->Paginator->last(2);
		$expected = '...<span><a href="/index/page:14/sort:Client.name/direction:DESC">14</a></span> | <span><a href="/index/page:15/sort:Client.name/direction:DESC">15</a></span>';
		$this->assertEqual($result, $expected);
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
		$input = 'Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%';
		$result = $this->Paginator->counter($input);
		$expected = 'Page 1 of 5, showing 3 records out of 13 total, starting on record 1, ending on 3';
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
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Paginator);
	}
}
?>