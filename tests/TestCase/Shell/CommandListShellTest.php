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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * CommandListShellTest
 */
class CommandListShellTest extends ConsoleIntegrationTestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Plugin::load(['TestPlugin', 'TestPluginTwo']);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * test that main finds core shells.
     *
     * @return void
     */
    public function testMain()
    {
        $this->exec('command_list');

        $expected = "/\[.*TestPlugin.*\] example/";
        $this->assertOutputRegExp($expected);

        $expected = "/\[.*TestPluginTwo.*\] example, unique, welcome/";
        $this->assertOutputRegExp($expected);

        $expected = "/\[.*CORE.*\] cache, help, i18n, orm_cache, plugin, routes, schema_cache, server/";
        $this->assertOutputRegExp($expected);

        $expected = "/\[.*app.*\] abort, demo, i18m, integration, merge, sample/";
        $this->assertOutputRegExp($expected);
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertErrorEmpty();
    }

    /**
     * If there is an app shell with the same name as a core shell,
     * tests that the app shell is the one displayed and the core one is hidden.
     *
     * @return void
     */
    public function testMainAppPriority()
    {
        rename(APP . 'Shell' . DS . 'I18mShell.php', APP . 'Shell' . DS . 'I18nShell.php');
        $this->exec('command_list');
        rename(APP . 'Shell' . DS . 'I18nShell.php', APP . 'Shell' . DS . 'I18mShell.php');

        $expected = "/\[.*CORE.*\] cache, help, orm_cache, plugin, routes, schema_cache, server, version/";
        $this->assertOutputRegExp($expected);

        $expected = "/\[.*app.*\] abort, demo, i18n, integration, merge, sample/";
        $this->assertOutputRegExp($expected);
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertErrorEmpty();
    }

    /**
     * test xml output.
     *
     * @return void
     */
    public function testMainXml()
    {
        $this->exec('command_list --xml');

        $find = '<shell name="sample" call_as="sample" provider="app" help="sample -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="orm_cache" call_as="orm_cache" provider="CORE" help="orm_cache -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="welcome" call_as="TestPluginTwo.welcome" provider="TestPluginTwo" help="TestPluginTwo.welcome -h"';
        $this->assertOutputContains($find);
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertErrorEmpty();
    }

    /**
     * test that main prints the cakephp's version.
     *
     * @return void
     */
    public function testMainVersion()
    {
        $this->exec('command_list --version');
        $expected = Configure::version();
        $this->assertOutputContains($expected);
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertErrorEmpty();
    }
}
