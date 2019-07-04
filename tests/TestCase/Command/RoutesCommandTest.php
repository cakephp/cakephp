<?php
declare(strict_types=1);

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
namespace Cake\Test\TestCase\Command;

use Cake\Console\Command;
use Cake\Routing\Router;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * RoutesCommandTest
 */
class RoutesCommandTest extends ConsoleIntegrationTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->useCommandRunner();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Router::reload();
    }

    /**
     * Ensure help for `routes` works
     *
     * @return void
     */
    public function testRouteListHelp()
    {
        $this->exec('routes -h');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('list of routes');
        $this->assertErrorEmpty();
    }

    /**
     * Test checking an non-existing route.
     *
     * @return void
     */
    public function testRouteList()
    {
        $this->exec('routes');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>',
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/app/articles/:action/*',
            '{"action":"index","controller":"Articles","plugin":null}',
        ]);
        $this->assertOutputContainsRow([
            'bake._controller:_action',
            '/bake/:controller/:action',
            '{"action":"index","plugin":"Bake"}',
        ]);
        $this->assertOutputContainsRow([
            'testName',
            '/app/tests/:action/*',
            '{"action":"index","controller":"Tests","plugin":null}',
        ]);
    }

    /**
     * Ensure help for `routes` works
     *
     * @return void
     */
    public function testCheckHelp()
    {
        $this->exec('routes check -h');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Check a URL');
        $this->assertErrorEmpty();
    }

    /**
     * Test checking an existing route.
     *
     * @return void
     */
    public function testCheck()
    {
        $this->exec('routes check /app/articles/check');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>',
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/app/articles/check',
            '{"action":"check","controller":"Articles","pass":[],"plugin":null}',
        ]);
    }

    /**
     * Test checking an existing route with named route.
     *
     * @return void
     */
    public function testCheckWithNamedRoute()
    {
        $this->exec('routes check /app/tests/index');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Defaults</info>',
        ]);
        $this->assertOutputContainsRow([
            'testName',
            '/app/tests/index',
            '{"_name":"testName","action":"index","controller":"Tests","pass":[],"plugin":null}',
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
        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('did not match');
    }

    /**
     * Ensure help for `routes` works
     *
     * @return void
     */
    public function testGenerareHelp()
    {
        $this->exec('routes generate -h');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Check a routing array');
        $this->assertErrorEmpty();
    }

    /**
     * Test generating URLs
     *
     * @return void
     */
    public function testGenerateNoPassArgs()
    {
        $this->exec('routes generate controller:Articles action:index');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('> /app/articles');
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
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('> /app/articles/view/2/3');
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
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('> https://example.com/app/articles');
    }

    /**
     * Test generating URLs
     *
     * @return void
     */
    public function testGenerateMissing()
    {
        $this->exec('routes generate plugin:Derp controller:Derp');
        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('do not match');
    }
}
