<?php
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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Routing\Route\PluginShortRoute;
use Cake\TestSuite\TestCase;

/**
 * test case for PluginShortRoute
 */
class PluginShortRouteTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Routing', ['admin' => null, 'prefixes' => []]);
        Router::reload();
    }

    /**
     * test the parsing of routes.
     *
     * @return void
     */
    public function testParsing()
    {
        $route = new PluginShortRoute('/:plugin', ['action' => 'index'], ['plugin' => 'foo|bar']);

        $result = $route->parse('/foo', 'GET');
        $this->assertEquals('Foo', $result['plugin']);
        $this->assertEquals('Foo', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result = $route->parse('/wrong', 'GET');
        $this->assertFalse($result, 'Wrong plugin name matched %s');
    }

    /**
     * test the reverse routing of the plugin shortcut URLs.
     *
     * @return void
     */
    public function testMatch()
    {
        $route = new PluginShortRoute('/:plugin', ['action' => 'index'], ['plugin' => 'foo|bar']);

        $result = $route->match(['plugin' => 'foo', 'controller' => 'posts', 'action' => 'index']);
        $this->assertFalse($result, 'plugin controller mismatch was converted. %s');

        $result = $route->match(['plugin' => 'foo', 'controller' => 'foo', 'action' => 'index']);
        $this->assertEquals('/foo', $result);
    }
}
