<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.2.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Routing\Route\InflectedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Test case for InflectedRoute
 */
class InflectedRouteTest extends TestCase
{
    /**
     * test that routes match their pattern.
     */
    public function testMatchBasic(): void
    {
        $route = new InflectedRoute('/{controller}/{action}/{id}', ['plugin' => null]);
        $result = $route->match(['controller' => 'Posts', 'action' => 'my_view', 'plugin' => null]);
        $this->assertNull($result);

        $result = $route->match([
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'my_view',
            0,
        ]);
        $this->assertNull($result);

        $result = $route->match([
            'plugin' => null,
            'controller' => 'MyPosts',
            'action' => 'my_view',
            'id' => 1,
        ]);
        $this->assertSame('/my_posts/my_view/1', $result);

        $route = new InflectedRoute('/', ['controller' => 'Pages', 'action' => 'my_view', 'home']);
        $result = $route->match(['controller' => 'Pages', 'action' => 'my_view', 'home']);
        $this->assertSame('/', $result);

        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 'about']);
        $this->assertNull($result);

        $route = new InflectedRoute('/blog/{action}', ['controller' => 'Posts']);
        $result = $route->match(['controller' => 'Posts', 'action' => 'my_view']);
        $this->assertSame('/blog/my_view', $result);

        $result = $route->match(['controller' => 'Posts', 'action' => 'my_view', '?' => ['id' => 2]]);
        $this->assertSame('/blog/my_view?id=2', $result);

        $result = $route->match(['controller' => 'Posts', 'action' => 'my_view', 1]);
        $this->assertNull($result);

        $route = new InflectedRoute('/foo/{controller}/{action}', ['action' => 'index']);
        $result = $route->match(['controller' => 'Posts', 'action' => 'my_view']);
        $this->assertSame('/foo/posts/my_view', $result);

        $route = new InflectedRoute('/{plugin}/{id}/*', ['controller' => 'Posts', 'action' => 'my_view']);
        $result = $route->match([
            'plugin' => 'TestPlugin',
            'controller' => 'Posts',
            'action' => 'my_view',
            'id' => '1',
        ]);
        $this->assertSame('/test_plugin/1/', $result);

        $result = $route->match([
            'plugin' => 'TestPlugin',
            'controller' => 'Posts',
            'action' => 'my_view',
            'id' => '1',
            '0',
        ]);
        $this->assertSame('/test_plugin/1/0', $result);

        $result = $route->match([
            'plugin' => 'TestPlugin',
            'controller' => 'Nodes',
            'action' => 'my_view',
            'id' => 1,
        ]);
        $this->assertNull($result);

        $result = $route->match([
            'plugin' => 'TestPlugin',
            'controller' => 'Posts',
            'action' => 'edit',
            'id' => 1,
        ]);
        $this->assertNull($result);

        $route = new InflectedRoute('/admin/subscriptions/{action}/*', [
            'controller' => 'Subscribe', 'prefix' => 'Admin',
        ]);
        $result = $route->match([
            'controller' => 'Subscribe',
            'prefix' => 'Admin',
            'action' => 'edit_admin_e',
            1,
        ]);
        $expected = '/admin/subscriptions/edit_admin_e/1';
        $this->assertSame($expected, $result);

        $route = new InflectedRoute('/{controller}/{action}-{id}');
        $result = $route->match([
            'controller' => 'MyPosts',
            'action' => 'my_view',
            'id' => 1,
        ]);
        $this->assertSame('/my_posts/my_view-1', $result);

        $route = new InflectedRoute('/{controller}/{action}/{slug}-{id}', [], ['id' => Router::ID]);
        $result = $route->match([
            'controller' => 'MyPosts',
            'action' => 'my_view',
            'id' => '1',
            'slug' => 'the-slug',
        ]);
        $this->assertSame('/my_posts/my_view/the-slug-1', $result);
    }

    /**
     * test the parse method of InflectedRoute.
     */
    public function testParse(): void
    {
        $route = new InflectedRoute('/{controller}/{action}/{id}', [], ['id' => Router::ID]);
        $route->compile();
        $result = $route->parse('/my_posts/my_view/1', 'GET');
        $this->assertSame('MyPosts', $result['controller']);
        $this->assertSame('my_view', $result['action']);
        $this->assertSame('1', $result['id']);

        $route = new InflectedRoute('/{controller}/{action}-{id}');
        $route->compile();
        $result = $route->parse('/my_posts/my_view-1', 'GET');
        $this->assertSame('MyPosts', $result['controller']);
        $this->assertSame('my_view', $result['action']);
        $this->assertSame('1', $result['id']);

        $route = new InflectedRoute('/{controller}/{action}/{slug}-{id}', [], ['id' => Router::ID]);
        $route->compile();
        $result = $route->parse('/my_posts/my_view/the-slug-1', 'GET');
        $this->assertSame('MyPosts', $result['controller']);
        $this->assertSame('my_view', $result['action']);
        $this->assertSame('1', $result['id']);
        $this->assertSame('the-slug', $result['slug']);

        $route = new InflectedRoute(
            '/admin/{controller}',
            ['prefix' => 'Admin', 'action' => 'index']
        );
        $route->compile();
        $result = $route->parse('/admin/', 'GET');
        $this->assertNull($result);

        $result = $route->parse('/admin/my_posts', 'GET');
        $this->assertSame('MyPosts', $result['controller']);
        $this->assertSame('index', $result['action']);

        $route = new InflectedRoute(
            '/media/search/*',
            ['controller' => 'Media', 'action' => 'search_it']
        );
        $result = $route->parse('/media/search', 'GET');
        $this->assertSame('Media', $result['controller']);
        $this->assertSame('search_it', $result['action']);
        $this->assertEquals([], $result['pass']);

        $result = $route->parse('/media/search/tv_shows', 'GET');
        $this->assertSame('Media', $result['controller']);
        $this->assertSame('search_it', $result['action']);
        $this->assertEquals(['tv_shows'], $result['pass']);
    }

    /**
     * Test that parse() checks methods.
     */
    public function testParseMethodMatch(): void
    {
        $route = new InflectedRoute('/{controller}/{action}', ['_method' => 'POST']);
        $this->assertNull($route->parse('/blog_posts/add_new', 'GET'));

        $result = $route->parse('/blog_posts/add_new', 'POST');
        $this->assertSame('BlogPosts', $result['controller']);
        $this->assertSame('add_new', $result['action']);
    }

    public function testMatchThenParse(): void
    {
        $route = new InflectedRoute('/plugin/{controller}/{action}', [
            'plugin' => 'Vendor/PluginName',
        ]);
        $url = $route->match([
            'plugin' => 'Vendor/PluginName',
            'controller' => 'ControllerName',
            'action' => 'action_name',
        ]);
        $expectedUrl = '/plugin/controller_name/action_name';
        $this->assertSame($expectedUrl, $url);
        $result = $route->parse($expectedUrl, 'GET');
        $this->assertSame('ControllerName', $result['controller']);
        $this->assertSame('action_name', $result['action']);
        $this->assertSame('Vendor/PluginName', $result['plugin']);
    }

    public function testMatchDoesNotCorruptDefaults()
    {
        $route = new InflectedRoute('/user_permissions/edit', ['controller' => 'UserPermissions', 'action' => 'edit']);
        $route->match(['controller' => 'UserPermissions', 'action' => 'edit'], []);

        $this->assertSame('UserPermissions', $route->defaults['controller']);
        $this->assertSame('edit', $route->defaults['action']);

        // Do the match again to ensure that state doesn't become incorrect.
        $route->match(['controller' => 'UserPermissions', 'action' => 'edit'], []);
        $this->assertSame('UserPermissions', $route->defaults['controller']);
        $this->assertSame('edit', $route->defaults['action']);
    }
}
