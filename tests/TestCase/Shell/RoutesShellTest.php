<?php
/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Routing\Router;
use Cake\Shell\RoutesShell;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * RoutesShellTest
 */
class RoutesShellTest extends ConsoleIntegrationTestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Router::connect('/articles/:action/*', ['controller' => 'Articles']);
        Router::connect('/bake/:controller/:action', ['plugin' => 'Bake']);
        Router::connect('/tests/:action/*', ['controller' => 'Tests'], ['_name' => 'testName']);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Router::reload();
    }

    /**
     * Check that a row of cells exists in the output.
     *
     * @param array $row The row of cells to check
     * @return void
     */
    protected function assertOutputContainsRow(array $row)
    {
        $row = array_map(function ($cell) {
            return preg_quote($cell, '/');
        }, $row);
        $cells = implode('\s+\|\s+', $row);
        $pattern = '/' . $cells . '/';
        $this->assertOutputRegexp($pattern);
    }

    /**
     * Test checking an non-existing route.
     *
     * @return void
     */
    public function testMain()
    {
        $this->exec('routes');
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>'
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/articles/:action/*',
            '{"controller":"Articles","action":"index","plugin":null}'
        ]);
        $this->assertOutputContainsRow([
            'bake._controller:_action',
            '/bake/:controller/:action',
            '{"plugin":"Bake","action":"index"}'
        ]);
        $this->assertOutputContainsRow([
            'testName',
            '/tests/:action/*',
            '{"controller":"Tests","action":"index","plugin":null}'
        ]);
    }

    /**
     * Test checking an existing route.
     *
     * @return void
     */
    public function testCheck()
    {
        $this->exec('routes check /articles/check');
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>'
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/articles/check',
            '{"action":"check","pass":[],"controller":"Articles","plugin":null}'
        ]);
    }

    /**
     * Test checking an existing route with named route.
     *
     * @return void
     */
    public function testCheckWithNamedRoute()
    {
        $this->exec('routes check /tests/index');
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>'
        ]);
        $this->assertOutputContainsRow([
            'testName',
            '/tests/index',
            '{"action":"index","pass":[],"controller":"Tests","plugin":null}'
        ]);
    }

    /**
     * Test checking an non-existing route.
     *
     * @return void
     */
    public function testCheckNotFound()
    {
        $this->exec('routes check /nope');
        $this->assertErrorContains('did not match');
    }

    /**
     * Test generating URLs
     *
     * @return void
     */
    public function testGenerateNoPassArgs()
    {
        $this->exec('routes generate controller:Articles action:index');
        $this->assertOutputContains('> /articles/index');
        $this->assertErrorEmpty();
    }

    /**
     * Test generating URLs with passed arguments
     *
     * @return void
     */
    public function testGeneratePassedArguments()
    {
        $this->exec('routes generate controller:Articles action:view 2 3');
        $this->assertOutputContains('> /articles/view/2/3');
        $this->assertErrorEmpty();
    }

    /**
     * Test generating URLs with bool params
     *
     * @return void
     */
    public function testGenerateBoolParams()
    {
        $this->exec('routes generate controller:Articles action:index _ssl:true _host:example.com');
        $this->assertOutputContains('> https://example.com/articles/index');
    }

    /**
     * Test generating URLs
     *
     * @return void
     */
    public function testGenerateMissing()
    {
        $this->exec('routes generate controller:Derp');
        $this->assertErrorContains('do not match');
    }
}
