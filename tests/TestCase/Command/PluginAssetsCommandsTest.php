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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\Configure;
use Cake\Filesystem\Filesystem;
use Cake\TestSuite\TestCase;
use SplFileInfo;

/**
 * PluginAssetsCommandsTest class
 */
class PluginAssetsCommandsTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $wwwRoot;

    /**
     * @var \Cake\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->wwwRoot = TMP . 'assets_task_webroot' . DS;
        Configure::write('App.wwwRoot', $this->wwwRoot);

        $this->fs = new Filesystem();
        $this->fs->deleteDir($this->wwwRoot);
        $this->fs->copyDir(WWW_ROOT, $this->wwwRoot);

        $this->useCommandRunner();
        $this->setAppNamespace();
        $this->configApplication(Configure::read('App.namespace') . '\ApplicationWithDefaultRoutes', []);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * testSymlink method
     */
    public function testSymlink(): void
    {
        $this->loadPlugins(['TestPlugin' => ['routes' => false], 'Company/TestPluginThree']);

        $this->exec('plugin assets symlink');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $path = $this->wwwRoot . 'test_plugin';
        $this->assertFileExists($path . DS . 'root.js');
        $this->assertTrue(is_link($path));

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
        $this->assertTrue(is_link($path));
    }

    public function testSymlinkWhenVendorDirectoryExists(): void
    {
        $this->loadPlugins(['Company/TestPluginThree']);

        mkdir($this->wwwRoot . 'company');

        $this->exec('plugin assets symlink');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
        $this->assertTrue(is_link($path));
    }

    /**
     * testSymlinkWhenTargetAlreadyExits
     */
    public function testSymlinkWhenTargetAlreadyExits(): void
    {
        $this->loadPlugins(['TestTheme']);

        $output = new StubConsoleOutput();
        $io = $this->getMockBuilder(ConsoleIo::class)
            ->setConstructorArgs([$output, $output, null, null])
            ->addMethods(['in'])
            ->getMock();
        $parser = new ConsoleOptionParser('cake example');
        $parser->addArgument('name', ['required' => false]);
        $parser->addOption('overwrite', ['default' => false, 'boolean' => true]);

        $command = $this->getMockBuilder('Cake\Command\PluginAssetsSymlinkCommand')
            ->onlyMethods(['getOptionParser', '_createSymlink', '_copyDirectory'])
            ->getMock();
        $command->method('getOptionParser')->will($this->returnValue($parser));

        $this->assertDirectoryExists($this->wwwRoot . 'test_theme');

        $command->expects($this->never())->method('_createSymlink');
        $command->expects($this->never())->method('_copyDirectory');
        $command->run([], $io);
    }

    /**
     * test that plugins without webroot are not processed
     */
    public function testForPluginWithoutWebroot(): void
    {
        $this->loadPlugins(['TestPluginTwo']);

        $this->exec('plugin assets symlink');
        $this->assertFileDoesNotExist($this->wwwRoot . 'test_plugin_two');
    }

    /**
     * testSymlinkingSpecifiedPlugin
     */
    public function testSymlinkingSpecifiedPlugin(): void
    {
        $this->loadPlugins(['TestPlugin' => ['routes' => false], 'Company/TestPluginThree']);

        $this->exec('plugin assets symlink TestPlugin');

        $path = $this->wwwRoot . 'test_plugin';
        $link = new SplFileInfo($path);
        $this->assertFileExists($path . DS . 'root.js');

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertDirectoryDoesNotExist($path);
        $this->assertFalse(is_link($path));
    }

    /**
     * testCopy
     */
    public function testCopy(): void
    {
        $this->loadPlugins(['TestPlugin' => ['routes' => false], 'Company/TestPluginThree']);

        $this->exec('plugin assets copy');

        $path = $this->wwwRoot . 'test_plugin';
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'root.js');

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
    }

    /**
     * testCopyOverwrite
     */
    public function testCopyOverwrite(): void
    {
        $this->loadPlugins(['TestPlugin' => ['routes' => false]]);

        $this->exec('plugin assets copy');

        $pluginPath = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'webroot';

        $path = $this->wwwRoot . 'test_plugin';
        $dir = new SplFileInfo($path);
        $this->assertTrue($dir->isDir());
        $this->assertFileExists($path . DS . 'root.js');

        file_put_contents($path . DS . 'root.js', 'updated');

        $this->exec('plugin assets copy');

        $this->assertFileNotEquals($path . DS . 'root.js', $pluginPath . DS . 'root.js');

        $this->exec('plugin assets copy --overwrite');

        $this->assertFileEquals($path . DS . 'root.js', $pluginPath . DS . 'root.js');
    }

    /**
     * testRemoveSymlink method
     */
    public function testRemoveSymlink(): void
    {
        $this->loadPlugins(['TestPlugin' => ['routes' => false], 'Company/TestPluginThree']);

        mkdir($this->wwwRoot . 'company');

        $this->exec('plugin assets symlink');

        $this->assertTrue(is_link($this->wwwRoot . 'test_plugin'));

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertTrue(is_link($path));

        $this->exec('plugin assets remove');

        $this->assertFalse(is_link($this->wwwRoot . 'test_plugin'));
        $this->assertFalse(is_link($path));
        $this->assertDirectoryExists($this->wwwRoot . 'company', 'Ensure namespace folder isn\'t removed');
    }

    /**
     * testRemoveFolder method
     */
    public function testRemoveFolder(): void
    {
        $this->loadPlugins(['TestPlugin' => ['routes' => false], 'Company/TestPluginThree']);

        $this->exec('plugin assets copy');

        $this->assertTrue(is_dir($this->wwwRoot . 'test_plugin'));

        $this->assertTrue(is_dir($this->wwwRoot . 'company' . DS . 'test_plugin_three'));

        $this->exec('plugin assets remove');

        $this->assertDirectoryDoesNotExist($this->wwwRoot . 'test_plugin');
        $this->assertDirectoryDoesNotExist($this->wwwRoot . 'company' . DS . 'test_plugin_three');
        $this->assertDirectoryExists($this->wwwRoot . 'company', 'Ensure namespace folder isn\'t removed');
    }

    /**
     * testOverwrite
     */
    public function testOverwrite(): void
    {
        $this->loadPlugins(['TestPlugin' => ['routes' => false], 'Company/TestPluginThree']);

        $path = $this->wwwRoot . 'test_plugin';

        mkdir($path);
        $filectime = filectime($path);

        sleep(1);
        $this->exec('plugin assets symlink TestPlugin --overwrite');
        $this->assertTrue(is_link($path));

        $newfilectime = filectime($path);
        $this->assertTrue($newfilectime !== $filectime);

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        mkdir($path, 0777, true);
        $filectime = filectime($path);

        sleep(1);
        $this->exec('plugin assets copy Company/TestPluginThree --overwrite');

        $newfilectime = filectime($path);
        $this->assertTrue($newfilectime > $filectime);
    }
}
