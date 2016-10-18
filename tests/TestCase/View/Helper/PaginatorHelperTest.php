<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\PaginatorHelper;
use Cake\View\View;

/**
 * PaginatorHelperTest class
 */
class PaginatorHelperTest extends TestCase
{

    /**
     * @var string
     */
    protected $locale;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Config.language', 'eng');
        $this->View = new View();
        $this->Paginator = new PaginatorHelper($this->View);
        $this->Paginator->Js = $this->getMockBuilder('Cake\View\Helper\PaginatorHelper')
            ->setConstructorArgs([$this->View])
            ->getMock();
        $this->Paginator->request = new Request();
        $this->Paginator->request->addParams([
            'paging' => [
                'Article' => [
                    'page' => 1,
                    'current' => 9,
                    'count' => 62,
                    'prevPage' => false,
                    'nextPage' => true,
                    'pageCount' => 7,
                    'sort' => null,
                    'direction' => null,
                    'limit' => null,
                ]
            ]
        ]);

        Configure::write('Routing.prefixes', []);
        Router::reload();
        Router::connect('/:controller/:action/*');
        Router::connect('/:plugin/:controller/:action/*');

        $this->locale = I18n::locale();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->View, $this->Paginator);

        I18n::locale($this->locale);
    }

    /**
     * Test the templates method.
     *
     * @return void
     */
    public function testTemplates()
    {
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
    public function testHasPrevious()
    {
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
    public function testHasNext()
    {
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
    public function testSortLinks()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => [], 'url' => ['url' => 'accounts/']],
            ['base' => '', 'here' => '/accounts/', 'webroot' => '/']
        ]);

        $this->Paginator->options(['url' => ['param']]);
        $this->Paginator->request['paging'] = [
            'Article' => [
                'current' => 9,
                'count' => 62,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 7,
                'sort' => 'date',
                'direction' => 'asc',
                'page' => 1,
            ]
        ];

        $result = $this->Paginator->sort('title');
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('title', null, ['model' => 'Nope']);
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('title', null, ['model' => 'Article']);
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('date');
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=date&amp;direction=desc', 'class' => 'asc'],
            'Date',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('title', 'TestTitle');
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=asc'],
            'TestTitle',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('title', ['asc' => 'ascending', 'desc' => 'descending']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=asc'],
            'ascending',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'title';
        $result = $this->Paginator->sort('title', ['asc' => 'ascending', 'desc' => 'descending']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'],
            'descending',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
        $result = $this->Paginator->sort('title');
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=asc', 'class' => 'desc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
        $result = $this->Paginator->sort('title');
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
        $result = $this->Paginator->sort('title', 'Title', ['direction' => 'desc']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=asc', 'class' => 'desc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
        $result = $this->Paginator->sort('title', 'Title', ['direction' => 'ASC']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=asc', 'class' => 'desc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
        $result = $this->Paginator->sort('title', 'Title', ['direction' => 'asc']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'asc';

        $result = $this->Paginator->sort('title', 'Title', ['direction' => 'desc']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=desc', 'class' => 'asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test sort() with escape option
     */
    public function testSortEscape()
    {
        $result = $this->Paginator->sort('title', 'TestTitle >');
        $expected = [
            'a' => ['href' => '/index?sort=title&amp;direction=asc'],
            'TestTitle &gt;',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('title', 'TestTitle >', ['escape' => true]);
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('title', 'TestTitle >', ['escape' => false]);
        $expected = [
            'a' => ['href' => '/index?sort=title&amp;direction=asc'],
            'TestTitle >',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test that sort() works with virtual field order options.
     *
     * @return void
     */
    public function testSortLinkWithVirtualField()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => [], 'form' => [], 'url' => ['url' => 'accounts/']],
            ['base' => '', 'here' => '/accounts/', 'webroot' => '/']
        ]);
        $this->Paginator->request->params['paging']['Article']['sort'] = 'full_name';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'asc';

        $result = $this->Paginator->sort('Article.full_name');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=Article.full_name&amp;direction=desc', 'class' => 'asc'],
            'Article Full Name',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('full_name');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=full_name&amp;direction=desc', 'class' => 'asc'],
            'Full Name',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'full_name';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
        $result = $this->Paginator->sort('Article.full_name');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=Article.full_name&amp;direction=asc', 'class' => 'desc'],
            'Article Full Name',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('full_name');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=full_name&amp;direction=asc', 'class' => 'desc'],
            'Full Name',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSortLinksUsingDirectionOption method
     *
     * @return void
     */
    public function testSortLinksUsingDirectionOption()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'accounts', 'action' => 'index',
                'url' => ['url' => 'accounts/', 'mod_rewrite' => 'true']],
            ['base' => '/', 'here' => '/accounts/', 'webroot' => '/']
        ]);
        $this->Paginator->options(['url' => ['param']]);

        $result = $this->Paginator->sort('title', 'TestTitle', ['direction' => 'desc']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=desc'],
            'TestTitle',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->sort('title', ['asc' => 'ascending', 'desc' => 'descending'], ['direction' => 'desc']);
        $expected = [
            'a' => ['href' => '/accounts/index/param?sort=title&amp;direction=desc'],
            'descending',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSortLinksUsingDotNotation method
     *
     * @return void
     */
    public function testSortLinksUsingDotNotation()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => []],
            ['base' => '', 'here' => '/accounts/', 'webroot' => '/']
        ]);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
        $result = $this->Paginator->sort('Article.title');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=Article.title&amp;direction=asc', 'class' => 'desc'],
            'Article Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'desc';
        $result = $this->Paginator->sort('Article.title', 'Title');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=Article.title&amp;direction=asc', 'class' => 'desc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
        $result = $this->Paginator->sort('Article.title', 'Title');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=Article.title&amp;direction=desc', 'class' => 'asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Account.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
        $result = $this->Paginator->sort('title');
        $expected = [
            'a' => ['href' => '/accounts/index?sort=title&amp;direction=asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test multiple pagination sort links
     *
     * @return void
     */
    public function testSortLinksMultiplePagination()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => [], 'url' => ['url' => 'accounts/']],
            ['base' => '', 'here' => '/accounts/', 'webroot' => '/']
        ]);

        $this->Paginator->options(['model' => 'Articles']);
        $this->Paginator->request['paging'] = [
            'Articles' => [
                'current' => 9,
                'count' => 62,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 7,
                'sort' => 'date',
                'direction' => 'asc',
                'page' => 1,
                'scope' => 'article',
            ]
        ];

        $result = $this->Paginator->sort('title');
        $expected = [
            'a' => ['href' => '/accounts/index?article%5Bsort%5D=title&amp;article%5Bdirection%5D=asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test creating paging links for missing models.
     *
     * @return void
     */
    public function testPagingLinksMissingModel()
    {
        $result = $this->Paginator->sort('title', 'Title', ['model' => 'Missing']);
        $expected = [
            'a' => ['href' => '/index?sort=title&amp;direction=asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('Next', ['model' => 'Missing']);
        $expected = [
            'li' => ['class' => 'next disabled'],
            'a' => ['href' => '', 'onclick' => 'return false;'],
            'Next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->prev('Prev', ['model' => 'Missing']);
        $expected = [
            'li' => ['class' => 'prev disabled'],
            'a' => ['href' => '', 'onclick' => 'return false;'],
            'Prev',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSortKey method
     *
     * @return void
     */
    public function testSortKey()
    {
        $result = $this->Paginator->sortKey('Article', ['sort' => 'Article.title']);
        $this->assertEquals('Article.title', $result);

        $result = $this->Paginator->sortKey('Article', ['sort' => 'Article']);
        $this->assertEquals('Article', $result);
    }

    /**
     * Test that sortKey falls back to the default sorting options set
     * in the $params which are the default pagination options.
     *
     * @return void
     */
    public function testSortKeyFallbackToParams()
    {
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
    public function testSortDir()
    {
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
        $result = $this->Paginator->sortDir('Article', ['direction' => 'asc']);
        $this->assertEquals('asc', $result);

        $result = $this->Paginator->sortDir('Article', ['direction' => 'desc']);
        $this->assertEquals('desc', $result);

        $result = $this->Paginator->sortDir('Article', ['direction' => 'asc']);
        $this->assertEquals('asc', $result);
    }

    /**
     * Test that sortDir falls back to the default sorting options set
     * in the $params which are the default pagination options.
     *
     * @return void
     */
    public function testSortDirFallbackToParams()
    {
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
    public function testSortAdminLinks()
    {
        Configure::write('Routing.prefixes', ['admin']);
        Router::reload();
        Router::connect('/admin/:controller/:action/*', ['prefix' => 'admin']);
        Router::setRequestInfo([
            ['controller' => 'users', 'plugin' => null, 'action' => 'index', 'prefix' => 'admin'],
            ['base' => '', 'here' => '/admin/users', 'webroot' => '/']
        ]);
        $this->Paginator->request->params['paging']['Article']['page'] = 1;
        $result = $this->Paginator->next('Next');
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['href' => '/admin/users/index?page=2', 'rel' => 'next'],
            'Next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->options(['url' => ['param']]);
        $result = $this->Paginator->sort('title');
        $expected = [
            'a' => ['href' => '/admin/users/index/param?sort=title&amp;direction=asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->options(['url' => ['param']]);
        $result = $this->Paginator->sort('Article.title', 'Title');
        $expected = [
            'a' => ['href' => '/admin/users/index/param?sort=Article.title&amp;direction=asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that generated URLs work without sort defined within the request
     *
     * @return void
     */
    public function testDefaultSortAndNoSort()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'articles', 'action' => 'index'],
            ['base' => '/', 'here' => '/articles/', 'webroot' => '/']
        ]);
        $this->Paginator->request->params['paging'] = [
            'Article' => [
                'page' => 1, 'current' => 3, 'count' => 13,
                'prevPage' => false, 'nextPage' => true, 'pageCount' => 8,
                'sortDefault' => 'Article.title', 'directionDefault' => 'ASC',
                'sort' => null
            ]
        ];
        $result = $this->Paginator->next('Next');
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['rel' => 'next', 'href' => '/articles/index?page=2'],
            'Next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testUrlGeneration method
     *
     * @return void
     */
    public function testUrlGeneration()
    {
        $result = $this->Paginator->sort('controller');
        $expected = [
            'a' => ['href' => '/index?sort=controller&amp;direction=asc'],
            'Controller',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->generateUrl();
        $this->assertEquals('/index', $result);

        $this->Paginator->request->params['paging']['Article']['page'] = 2;
        $result = $this->Paginator->generateUrl();
        $this->assertEquals('/index?page=2', $result);

        $options = ['sort' => 'Article', 'direction' => 'desc'];
        $result = $this->Paginator->generateUrl($options);
        $this->assertEquals('/index?page=2&amp;sort=Article&amp;direction=desc', $result);

        $this->Paginator->request->params['paging']['Article']['page'] = 3;
        $options = ['sort' => 'Article.name', 'direction' => 'desc'];
        $result = $this->Paginator->generateUrl($options);
        $this->assertEquals('/index?page=3&amp;sort=Article.name&amp;direction=desc', $result);

        $this->Paginator->request->params['paging']['Article']['page'] = 3;
        $options = ['sort' => 'Article.name', 'direction' => 'desc'];
        $result = $this->Paginator->generateUrl($options, null, ['escape' => false]);
        $this->assertEquals('/index?page=3&sort=Article.name&direction=desc', $result);

        $this->Paginator->request->params['paging']['Article']['page'] = 3;
        $options = ['sort' => 'Article.name', 'direction' => 'desc'];
        $result = $this->Paginator->generateUrl($options, null, ['fullBase' => true]);
        $this->assertEquals('http://localhost/index?page=3&amp;sort=Article.name&amp;direction=desc', $result);

        // @deprecated 3.3.5 Use fullBase array option instead.
        $this->Paginator->request->params['paging']['Article']['page'] = 3;
        $options = ['sort' => 'Article.name', 'direction' => 'desc'];
        $result = $this->Paginator->generateUrl($options, null, true);
        $this->assertEquals('http://localhost/index?page=3&amp;sort=Article.name&amp;direction=desc', $result);
    }

    /**
     * test URL generation with prefix routes
     *
     * @return void
     */
    public function testGenerateUrlWithPrefixes()
    {
        Configure::write('Routing.prefixes', ['members']);
        Router::reload();
        Router::connect('/members/:controller/:action/*', ['prefix' => 'members']);
        Router::connect('/:controller/:action/*');

        Router::setRequestInfo([
            ['controller' => 'posts', 'action' => 'index', 'plugin' => null],
            ['base' => '', 'here' => 'posts/index', 'webroot' => '/']
        ]);

        $this->Paginator->request->params['paging']['Article']['page'] = 2;
        $this->Paginator->request->params['paging']['Article']['prevPage'] = true;
        $options = ['prefix' => 'members'];

        $result = $this->Paginator->generateUrl($options);
        $expected = '/members/posts/index?page=2';
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->sort('name', null, ['url' => $options]);
        $expected = [
            'a' => ['href' => '/members/posts/index?page=2&amp;sort=name&amp;direction=asc'],
            'Name',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('next', ['url' => $options]);
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['href' => '/members/posts/index?page=3', 'rel' => 'next'],
            'next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->prev('prev', ['url' => $options]);
        $expected = [
            'li' => ['class' => 'prev'],
            'a' => ['href' => '/members/posts/index', 'rel' => 'prev'],
            'prev',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $options = ['prefix' => 'members', 'controller' => 'posts', 'sort' => 'name', 'direction' => 'desc'];
        $result = $this->Paginator->generateUrl($options);
        $expected = '/members/posts/index?page=2&amp;sort=name&amp;direction=desc';
        $this->assertEquals($expected, $result);

        $options = ['controller' => 'posts', 'sort' => 'Article.name', 'direction' => 'desc'];
        $result = $this->Paginator->generateUrl($options);
        $expected = '/posts/index?page=2&amp;sort=Article.name&amp;direction=desc';
        $this->assertEquals($expected, $result);
    }

    /**
     * test generateUrl with multiple pagination
     *
     * @return void
     */
    public function testGenerateUrlMultiplePagination()
    {
        Router::setRequestInfo([
            ['controller' => 'posts', 'action' => 'index', 'plugin' => null],
            ['base' => '', 'here' => 'posts/index', 'webroot' => '/']
        ]);

        $this->Paginator->request->params['paging']['Article']['scope'] = 'article';
        $this->Paginator->request->params['paging']['Article']['page'] = 3;
        $this->Paginator->request->params['paging']['Article']['prevPage'] = true;
        $this->Paginator->options(['model' => 'Article']);

        $result = $this->Paginator->generateUrl([]);
        $expected = '/posts/index?article%5Bpage%5D=3';
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->sort('name');
        $expected = [
            'a' => ['href' => '/posts/index?article%5Bpage%5D=3&amp;article%5Bsort%5D=name&amp;article%5Bdirection%5D=asc'],
            'Name',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('next');
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['href' => '/posts/index?article%5Bpage%5D=4', 'rel' => 'next'],
            'next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->prev('prev');
        $expected = [
            'li' => ['class' => 'prev'],
            'a' => ['href' => '/posts/index?article%5Bpage%5D=2', 'rel' => 'prev'],
            'prev',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->generateUrl(['sort' => 'name']);
        $expected = '/posts/index?article%5Bpage%5D=3&amp;article%5Bsort%5D=name';
        $this->assertEquals($expected, $result);
    }

    /**
     * test generateUrl with multiple pagination and query string values
     *
     * @return void
     */
    public function testGenerateUrlMultiplePaginationQueryStringData()
    {
        Router::setRequestInfo([
            ['controller' => 'posts', 'action' => 'index', 'plugin' => null],
            ['base' => '', 'here' => 'posts/index', 'webroot' => '/']
        ]);
        $this->View->request->params['paging']['Article']['scope'] = 'article';
        $this->View->request->params['paging']['Article']['page'] = 3;
        $this->View->request->params['paging']['Article']['prevPage'] = true;
        $this->View->request->query = [
            'article' => [
                'puppy' => 'no'
            ]
        ];
        // Need to run __construct to update _config['url']
        $paginator = new PaginatorHelper($this->View);
        $paginator->options(['model' => 'Article']);

        $result = $paginator->generateUrl(['sort' => 'name']);
        $expected = '/posts/index?article%5Bpage%5D=3&amp;article%5Bsort%5D=name&amp;article%5Bpuppy%5D=no';
        $this->assertEquals($expected, $result);

        $result = $paginator->generateUrl([]);
        $expected = '/posts/index?article%5Bpage%5D=3&amp;article%5Bpuppy%5D=no';
        $this->assertEquals($expected, $result);
    }

    /**
     * testOptions method
     *
     * @return void
     */
    public function testOptions()
    {
        $this->Paginator->options = [];
        $this->Paginator->request->params = [];

        $options = ['paging' => ['Article' => [
            'direction' => 'desc',
            'sort' => 'title'
        ]]];
        $this->Paginator->options($options);

        $expected = ['Article' => [
            'direction' => 'desc',
            'sort' => 'title'
        ]];
        $this->assertEquals($expected, $this->Paginator->request->params['paging']);

        $this->Paginator->options = [];
        $this->Paginator->request->params = [];

        $options = ['Article' => [
            'direction' => 'desc',
            'sort' => 'title'
        ]];
        $this->Paginator->options($options);
        $this->assertEquals($expected, $this->Paginator->request->params['paging']);

        $options = ['paging' => ['Article' => [
            'direction' => 'desc',
            'sort' => 'Article.title'
        ]]];
        $this->Paginator->options($options);

        $expected = ['Article' => [
            'direction' => 'desc',
            'sort' => 'Article.title'
        ]];
        $this->assertEquals($expected, $this->Paginator->request->params['paging']);
    }

    /**
     * testPassedArgsMergingWithUrlOptions method
     *
     * @return void
     */
    public function testPassedArgsMergingWithUrlOptions()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'articles', 'action' => 'index', 'pass' => ['2']],
            ['base' => '/', 'here' => '/articles/', 'webroot' => '/']
        ]);
        $this->Paginator->request->params['paging'] = [
            'Article' => [
                'page' => 1, 'current' => 3, 'count' => 13,
                'prevPage' => false, 'nextPage' => true, 'pageCount' => 8,
                'sort' => null, 'direction' => null,
            ]
        ];

        $this->Paginator->request->params['pass'] = [2];
        $this->Paginator->request->query = ['page' => 1, 'foo' => 'bar', 'x' => 'y', 'num' => 0];
        $this->View->request = $this->Paginator->request;
        $this->Paginator = new PaginatorHelper($this->View);

        $result = $this->Paginator->sort('title');
        $expected = [
            'a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;sort=title&amp;direction=asc'],
            'Title',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers();
        $expected = [
            ['li' => ['class' => 'active']], '<a href=""', '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;page=7']], '7', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('Next');
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['href' => '/articles/index/2?foo=bar&amp;x=y&amp;num=0&amp;page=2', 'rel' => 'next'],
            'Next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that generated URLs don't include sort and direction parameters
     *
     * @return void
     */
    public function testDefaultSortRemovedFromUrl()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'articles', 'action' => 'index'],
            ['base' => '/', 'here' => '/articles/', 'webroot' => '/']
        ]);
        $this->Paginator->request->params['paging'] = [
            'Article' => [
                'page' => 1, 'current' => 3, 'count' => 13,
                'prevPage' => false, 'nextPage' => true, 'pageCount' => 8,
                'sort' => 'Article.title', 'direction' => 'ASC',
                'sortDefault' => 'Article.title', 'directionDefault' => 'ASC'
            ]
        ];
        $result = $this->Paginator->next('Next');
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['rel' => 'next', 'href' => '/articles/index?page=2'],
            'Next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test the prev() method.
     *
     * @return void
     */
    public function testPrev()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 1,
                'current' => 3,
                'count' => 13,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 5,
            ]
        ];
        $result = $this->Paginator->prev('<< Previous');
        $expected = [
            'li' => ['class' => 'prev disabled'],
            'a' => ['href' => '', 'onclick' => 'return false;'],
            '&lt;&lt; Previous',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->prev('<< Previous', ['disabledTitle' => 'Prev']);
        $expected = [
            'li' => ['class' => 'prev disabled'],
            'a' => ['href' => '', 'onclick' => 'return false;'],
            'Prev',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->prev('<< Previous', ['disabledTitle' => false]);
        $this->assertEquals('', $result, 'disabled + no text = no link');

        $this->Paginator->request->params['paging']['Client']['page'] = 2;
        $this->Paginator->request->params['paging']['Client']['prevPage'] = true;
        $result = $this->Paginator->prev('<< Previous');
        $expected = [
            'li' => ['class' => 'prev'],
            'a' => ['href' => '/index', 'rel' => 'prev'],
            '&lt;&lt; Previous',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->prev('Prev', [
            'templates' => [
                'prevActive' => '<a rel="prev" href="{{url}}">{{text}}</a>'
            ]
        ]);
        $expected = [
            'a' => ['href' => '/index', 'rel' => 'prev'],
            'Prev',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that prev() and the shared implementation underneath picks up from optins
     *
     * @return void
     */
    public function testPrevWithOptions()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 2, 'current' => 1, 'count' => 13, 'prevPage' => true,
                'nextPage' => false, 'pageCount' => 2,
                'limit' => 10,
            ]
        ];
        $this->Paginator->options(['url' => [12, 'page' => 3]]);
        $result = $this->Paginator->prev('Prev', ['url' => ['foo' => 'bar']]);
        $expected = [
            'li' => ['class' => 'prev'],
            'a' => ['href' => '/index/12?limit=10&amp;foo=bar', 'rel' => 'prev'],
            'Prev',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test the next() method.
     *
     * @return void
     */
    public function testNext()
    {
        $result = $this->Paginator->next('Next >>');
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['href' => '/index?page=2', 'rel' => 'next'],
            'Next &gt;&gt;',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('Next', [
            'templates' => [
                'nextActive' => '<a rel="next" href="{{url}}">{{text}}</a>'
            ]
        ]);
        $expected = [
            'a' => ['href' => '/index?page=2', 'rel' => 'next'],
            'Next',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('Next >>', ['escape' => false]);
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['href' => '/index?page=2', 'rel' => 'next'],
            'preg:/Next >>/',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test next() with disabled links
     *
     * @return void
     */
    public function testNextDisabled()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 5,
                'current' => 3,
                'count' => 13,
                'prevPage' => true,
                'nextPage' => false,
                'pageCount' => 5,
            ]
        ];
        $result = $this->Paginator->next('Next >>');
        $expected = [
            'li' => ['class' => 'next disabled'],
            'a' => ['href' => '', 'onclick' => 'return false;'],
            'Next &gt;&gt;',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('Next >>', ['disabledTitle' => 'Next']);
        $expected = [
            'li' => ['class' => 'next disabled'],
            'a' => ['href' => '', 'onclick' => 'return false;'],
            'Next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->next('Next >>', ['disabledTitle' => false]);
        $this->assertEquals('', $result, 'disabled + no text = no link');
    }

    /**
     * Test next() with a model argument.
     *
     * @return void
     */
    public function testNextAndPrevNonDefaultModel()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 1,
                'current' => 3,
                'count' => 13,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 5,
            ],
            'Server' => [
                'page' => 5,
                'current' => 1,
                'count' => 5,
                'prevPage' => true,
                'nextPage' => false,
                'pageCount' => 5,
            ]
        ];
        $result = $this->Paginator->next('Next', [
            'model' => 'Client'
        ]);
        $expected = [
            'li' => ['class' => 'next'],
            'a' => ['href' => '/index?page=2', 'rel' => 'next'],
            'Next',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->prev('Prev', [
            'model' => 'Client'
        ]);
        $expected = '<li class="prev disabled"><a href="" onclick="return false;">Prev</a></li>';
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->next('Next', [
            'model' => 'Server'
        ]);
        $expected = '<li class="next disabled"><a href="" onclick="return false;">Next</a></li>';
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->prev('Prev', [
            'model' => 'Server'
        ]);
        $expected = [
            'li' => ['class' => 'prev'],
            'a' => ['href' => '/index?page=4', 'rel' => 'prev'],
            'Prev',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testNumbers method
     *
     * @return void
     */
    public function testNumbers()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 8,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
            ]
        ];
        $result = $this->Paginator->numbers();
        $expected = [
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10']], '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12']], '12', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers(['first' => 'first', 'last' => 'last']);
        $expected = [
            ['li' => ['class' => 'first']], ['a' => ['href' => '/index']], 'first', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10']], '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12']], '12', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => ['class' => 'last']], ['a' => ['href' => '/index?page=15']], 'last', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers(['first' => '2', 'last' => '8']);
        $expected = [
            ['li' => ['class' => 'first']], ['a' => ['href' => '/index']], '2', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10']], '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12']], '12', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => ['class' => 'last']], ['a' => ['href' => '/index?page=15']], '8', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers(['first' => '8', 'last' => '8']);
        $expected = [
            ['li' => ['class' => 'first']], ['a' => ['href' => '/index']], '8', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10']], '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12']], '12', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => ['class' => 'last']], ['a' => ['href' => '/index?page=15']], '8', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 1,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
            ]
        ];
        $result = $this->Paginator->numbers();
        $expected = [
            ['li' => ['class' => 'active']], '<a href=""', '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=8']], '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 14,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
            ]
        ];
        $result = $this->Paginator->numbers();
        $expected = [
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=8']], '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10']], '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12']], '12', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=13']], '13', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '14', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=15']], '15', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 2,
                'current' => 3,
                'count' => 27,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 9,
            ]
        ];

        $result = $this->Paginator->numbers(['first' => 1]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=8']], '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers(['last' => 1]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=8']], '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 15,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
            ]
        ];

        $result = $this->Paginator->numbers(['first' => 1]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=8']], '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10']], '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12']], '12', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=13']], '13', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=14']], '14', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '15', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 10,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
            ]
        ];

        $result = $this->Paginator->numbers(['first' => 1, 'last' => 1]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=8']], '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12']], '12', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=13']], '13', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=14']], '14', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=15']], '15', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 6,
                'current' => 15,
                'count' => 623,
                'prevPage' => 1,
                'nextPage' => 1,
                'pageCount' => 42,
            ]
        ];

        $result = $this->Paginator->numbers(['first' => 1, 'last' => 1]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=8']], '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10']], '10', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=42']], '42', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 37,
                'current' => 15,
                'count' => 623,
                'prevPage' => 1,
                'nextPage' => 1,
                'pageCount' => 42,
            ]
        ];

        $result = $this->Paginator->numbers(['first' => 1, 'last' => 1]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=33']], '33', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=34']], '34', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=35']], '35', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=36']], '36', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '37', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=38']], '38', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=39']], '39', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=40']], '40', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=41']], '41', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=42']], '42', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that numbers() lets you overwrite templates.
     *
     * The templates file has no li elements.
     *
     * @return void
     */
    public function testNumbersTemplates()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 8,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
            ]
        ];
        $result = $this->Paginator->numbers(['templates' => 'htmlhelper_tags']);
        $expected = [
            ['a' => ['href' => '/index?page=4']], '4', '/a',
            ['a' => ['href' => '/index?page=5']], '5', '/a',
            ['a' => ['href' => '/index?page=6']], '6', '/a',
            ['a' => ['href' => '/index?page=7']], '7', '/a',
            'span' => ['class' => 'active'], '8', '/span',
            ['a' => ['href' => '/index?page=9']], '9', '/a',
            ['a' => ['href' => '/index?page=10']], '10', '/a',
            ['a' => ['href' => '/index?page=11']], '11', '/a',
            ['a' => ['href' => '/index?page=12']], '12', '/a',
        ];
        $this->assertHtml($expected, $result);

        $this->assertContains(
            '<li',
            $this->Paginator->templater()->get('current'),
            'Templates were not restored.'
        );
    }

    /**
     * Test modulus option for numbers()
     *
     * @return void
     */
    public function testNumbersModulus()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 1,
                'current' => 10,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 3,
            ]
        ];

        $result = $this->Paginator->numbers(['modulus' => 10]);
        $expected = [
            ['li' => ['class' => 'active']], '<a href=""', '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers(['modulus' => 3]);
        $expected = [
            ['li' => ['class' => 'active']], '<a href=""', '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 4895,
                'current' => 10,
                'count' => 48962,
                'prevPage' => 1,
                'nextPage' => 1,
                'pageCount' => 4897,
            ]
        ];

        $result = $this->Paginator->numbers(['first' => 2, 'modulus' => 2, 'last' => 2]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4894']], '4894', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '4895', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Client']['page'] = 3;

        $result = $this->Paginator->numbers(['first' => 2, 'modulus' => 2, 'last' => 2]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers(['first' => 2, 'modulus' => 2, 'last' => 2]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->numbers(['first' => 5, 'modulus' => 5, 'last' => 5]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4893']], '4893', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4894']], '4894', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4895']], '4895', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Client']['page'] = 4893;
        $result = $this->Paginator->numbers(['first' => 5, 'modulus' => 4, 'last' => 5]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4891']], '4891', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4892']], '4892', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '4893', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4894']], '4894', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4895']], '4895', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Client']['page'] = 58;
        $result = $this->Paginator->numbers(['first' => 5, 'modulus' => 4, 'last' => 5]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=56']], '56', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=57']], '57', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '58', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=59']], '59', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=60']], '60', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4893']], '4893', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4894']], '4894', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4895']], '4895', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Client']['page'] = 5;
        $result = $this->Paginator->numbers(['first' => 5, 'modulus' => 4, 'last' => 5]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7']], '7', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4893']], '4893', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4894']], '4894', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4895']], '4895', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Client']['page'] = 3;
        $result = $this->Paginator->numbers(['first' => 2, 'modulus' => 2, 'last' => 2]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4']], '4', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Client']['page'] = 3;
        $result = $this->Paginator->numbers(['first' => 2, 'modulus' => 0, 'last' => 2]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '3', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests that disabling modulus displays all page links.
     *
     * @return void
     */
    public function testModulusDisabled()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 4,
                'current' => 2,
                'count' => 30,
                'prevPage' => 1,
                'nextPage' => 1,
                'pageCount' => 6,
            ]
        ];

        $result = $this->Paginator->numbers(['modulus' => false]);
        $expected = [
            ['li' => []], '<a href="/index"', '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2']], '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=3']], '3', '/a', '/li',
            ['li' => ['class' => 'active']], ['a' => ['href' => '']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6']], '6', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that numbers() with url options.
     *
     * @return void
     */
    public function testNumbersWithUrlOptions()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 8,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
            ]
        ];
        $result = $this->Paginator->numbers(['url' => ['#' => 'foo']]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index?page=4#foo']], '4', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=5#foo']], '5', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=6#foo']], '6', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=7#foo']], '7', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '8', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=9#foo']], '9', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=10#foo']], '10', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=11#foo']], '11', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=12#foo']], '12', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 3,
                'current' => 10,
                'count' => 48962,
                'prevPage' => 1,
                'nextPage' => 1,
                'pageCount' => 4897,
            ]
        ];
        $result = $this->Paginator->numbers([
            'first' => 2,
            'modulus' => 2,
            'last' => 2,
            'url' => ['foo' => 'bar']]);
        $expected = [
            ['li' => []], ['a' => ['href' => '/index?foo=bar']], '1', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=2&amp;foo=bar']], '2', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '3', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4&amp;foo=bar']], '4', '/a', '/li',
            ['li' => ['class' => 'ellipsis']], '...', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4896&amp;foo=bar']], '4896', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/index?page=4897&amp;foo=bar']], '4897', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test numbers() with routing parameters.
     *
     * @return void
     */
    public function testNumbersRouting()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 2,
                'current' => 2,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 3,
                'pageCount' => 3,
            ]
        ];

        $request = new Request();
        $request->addParams([
            'controller' => 'clients', 'action' => 'index', 'plugin' => null
        ]);
        $request->base = '';
        $request->here = '/clients/index?page=2';
        $request->webroot = '/';

        Router::setRequestInfo($request);

        $result = $this->Paginator->numbers();
        $expected = [
            ['li' => []], ['a' => ['href' => '/clients/index']], '1', '/a', '/li',
            ['li' => ['class' => 'active']], '<a href=""', '2', '/a', '/li',
            ['li' => []], ['a' => ['href' => '/clients/index?page=3']], '3', '/a', '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that numbers() works with the non default model.
     *
     * @return void
     */
    public function testNumbersNonDefaultModel()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 1,
                'current' => 3,
                'count' => 13,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 5,
            ],
            'Server' => [
                'page' => 5,
                'current' => 1,
                'count' => 5,
                'prevPage' => true,
                'nextPage' => false,
                'pageCount' => 5,
            ]
        ];
        $result = $this->Paginator->numbers(['model' => 'Server']);
        $this->assertContains('<li class="active"><a href="">5</a></li>', $result);
        $this->assertNotContains('<li class="active"><a href="">1</a></li>', $result);

        $result = $this->Paginator->numbers(['model' => 'Client']);
        $this->assertContains('<li class="active"><a href="">1</a></li>', $result);
        $this->assertNotContains('<li class="active"><a href="">5</a></li>', $result);
    }

    /**
     * test first() and last() with tag options
     *
     * @return void
     */
    public function testFirstAndLastTag()
    {
        $this->Paginator->request->params['paging']['Article']['page'] = 2;
        $result = $this->Paginator->first('<<');
        $expected = [
            'li' => ['class' => 'first'],
            'a' => ['href' => '/index'],
            '&lt;&lt;',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->first('5');
        $expected = [
            'li' => ['class' => 'first'],
            'a' => ['href' => '/index'],
            '5',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->last(2);
        $expected = [
            '<li',
            ['a' => ['href' => '/index?page=6']], '6', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '/index?page=7']], '7', '/a',
            '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->last('9');
        $expected = [
            'li' => ['class' => 'last'],
            'a' => ['href' => '/index?page=7'],
            '9',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test that on the last page you don't get a link ot the last page.
     *
     * @return void
     */
    public function testLastNoOutput()
    {
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
    public function testFirstNonDefaultModel()
    {
        $this->Paginator->request->params['paging']['Article']['page'] = 1;
        $this->Paginator->request->params['paging']['Client'] = [
            'page' => 3,
            'current' => 3,
            'count' => 13,
            'prevPage' => false,
            'nextPage' => true,
            'pageCount' => 5,
        ];

        $result = $this->Paginator->first('first', ['model' => 'Article']);
        $this->assertEquals('', $result);

        $result = $this->Paginator->first('first', ['model' => 'Client']);
        $expected = [
            'li' => ['class' => 'first'],
            'a' => ['href' => '/index'],
            'first',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test first() on the first page.
     *
     * @return void
     */
    public function testFirstEmpty()
    {
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
    public function testFirstFullBaseUrl()
    {
        $this->Paginator->request->params['paging']['Article']['page'] = 3;
        $this->Paginator->request->params['paging']['Article']['direction'] = 'DESC';
        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';

        $this->Paginator->options(['url' => ['_full' => true]]);

        $result = $this->Paginator->first();
        $expected = [
            'li' => ['class' => 'first'],
            ['a' => [
                'href' => Configure::read('App.fullBaseUrl') . '/index?sort=Article.title&amp;direction=DESC'
            ]],
            '&lt;&lt; first',
            '/a',
            '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test first() on the fence-post
     *
     * @return void
     */
    public function testFirstBoundaries()
    {
        $this->Paginator->request->params['paging']['Article']['page'] = 3;
        $result = $this->Paginator->first();
        $expected = [
            'li' => ['class' => 'first'],
            'a' => ['href' => '/index'],
            '&lt;&lt; first',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->first(2);
        $expected = [
            '<li',
            ['a' => ['href' => '/index']], '1', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '/index?page=2']], '2', '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['page'] = 2;
        $result = $this->Paginator->first(3);
        $this->assertEquals('', $result, 'When inside the first links range, no links should be made');
    }

    /**
     * test params() method
     *
     * @return void
     */
    public function testParams()
    {
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
    public function testParam()
    {
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
    public function testLast()
    {
        $result = $this->Paginator->last();
        $expected = [
            'li' => ['class' => 'last'],
            'a' => ['href' => '/index?page=7'],
            'last &gt;&gt;',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->last(1);
        $expected = [
            '<li',
            'a' => ['href' => '/index?page=7'],
            '7',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);

        $this->Paginator->request->params['paging']['Article']['page'] = 6;

        $result = $this->Paginator->last(2);
        $expected = [
            '<li',
            ['a' => ['href' => '/index?page=6']], '6', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '/index?page=7']], '7', '/a',
            '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->last(3);
        $this->assertEquals('', $result, 'When inside the last links range, no links should be made');
    }

    /**
     * test the options for last()
     *
     * @return void
     */
    public function testLastOptions()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 4,
                'current' => 3,
                'count' => 30,
                'prevPage' => false,
                'nextPage' => 2,
                'pageCount' => 15,
                'sort' => 'Client.name',
                'direction' => 'DESC',
            ]
        ];

        $result = $this->Paginator->last();
        $expected = [
            'li' => ['class' => 'last'],
            'a' => [
                'href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC',
            ],
                'last &gt;&gt;', '/a',
            '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->last(1);
        $expected = [
            '<li',
            ['a' => ['href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC']], '15', '/a',
            '/li',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Paginator->last(2);
        $expected = [
            '<li',
            ['a' => ['href' => '/index?page=14&amp;sort=Client.name&amp;direction=DESC']], '14', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '/index?page=15&amp;sort=Client.name&amp;direction=DESC']], '15', '/a',
            '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test last() with a the model parameter.
     *
     * @return void
     */
    public function testLastNonDefaultModel()
    {
        $this->Paginator->request->params['paging']['Article']['page'] = 7;
        $this->Paginator->request->params['paging']['Client'] = [
            'page' => 3,
            'current' => 3,
            'count' => 13,
            'prevPage' => false,
            'nextPage' => true,
            'pageCount' => 5,
        ];

        $result = $this->Paginator->last('last', ['model' => 'Article']);
        $this->assertEquals('', $result);

        $result = $this->Paginator->last('last', ['model' => 'Client']);
        $expected = [
            'li' => ['class' => 'last'],
            'a' => ['href' => '/index?page=5'],
            'last',
            '/a',
            '/li'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCounter method
     *
     * @return void
     */
    public function testCounter()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
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
            ]
        ];
        $input = 'Page {{page}} of {{pages}}, showing {{current}} records out of {{count}} total, ';
        $input .= 'starting on record {{start}}, ending on {{end}}';

        $expected = 'Page 1 of 5, showing 3 records out of 13 total, starting on record 1, ';
        $expected .= 'ending on 3';
        $result = $this->Paginator->counter($input);
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->counter(['format' => 'pages']);
        $expected = '1 of 5';
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->counter(['format' => 'range']);
        $expected = '1 - 3 of 13';
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->counter('Showing {{page}} of {{pages}} {{model}}');
        $this->assertEquals('Showing 1 of 5 clients', $result);
    }

    /**
     * Tests that numbers are formatted according to the locale when using counter()
     *
     * @return void
     */
    public function testCounterBigNumbers()
    {
        $this->Paginator->request->params['paging'] = [
            'Client' => [
                'page' => 1523,
                'current' => 1230,
                'count' => 234567,
                'perPage' => 3000,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 1000,
                'limit' => 5000,
                'sort' => 'Client.name',
                'order' => 'DESC',
            ]
        ];

        $input = 'Page {{page}} of {{pages}}, showing {{current}} records out of {{count}} total, ';
        $input .= 'starting on record {{start}}, ending on {{end}}';

        $expected = 'Page 1,523 of 1,000, showing 1,230 records out of 234,567 total, ';
        $expected .= 'starting on record 4,566,001, ending on 234,567';
        $result = $this->Paginator->counter($input);
        $this->assertEquals($expected, $result);

        I18n::locale('de-DE');
        $expected = 'Page 1.523 of 1.000, showing 1.230 records out of 234.567 total, ';
        $expected .= 'starting on record 4.566.001, ending on 234.567';
        $result = $this->Paginator->counter($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * testHasPage method
     *
     * @return void
     */
    public function testHasPage()
    {
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
    public function testNextLinkUsingDotNotation()
    {
        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => []],
            ['base' => '', 'here' => '/accounts/', 'webroot' => '/']
        ]);

        $this->Paginator->request->params['paging']['Article']['sort'] = 'Article.title';
        $this->Paginator->request->params['paging']['Article']['direction'] = 'asc';
        $this->Paginator->request->params['paging']['Article']['page'] = 1;

        $test = ['url' => [
            'page' => '1',
            'sort' => 'Article.title',
            'direction' => 'asc',
        ]];
        $this->Paginator->options($test);

        $result = $this->Paginator->next('Next');
        $expected = [
            'li' => ['class' => 'next'],
            'a' => [
                'href' => '/accounts/index?page=2&amp;sort=Article.title&amp;direction=asc',
                'rel' => 'next'
            ],
            'Next',
            '/a',
            '/li',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test the current() method
     *
     * @return void
     */
    public function testCurrent()
    {
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
    public function testNoDefaultModel()
    {
        $this->Paginator->request = new Request();
        $this->assertNull($this->Paginator->defaultModel());

        $this->Paginator->defaultModel('Article');
        $this->assertEquals('Article', $this->Paginator->defaultModel());

        $this->Paginator->options(['model' => 'Client']);
        $this->assertEquals('Client', $this->Paginator->defaultModel());
    }

    /**
     * test the numbers() method when there is only one page
     *
     * @return void
     */
    public function testWithOnePage()
    {
        $this->Paginator->request['paging'] = [
            'Article' => [
                'page' => 1,
                'current' => 2,
                'count' => 2,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 1,
            ]
        ];
        $this->assertFalse($this->Paginator->numbers());
        $this->assertFalse($this->Paginator->first());
        $this->assertFalse($this->Paginator->last());
    }

    /**
     * test the numbers() method when there is only one page
     *
     * @return void
     */
    public function testWithZeroPages()
    {
        $this->Paginator->request['paging'] = [
            'Article' => [
                'page' => 0,
                'current' => 0,
                'count' => 0,
                'perPage' => 10,
                'prevPage' => false,
                'nextPage' => false,
                'pageCount' => 0,
                'limit' => 10,
            ]
        ];

        $result = $this->Paginator->counter(['format' => 'pages']);
        $expected = '0 of 1';
        $this->assertEquals($expected, $result);
    }

    /**
     * Verifies that no next and prev links are created for single page results.
     *
     * @return void
     */
    public function testMetaPage0()
    {
        $this->Paginator->request['paging'] = [
            'Article' => [
                'page' => 1,
                'prevPage' => false,
                'nextPage' => false,
                'pageCount' => 1,
            ]
        ];

        $expected = '';
        $result = $this->Paginator->meta();
        $this->assertSame($expected, $result);
    }

    /**
     * Verifies that page 1 only has a next link.
     *
     * @return void
     */
    public function testMetaPage1()
    {
        $this->Paginator->request['paging'] = [
            'Article' => [
                'page' => 1,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 2,
            ]
        ];

        $expected = '<link rel="next" href="http://localhost/index?page=2"/>';
        $result = $this->Paginator->meta();
        $this->assertSame($expected, $result);
    }

    /**
     * Verifies that the method will append to a block.
     *
     * @return void
     */
    public function testMetaPage1InlineFalse()
    {
        $this->Paginator->request['paging'] = [
            'Article' => [
                'page' => 1,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 2,
            ]
        ];

        $expected = '<link rel="next" href="http://localhost/index?page=2"/>';
        $this->Paginator->meta(['block' => true]);
        $result = $this->View->fetch('meta');
        $this->assertSame($expected, $result);
    }

    /**
     * Verifies that the last page only has a prev link.
     *
     * @return void
     */
    public function testMetaPage1Last()
    {
        $this->Paginator->request['paging'] = [
            'Article' => [
                'page' => 2,
                'prevPage' => true,
                'nextPage' => false,
                'pageCount' => 2,
            ]
        ];

        $expected = '<link rel="prev" href="http://localhost/index"/>';
        $result = $this->Paginator->meta();

        $this->assertSame($expected, $result);
    }

    /**
     * Verifies that a page in the middle has both links.
     *
     * @return void
     */
    public function testMetaPage10Last()
    {
        $this->Paginator->request['paging'] = [
            'Article' => [
                'page' => 5,
                'prevPage' => true,
                'nextPage' => true,
                'pageCount' => 10,
            ]
        ];

        $expected = '<link rel="prev" href="http://localhost/index?page=4"/>';
        $expected .= '<link rel="next" href="http://localhost/index?page=6"/>';
        $result = $this->Paginator->meta();
        $this->assertSame($expected, $result);
    }
}
