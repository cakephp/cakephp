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
use Cake\TestSuite\TestCase;

/**
 * RoutesShellTest
 */
class RoutesShellTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->setMethods(['helper', 'out', 'err'])
            ->getMock();
        $this->table = $this->getMockBuilder('Cake\Shell\Helper\TableHelper')
            ->setConstructorArgs([$this->io])
            ->getMock();
        $this->io->expects($this->any())
            ->method('helper')
            ->with('table')
            ->will($this->returnValue($this->table));

        $this->shell = new RoutesShell($this->io);
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
        unset($this->io, $this->shell);
    }

    /**
     * Test checking an non-existing route.
     *
     * @return void
     */
    public function testMain()
    {
        $this->table->expects($this->once())
            ->method('output')
            ->with(
                $this->logicalAnd(
                    $this->contains(['Route name', 'URI template', 'Defaults']),
                    $this->contains([
                        'articles:_action',
                        '/articles/:action/*',
                        '{"controller":"Articles","action":"index","plugin":null}'
                    ]),
                    $this->contains([
                        'bake._controller:_action',
                        '/bake/:controller/:action',
                        '{"plugin":"Bake","action":"index"}',
                    ]),
                    $this->contains([
                        'testName',
                        '/tests/:action/*',
                        '{"controller":"Tests","action":"index","plugin":null}'
                    ])
                )
            );
        $this->shell->main();
    }

    /**
     * Test checking an existing route.
     *
     * @return void
     */
    public function testCheck()
    {
        $this->table->expects($this->once())
            ->method('output')
            ->with(
                $this->logicalAnd(
                    $this->contains(['Route name', 'URI template', 'Defaults']),
                    $this->contains([
                        'articles:_action',
                        '/articles/index',
                        '{"action":"index","pass":[],"controller":"Articles","plugin":null}'
                    ])
                )
            );
        $this->shell->check('/articles/index');
    }

    /**
     * Test checking an existing route with named route.
     *
     * @return void
     */
    public function testCheckWithNamedRoute()
    {
        $this->table->expects($this->once())
            ->method('output')
            ->with(
                $this->logicalAnd(
                    $this->contains(['Route name', 'URI template', 'Defaults']),
                    $this->contains([
                        'testName',
                        '/tests/index',
                        '{"action":"index","pass":[],"controller":"Tests","plugin":null}'
                    ])
                )
            );
        $this->shell->check('/tests/index');
    }

    /**
     * Test checking an non-existing route.
     *
     * @return void
     */
    public function testCheckNotFound()
    {
        $this->io->expects($this->at(0))
            ->method('err')
            ->with($this->stringContains('did not match'));
        $this->shell->check('/nope');
    }

    /**
     * Test generating URLs
     *
     * @return void
     */
    public function testGenerate()
    {
        $this->io->expects($this->never())
            ->method('err');
        $this->io->expects($this->at(0))
            ->method('out')
            ->with($this->stringContains('> /articles/index'));
        $this->io->expects($this->at(2))
            ->method('out')
            ->with($this->stringContains('> /articles/view/2/3'));

        $this->shell->args = ['controller:Articles', 'action:index'];
        $this->shell->generate();

        $this->shell->args = ['controller:Articles', 'action:view', '2', '3'];
        $this->shell->generate();
    }

    /**
     * Test generating URLs with bool params
     *
     * @return void
     */
    public function testGenerateBoolParams()
    {
        $this->io->expects($this->never())
            ->method('err');
        $this->io->expects($this->at(0))
            ->method('out')
            ->with($this->stringContains('> https://example.com/articles/index'));

        $this->shell->args = ['_ssl:true', '_host:example.com', 'controller:Articles', 'action:index'];
        $this->shell->generate();
    }

    /**
     * Test generating URLs
     *
     * @return void
     */
    public function testGenerateMissing()
    {
        $this->io->expects($this->at(0))
            ->method('err')
            ->with($this->stringContains('do not match'));
        $this->shell->args = ['controller:Derp'];
        $this->shell->generate();
    }
}
