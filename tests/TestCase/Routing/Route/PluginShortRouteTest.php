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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Core\Configure;
use Cake\Routing\Route\PluginShortRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * test case for PluginShortRoute
 */
class PluginShortRouteTest extends TestCase
{
    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Routing', ['prefixes' => []]);
        Router::reload();
    }

    /**
     * test the parsing of routes.
     */
    public function testParsing(): void
    {
        $route = new PluginShortRoute('/{plugin}', ['action' => 'index'], ['plugin' => 'foo|bar']);

        $result = $route->parse('/foo', 'GET');
        $this->assertSame('Foo', $result['plugin']);
        $this->assertSame('Foo', $result['controller']);
        $this->assertSame('index', $result['action']);

        $result = $route->parse('/wrong', 'GET');
        $this->assertNull($result, 'Wrong plugin name matched %s');
    }

    /**
     * test the reverse routing of the plugin shortcut URLs.
     */
    public function testMatch(): void
    {
        $route = new PluginShortRoute('/{plugin}', ['action' => 'index'], ['plugin' => 'foo|bar']);

        $result = $route->match(['plugin' => 'Foo', 'controller' => 'Posts', 'action' => 'index']);
        $this->assertNull($result, 'plugin controller mismatch was converted. %s');

        $result = $route->match(['plugin' => 'Foo', 'controller' => 'Foo', 'action' => 'index']);
        $this->assertSame('/foo', $result);
    }
}
