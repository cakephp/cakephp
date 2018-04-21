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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\Filesystem\File;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * UnloadTaskTest class
 */
class UnloadTaskTest extends ConsoleIntegrationTestCase
{
    /**
     * @var string
     */
    protected $bootstrap;

    /**
     * @var string
     */
    protected $app;

    /**
     * @var string
     */
    protected $originalBootstrapContent;

    /**
     * @var string
     */
    protected $originalAppContent;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';
        $this->app = APP . DS . 'Application.php';

        $this->originalBootstrapContent = file_get_contents($this->bootstrap);
        $this->originalAppContent = file_get_contents($this->app);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->shell);
        Plugin::unload();

        file_put_contents($this->bootstrap, $this->originalBootstrapContent);
        file_put_contents($this->app, $this->originalAppContent);
    }

    /**
     * testUnload
     *
     * @return void
     */
    public function testUnload()
    {
        $this->_addPluginToBootstrap('TestPlugin');
        $this->_addPluginToBootstrap('TestPluginSecond');

        $contents = file_get_contents($this->bootstrap);
        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $this->assertContains($expected, $contents);

        $this->exec('plugin unload --no_app TestPlugin');

        $this->assertExitCode(Shell::CODE_SUCCESS);
        $contents = file_get_contents($this->bootstrap);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";

        $this->assertNotContains($expected, $contents);
        $expected = "Plugin::load('TestPluginSecond', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
        $this->assertContains($expected, $contents);
    }

    /**
     * Data provider for various forms.
     *
     * @return array
     */
    public function variantProvider()
    {
        return [
            //  Plugin::load('TestPlugin', [
            //      'bootstrap' => false
            //  ]);
            ["\nPlugin::load('TestPlugin', [\n\t'bootstrap' => false\n]);\n"],

            //  Plugin::load(
            //      'TestPlugin',
            //      [ 'bootstrap' => false]
            //  );
            ["\nPlugin::load(\n\t'TestPlugin',\n\t[ 'bootstrap' => false]\n);\n"],

            //  Plugin::load(
            //      'Foo',
            //      [
            //          'bootstrap' => false
            //      ]
            //  );
            ["\nPlugin::load(\n\t'TestPlugin',\n\t[\n\t\t'bootstrap' => false\n\t]\n);\n"],

            //  Plugin::load('Test', [
            //      'autoload' => false,
            //      'bootstrap' => true,
            //      'routes' => true
            //  ]);
            ["\nPlugin::load('TestPlugin', [\n\t'autoload' => false,\n\t'bootstrap' => true,\n\t'routes' => true\n]);\n"],

            //  Plugin::load('Test',
            //      [
            //          'bootstrap' => true,
            //          'routes' => true
            //      ]
            //  );
            ["\nPlugin::load('TestPlugin',\n\t[\n\t\t'bootstrap' => true,\n\t\t'routes' => true\n\t]\n);\n"],

            //  Plugin::load('Test',
            //      [
            //
            //      ]
            //  );
            ["\nPlugin::load('TestPlugin',\n\t[\n\t\n\t]\n);\n"],

            //  Plugin::load('Test');
            ["\nPlugin::load('TestPlugin');\n"],

            //  Plugin::load('Test', ['bootstrap' => true, 'route' => false]);
            ["\nPlugin::load('TestPlugin', ['bootstrap' => true, 'route' => false]);\n"],
        ];
    }

    /**
     * testRegularExpressions
     *
     * This method will tests multiple notations of plugin loading.
     *
     * @dataProvider variantProvider
     * @return void
     */
    public function testRegularExpressions($content)
    {
        $bootstrap = new File($this->bootstrap, false);
        $bootstrap->append($content);

        $this->exec('plugin unload --no_app TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $result = $bootstrap->read();
        $this->assertNotRegexp("/Plugin\:\:load\([\'\"]TestPlugin'[\'\"][^\)]*\)\;/mi", $result);
    }

    /**
     * This method will tests multiple notations of plugin loading in the application class
     *
     * @dataProvider variantProvider
     * @return void
     */
    public function testRegularExpressionsApplication($content)
    {
        $content = str_replace('Plugin::load', "        \$this->addPlugin", $content);
        $this->addPluginToApp($content);

        $this->exec('plugin unload TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $result = file_get_contents($this->app);

        $this->assertNotContains("addPlugin('TestPlugin'", $result);
        $this->assertNotRegexp("/this\-\>addPlugin\([\'\"]TestPlugin'[\'\"][^\)]*\)\;/mi", $result);
    }

    /**
     * _addPluginToBootstrap
     *
     * Quick method to add a plugin to the bootstrap file.
     * This is useful for the tests
     *
     * @param string $name
     */
    protected function _addPluginToBootstrap($name)
    {
        $bootstrap = new File($this->bootstrap, false);
        $bootstrap->append("\n\nPlugin::load('$name', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);\n");
    }

    /**
     * _addPluginToApp
     *
     * Quick method to add a plugin to the bootstrap file.
     * This is useful for the tests
     *
     * @param string $insert The addPlugin line to add.
     */
    protected function addPluginToApp($insert)
    {
        $contents = file_get_contents($this->app);
        $contents = preg_replace('/(function bootstrap\(\)(?:\s+)\{)/m', '$1' . $insert, $contents);
        file_put_contents($this->app, $contents);
    }
}
