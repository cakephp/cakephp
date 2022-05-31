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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * PluginUnloadCommandTest class
 */
class PluginUnloadCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $app;

    /**
     * @var string
     */
    protected $originalAppContent;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->app = APP . DS . 'Application.php';

        $this->originalAppContent = file_get_contents($this->app);

        $this->useCommandRunner();
        $this->setAppNamespace();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();

        file_put_contents($this->app, $this->originalAppContent);
    }

    /**
     * testUnload
     */
    public function testUnload(): void
    {
        $plugin1 = "\$this->addPlugin('TestPlugin', ['bootstrap' => false, 'routes' => false]);";
        $plugin2 = "\$this->addPlugin('TestPluginTwo', ['bootstrap' => false, 'routes' => false]);";
        $this->addPluginToApp($plugin1);
        $this->addPluginToApp($plugin2);
        $this->exec('plugin unload TestPlugin');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $contents = file_get_contents($this->app);

        $this->assertStringNotContainsString($plugin1, $contents);
        $this->assertStringContainsString($plugin2, $contents);
    }

    /**
     * test removing the first plugin leaves the second behind.
     */
    public function testUnloadFirstPlugin(): void
    {
        $plugin1 = "\$this->addPlugin('TestPlugin');";
        $plugin2 = "\$this->addPlugin('Vendor/TestPluginTwo');";
        $this->addPluginToApp($plugin1);
        $this->addPluginToApp($plugin2);
        $this->exec('plugin unload Vendor/TestPluginTwo');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $contents = file_get_contents($this->app);

        $this->assertStringNotContainsString($plugin2, $contents);
        $this->assertStringContainsString($plugin1, $contents);
    }

    /**
     * Data provider for various forms.
     *
     * @return array
     */
    public function variantProvider(): array
    {
        return [
            //  $this->addPlugin('TestPlugin', [
            //      'bootstrap' => false
            //  ]);
            ["        \$this->addPlugin('TestPlugin', [\n\t'bootstrap' => false\n]);\n"],

            //  $this->addPlugin(
            //      'TestPlugin',
            //      [ 'bootstrap' => false]
            //  );
            ["        \$this->addPlugin(\n\t'TestPlugin',\n\t[ 'bootstrap' => false]\n);\n"],

            //  $this->addPlugin(
            //      'Foo',
            //      [
            //          'bootstrap' => false
            //      ]
            //  );
            ["        \$this->addPlugin(\n\t'TestPlugin',\n\t[\n\t\t'bootstrap' => false\n\t]\n);\n"],

            //  $this->addPlugin('Test', [
            //      'bootstrap' => true,
            //      'routes' => true
            //  ]);
            ["        \$this->addPlugin('TestPlugin', [\n\t'bootstrap' => true,\n\t'routes' => true\n]);\n"],

            //  $this->addPlugin('Test',
            //      [
            //          'bootstrap' => true,
            //          'routes' => true
            //      ]
            //  );
            ["        \$this->addPlugin('TestPlugin',\n\t[\n\t\t'bootstrap' => true,\n\t\t'routes' => true\n\t]\n);\n"],

            //  $this->addPlugin('Test',
            //      [
            //
            //      ]
            //  );
            ["        \$this->addPlugin('TestPlugin',\n\t[\n\t\n\t]\n);\n"],

            //  $this->addPlugin('Test');
            ["        \$this->addPlugin('TestPlugin');\n"],

            //  $this->addPlugin('Test', ['bootstrap' => true, 'route' => false]);
            ["        \$this->addPlugin('TestPlugin', ['bootstrap' => true, 'route' => false]);\n"],
        ];
    }

    /**
     * This method will tests multiple notations of plugin loading in the application class
     *
     * @dataProvider variantProvider
     */
    public function testRegularExpressionsApplication(string $content): void
    {
        $this->addPluginToApp($content);

        $this->exec('plugin unload TestPlugin');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $result = file_get_contents($this->app);

        $this->assertStringNotContainsString("addPlugin('TestPlugin'", $result);
        $this->assertDoesNotMatchRegularExpression("/this\-\>addPlugin\([\'\"]TestPlugin'[\'\"][^\)]*\)\;/mi", $result);
    }

    /**
     * _addPluginToApp
     *
     * Quick method to add a plugin to the Application file.
     * This is useful for the tests
     *
     * @param string $insert The addPlugin line to add.
     */
    protected function addPluginToApp($insert): void
    {
        $contents = file_get_contents($this->app);
        $contents = preg_replace('/(function bootstrap\(\)(?:\s*)\:(?:\s*)void(?:\s+)\{)/m', "\$1\n        " . $insert, $contents);
        file_put_contents($this->app, $contents);
    }
}
