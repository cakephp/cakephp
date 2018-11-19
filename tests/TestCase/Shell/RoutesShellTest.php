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

use Cake\Console\Shell;
use Cake\Routing\Router;
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
     * Test checking an non-existing route.
     *
     * @return void
     */
    public function testMain()
    {
        $this->exec('routes');
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>'
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/articles/:action/*',
            '{"action":"index","controller":"Articles","plugin":null}'
        ]);
        $this->assertOutputContainsRow([
            'bake._controller:_action',
            '/bake/:controller/:action',
            '{"action":"index","plugin":"Bake"}'
        ]);
        $this->assertOutputContainsRow([
            'testName',
            '/tests/:action/*',
            '{"action":"index","controller":"Tests","plugin":null}'
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
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>'
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/articles/check',
            '{"action":"check","controller":"Articles","pass":[],"plugin":null}'
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
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>'
        ]);
        $this->assertOutputContainsRow([
            'testName',
            '/tests/index',
            '{"_name":"testName","action":"index","controller":"Tests","pass":[],"plugin":null}'
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
        $this->assertExitCode(Shell::CODE_ERROR);
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
        $this->assertExitCode(Shell::CODE_SUCCESS);
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
        $this->assertExitCode(Shell::CODE_SUCCESS);
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
        $this->assertExitCode(Shell::CODE_SUCCESS);
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
        $this->assertExitCode(Shell::CODE_ERROR);
        $this->assertErrorContains('do not match');
    }
}
