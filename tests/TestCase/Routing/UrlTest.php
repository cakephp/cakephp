<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Routing;

use Cake\Routing\Router;
use Cake\Routing\Url;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;

/**
 * UrlTest class
 */
class UrlTest extends TestCase
{

    /**
     * testUrl
     *
     * @return void
     */
    public function testUrl()
    {
        $url = (new Url())
            ->setAction('index')
            ->setController('Users');

        $expected = [
            'action' => 'index',
            'controller' => 'Users'
        ];
        $this->assertEquals($expected, $url->toArray());
        $this->assertEquals('/Users', ((string)($url)));

        $url->setAbsolute(true);
        $this->assertEquals('http://localhost/Users', ((string)($url)));

        $url->setAbsolute(false);
        $url->setQueryParams(['foo' => 'bar', 'one' => 'two']);
        $this->assertEquals('/Users?foo=bar&one=two', ((string)($url)));

        $url->setQuery('three', 3);
        $this->assertEquals('/Users?foo=bar&one=two&three=3', ((string)($url)));

        $url->addPass('passed');
        $this->assertEquals('/Users/index/passed?foo=bar&one=two&three=3', (string)$url);
    }

    /**
     * testPluginUrls
     *
     * @return void
     */
    public function testPluginUrls()
    {
        Router::connect('/company/three/:controller/:action', [
            'plugin' => 'Company/TestPluginThree'
        ]);

        $url = (new Url())
            ->setPlugin('Company/TestPluginThree')
            ->setAction('index')
            ->setController('Users');

        Router::connect('/company/three/:controller/:action', [
            'plugin' => 'Company/TestPluginThree'
        ]);

        $this->assertEquals('/company/three/Users/index', $url->toString());
    }

    /**
     * testPrefixedUrls
     *
     * @return void
     */
    public function testPrefixedUrls()
    {
        Router::prefix('admin', function ($routes) {
            $routes->connect('/company/three/:controller/:action', [
                'plugin' => 'Company/TestPluginThree'
            ]);
        });

        $url = (new Url())
            ->setPrefix('admin')
            ->setPlugin('Company/TestPluginThree')
            ->setAction('index')
            ->setController('Users');

        $this->assertEquals('/admin/company/three/Users/index', $url->toString());
    }

    /**
     * testArrayAccess
     *
     * @return void
     */
    public function testArrayAccess()
    {
        $url = (new Url());
        $url['controller'] = 'Controller';

        $this->assertNull($url['action']);
        $this->assertFalse(isset($url['action']));
        $this->assertTrue(isset($url['controller']));
        unset($url['controller']);
        $this->assertNull($url['controller']);
        $this->assertFalse(isset($url['controller']));
    }

    /**
     * testCustomRouteParams
     *
     * @return void
     */
    public function testCustomRouteParams()
    {
        Router::connect('/articles/view/:slug', [
            'controller' => 'Articles',
            'action' => 'view',
        ]);

        $url = (new Url())
            ->setController('Articles')
            ->setAction('view')
            ->setParam('slug', 'my-article')
            ->toString();

        $expected = '/articles/view/my-article';
        $this->assertEquals($expected, $url);

        Router::connect('/articles/:category/:slug', [
            'controller' => 'Articles',
            'action' => 'category',
        ]);

        $url = (new Url())
            ->setController('Articles')
            ->setAction('category')
            ->setParams([
                'category' => 'news',
                'slug' => 'my-article'
            ]);

        $expected = '/articles/news/my-article';
        $this->assertEquals($expected, $url->toString());
    }

    /**
     * testHostSchemePortAndFragment
     *
     * @return void
     */
    public function testHostSchemePortAndFragment()
    {
        Router::connect('/articles/view/:slug', [
            'controller' => 'Articles',
            'action' => 'view',
        ]);

        $url = (new Url())
            ->setController('Articles')
            ->setAction('view')
            ->setParam('slug', 'my-article')
            ->setScheme('https')
            ->setPort(123)
            ->setHost('cakephp.org')
            ->setFragment('fragment')
            ->setAbsolute(true)
            ->toString();

        $expected = 'https://cakephp.org:123/articles/view/my-article#fragment';
        $this->assertEquals($expected, $url);
    }

    /**
     * testInvalidPortException
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Port `999999` is outside of the specified range of 1 to 65535
     */
    public function testInvalidPortException()
    {
        (new Url())->setPort(999999);
    }

    /**
     * testRouterIntegration
     *
     * @return void
     */
    public function testRouterIntegration()
    {
        $url = (new Url())
            ->setController('Articles')
            ->setAction('view')
            ->setAbsolute(true);

        $result = Router::url($url);
        $this->assertEquals('http://localhost/Articles/view', $result);
    }

    /**
     * testHtmlHelperIntegration
     *
     * @return void
     */
    public function testHtmlHelperIntegration()
    {
        $view = new View();
        $helper = new HtmlHelper($view);

        $urlObject = (new Url())
            ->setController('Articles')
            ->setAction('view');

        $result = $helper->link('test', $urlObject);
        $this->assertEquals('<a href="/Articles/view">test</a>', $result);
    }
}
