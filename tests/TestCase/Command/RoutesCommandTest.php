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

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Routing\Route\Route;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * RoutesCommandTest
 */
class RoutesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->useCommandRunner();
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Router::reload();
    }

    /**
     * Ensure help for `routes` works
     */
    public function testRouteListHelp(): void
    {
        $this->exec('routes -h');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('list of routes');
        $this->assertErrorEmpty();
    }

    /**
     * Test checking an nonexistent route.
     */
    public function testRouteList(): void
    {
        $this->exec('routes');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Plugin</info>',
            '<info>Prefix</info>',
            '<info>Controller</info>',
            '<info>Action</info>',
            '<info>Method(s)</info>',
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/app/articles/{action}/*',
            '',
            '',
            'Articles',
            'index',
            '',
        ]);
        $this->assertOutputContainsRow([
            'bake._controller:_action',
            '/bake/{controller}/{action}',
            'Bake',
            '',
            '',
            'index',
            '',
        ]);
        $this->assertOutputContainsRow([
            'testName',
            '/app/tests/{action}/*',
            '',
            '',
            'Tests',
            'index',
            '',
        ]);
    }

    /**
     * Test routes with --verbose option
     */
    public function testRouteListVerbose(): void
    {
        $this->exec('routes -v');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Plugin</info>',
            '<info>Prefix</info>',
            '<info>Controller</info>',
            '<info>Action</info>',
            '<info>Method(s)</info>',
            '<info>Defaults</info>',
        ]);
        $this->assertOutputContainsRow([
            'articles:_action',
            '/app/articles/{action}/*',
            '',
            '',
            'Articles',
            'index',
            '',
            '{"action":"index","controller":"Articles","plugin":null}',
        ]);
    }

    /**
     * Test routes with --sort option
     */
    public function testRouteListSorted(): void
    {
        Router::createRouteBuilder('/')->connect(
            new Route('/a/route/sorted', [], ['_name' => '_aRoute'])
        );

        $this->exec('routes -s');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('_aRoute', $this->_out->messages()[3]);
    }

    /**
     * Ensure help for `routes` works
     */
    public function testCheckHelp(): void
    {
        $this->exec('routes check -h');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Check a URL');
        $this->assertErrorEmpty();
    }

    /**
     * Ensure routes check with no input
     */
    public function testCheckNoInput(): void
    {
        $this->exec('routes check');
        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('`url` argument is required');
    }

    /**
     * Test checking an existing route.
     */
    public function testCheck(): void
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
     */
    public function testCheckWithNamedRoute(): void
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
     * Test checking an existing route with redirect route.
     */
    public function testCheckWithRedirectRoute(): void
    {
        $this->exec('routes check /app/redirect');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>URI template</info>',
            '<info>Redirect</info>',
        ]);
        $this->assertOutputContainsRow([
            '/app/redirect',
            'http://example.com/test.html',
        ]);
    }

    /**
     * Test checking an nonexistent route.
     */
    public function testCheckNotFound(): void
    {
        $this->exec('routes check /nope');
        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('did not match');
    }

    /**
     * Ensure help for `routes` works
     */
    public function testGenerareHelp(): void
    {
        $this->exec('routes generate -h');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Check a routing array');
        $this->assertErrorEmpty();
    }

    /**
     * Test generating URLs
     */
    public function testGenerateNoPassArgs(): void
    {
        $this->exec('routes generate controller:Articles action:index');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('> /app/articles');
        $this->assertErrorEmpty();
    }

    /**
     * Test generating URLs with passed arguments
     */
    public function testGeneratePassedArguments(): void
    {
        $this->exec('routes generate controller:Articles action:view 2 3');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('> /app/articles/view/2/3');
        $this->assertErrorEmpty();
    }

    /**
     * Test generating URLs with bool params
     */
    public function testGenerateBoolParams(): void
    {
        $this->exec('routes generate controller:Articles action:index _https:true _host:example.com');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('> https://example.com/app/articles');
    }

    /**
     * Test generating URLs
     */
    public function testGenerateMissing(): void
    {
        $this->exec('routes generate plugin:Derp controller:Derp');
        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('do not match');
    }

    /**
     * Test routes duplicate warning
     */
    public function testRouteDuplicateWarning(): void
    {
        $builder = Router::createRouteBuilder('/');
        $builder->connect(
            new Route('/unique-path', [], ['_name' => '_aRoute'])
        );
        $builder->connect(
            new Route('/unique-path', [], ['_name' => '_bRoute'])
        );

        $builder->connect(
            new Route('/blog', ['_method' => 'GET'], ['_name' => 'blog-get'])
        );
        $builder->connect(
            new Route('/blog', [], ['_name' => 'blog-all'])
        );

        $builder->connect(
            new Route('/events', ['_method' => ['POST', 'PUT']], ['_name' => 'events-post'])
        );
        $builder->connect(
            new Route('/events', ['_method' => 'GET'], ['_name' => 'events-get'])
        );

        $this->exec('routes');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContainsRow([
            '<info>Route name</info>',
            '<info>URI template</info>',
            '<info>Plugin</info>',
            '<info>Prefix</info>',
            '<info>Controller</info>',
            '<info>Action</info>',
            '<info>Method(s)</info>',
        ]);
        $this->assertOutputContainsRow([
            '_aRoute',
            '/unique-path',
            '',
            '',
            '',
            '',
            '',
        ]);
        $this->assertOutputContainsRow([
            '_bRoute',
            '/unique-path',
            '',
            '',
            '',
            '',
            '',
        ]);
        $this->assertOutputContainsRow([
            'blog-get',
            '/blog',
            '',
            '',
            '',
            '',
            '',
        ]);
        $this->assertOutputContainsRow([
            'blog-all',
            '/blog',
            '',
            '',
            '',
            '',
            '',
        ]);
    }
}
