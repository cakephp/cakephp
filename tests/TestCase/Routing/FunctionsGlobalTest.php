<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

require_once CAKE . 'Routing/functions_global.php';

class FunctionsGlobalTest extends TestCase
{
    /**
     * Tests that the url() method is a shortcut Router::url()
     */
    public function testUrl(): void
    {
        $routes = Router::createRouteBuilder('/');
        $routes->fallbacks();

        $routerResult = Router::url(['controller' => 'Articles']);
        $globalResult = url(['controller' => 'Articles']);
        $this->assertSame($routerResult, $globalResult);
    }

    /**
     * Tests that the urlArray() method is a shortcut Router::parseRoutePath()
     */
    public function testUrlArray(): void
    {
        $routes = Router::createRouteBuilder('/');
        $routes->fallbacks();

        $routerResult = Router::parseRoutePath('Controller::articles');
        $globalResult = urlArray('Controller::articles');
        $this->assertSame($globalResult, $routerResult + ['plugin' => false, 'prefix' => false]);
    }
}
