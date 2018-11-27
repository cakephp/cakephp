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
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Filesystem;
use Cake\TestSuite\TestCase;

/**
 * AssetsTaskTest class
 */
class AssetsTaskTest extends TestCase
{
    protected $wwwRoot;

    /**
     * @var Cake\Filessytem\Filesystem;
     */
    protected $fs;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->skipIf(
            DS === '\\',
            'Skip AssetsTask tests on windows to prevent side effects for UrlHelper tests on AppVeyor.'
        );

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->Task = $this->getMockBuilder('Cake\Shell\Task\AssetsTask')
            ->setMethods(['in', 'out', 'err', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->wwwRoot = TMP . 'assets_task_webroot' . DS;
        Configure::write('App.wwwRoot', $this->wwwRoot);

        $this->fs = new Filesystem();
        $this->fs->deleteDir($this->wwwRoot);
        $this->fs->copyDir(WWW_ROOT, $this->wwwRoot);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Task);
        Plugin::getCollection()->clear();
    }

    /**
     * testSymlink method
     *
     * @return void
     */
    public function testSymlink()
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $this->Task->symlink();

        $path = $this->wwwRoot . 'test_plugin';
        $this->assertFileExists($path . DS . 'root.js');
        if (DS === '\\') {
            $this->assertDirectoryExists($path);
        } else {
            $this->assertTrue(is_link($path));
        }

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        // If "company" directory exists beforehand "test_plugin_three" would
        // be a link. But if the directory is created by the shell itself
        // symlinking fails and the assets folder is copied as fallback.
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
    }

    /**
     * testSymlinkWhenVendorDirectoryExits
     *
     * @return void
     */
    public function testSymlinkWhenVendorDirectoryExits()
    {
        $this->loadPlugins(['Company/TestPluginThree']);

        mkdir($this->wwwRoot . 'company');

        $this->Task->symlink();
        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        if (DS === '\\') {
            $this->assertDirectoryExits($path);
        } else {
            $this->assertTrue(is_link($path));
        }
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
    }

    /**
     * testSymlinkWhenTargetAlreadyExits
     *
     * @return void
     */
    public function testSymlinkWhenTargetAlreadyExits()
    {
        $this->loadPlugins(['TestTheme']);

        $shell = $this->getMockBuilder('Cake\Shell\Task\AssetsTask')
            ->setMethods(['in', 'out', 'err', '_stop', '_createSymlink', '_copyDirectory'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->assertDirectoryExists($this->wwwRoot . 'test_theme');

        $shell->expects($this->never())->method('_createSymlink');
        $shell->expects($this->never())->method('_copyDirectory');
        $shell->symlink();
    }

    /**
     * test that plugins without webroot are not processed
     *
     * @return void
     */
    public function testForPluginWithoutWebroot()
    {
        $this->loadPlugins(['TestPluginTwo']);

        $this->Task->symlink();
        $this->assertFileNotExists($this->wwwRoot . 'test_plugin_two');
    }

    /**
     * testSymlinkingSpecifiedPlugin
     *
     * @return void
     */
    public function testSymlinkingSpecifiedPlugin()
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $this->Task->symlink('TestPlugin');

        $path = $this->wwwRoot . 'test_plugin';
        $link = new \SplFileInfo($path);
        $this->assertFileExists($path . DS . 'root.js');
        unlink($path);

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertDirectoryNotExists($path);
        $this->assertFalse(is_link($path));
    }

    /**
     * testCopy
     *
     * @return void
     */
    public function testCopy()
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $this->Task->copy();

        $path = $this->wwwRoot . 'test_plugin';
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'root.js');

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
    }

    /**
     * testCopyOverwrite
     *
     * @return void
     */
    public function testCopyOverwrite()
    {
        $this->loadPlugins(['TestPlugin']);

        $this->Task->copy();

        $pluginPath = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'webroot';

        $path = $this->wwwRoot . 'test_plugin';
        $dir = new \SplFileInfo($path);
        $this->assertTrue($dir->isDir());
        $this->assertFileExists($path . DS . 'root.js');

        file_put_contents($path . DS . 'root.js', 'updated');

        $this->Task->copy();

        $this->assertFileNotEquals($path . DS . 'root.js', $pluginPath . DS . 'root.js');

        $this->Task->params['overwrite'] = true;
        $this->Task->copy();

        $this->assertFileEquals($path . DS . 'root.js', $pluginPath . DS . 'root.js');
    }

    /**
     * testRemoveSymlink method
     *
     * @return void
     */
    public function testRemoveSymlink()
    {
        if (DS === '\\') {
            $this->markTestSkipped(
                "Can't test symlink removal on windows."
            );
        }

        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        mkdir($this->wwwRoot . 'company');

        $this->Task->symlink();

        $this->assertTrue(is_link($this->wwwRoot . 'test_plugin'));

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        $this->assertTrue(is_link($path));

        $this->Task->remove();

        $this->assertFalse(is_link($this->wwwRoot . 'test_plugin'));
        $this->assertFalse(is_link($path));
        $this->assertDirectoryExists($this->wwwRoot . 'company', 'Ensure namespace folder isn\'t removed');

        rmdir($this->wwwRoot . 'company');
    }

    /**
     * testRemoveFolder method
     *
     * @return void
     */
    public function testRemoveFolder()
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $this->Task->copy();

        $this->assertTrue(is_dir($this->wwwRoot . 'test_plugin'));

        $this->assertTrue(is_dir($this->wwwRoot . 'company' . DS . 'test_plugin_three'));

        $this->Task->remove();

        $this->assertDirectoryNotExists($this->wwwRoot . 'test_plugin');
        $this->assertDirectoryNotExists($this->wwwRoot . 'company' . DS . 'test_plugin_three');
        $this->assertDirectoryExists($this->wwwRoot . 'company', 'Ensure namespace folder isn\'t removed');

        rmdir($this->wwwRoot . 'company');
    }

    /**
     * testOverwrite
     *
     * @return void
     */
    public function testOverwrite()
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $path = $this->wwwRoot . 'test_plugin';

        mkdir($path);
        $filectime = filectime($path);

        sleep(1);
        $this->Task->params['overwrite'] = true;
        $this->Task->symlink('TestPlugin');
        if (DS === '\\') {
            $this->assertDirectoryExists($path);
        } else {
            $this->assertTrue(is_link($path));
        }

        $newfilectime = filectime($path);
        $this->assertTrue($newfilectime !== $filectime);

        if (DS === '\\') {
            $this->fs->deleteDir($path);
        } else {
            unlink($path);
        }

        $path = $this->wwwRoot . 'company' . DS . 'test_plugin_three';
        mkdir($path, 0777, true);
        $filectime = filectime($path);

        sleep(1);
        $this->Task->params['overwrite'] = true;
        $this->Task->copy('Company/TestPluginThree');

        $newfilectime = filectime($path);
        $this->assertTrue($newfilectime > $filectime);
    }
}
