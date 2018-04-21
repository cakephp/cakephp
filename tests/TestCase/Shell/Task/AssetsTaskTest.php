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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;

/**
 * AssetsTaskTest class
 */
class AssetsTaskTest extends TestCase
{

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
        Plugin::unload();
    }

    /**
     * testSymlink method
     *
     * @return void
     */
    public function testSymlink()
    {
        Plugin::load('TestPlugin');
        Plugin::load('Company/TestPluginThree');

        $this->Task->symlink();

        $path = WWW_ROOT . 'test_plugin';
        $this->assertFileExists($path . DS . 'root.js');
        if (DS === '\\') {
            $this->assertDirectoryExists($path);
            $folder = new Folder($path);
            $folder->delete();
        } else {
            $this->assertTrue(is_link($path));
            unlink($path);
        }

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        // If "company" directory exists beforehand "test_plugin_three" would
        // be a link. But if the directory is created by the shell itself
        // symlinking fails and the assets folder is copied as fallback.
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
        $folder = new Folder(WWW_ROOT . 'company');
        $folder->delete();
    }

    /**
     * testSymlinkWhenVendorDirectoryExits
     *
     * @return void
     */
    public function testSymlinkWhenVendorDirectoryExits()
    {
        Plugin::load('Company/TestPluginThree');

        mkdir(WWW_ROOT . 'company');

        $this->Task->symlink();
        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        if (DS === '\\') {
            $this->assertDirectoryExits($path);
        } else {
            $this->assertTrue(is_link($path));
        }
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');
        $folder = new Folder(WWW_ROOT . 'company');
        $folder->delete();
    }

    /**
     * testSymlinkWhenTargetAlreadyExits
     *
     * @return void
     */
    public function testSymlinkWhenTargetAlreadyExits()
    {
        Plugin::load('TestTheme');

        $shell = $this->getMockBuilder('Cake\Shell\Task\AssetsTask')
            ->setMethods(['in', 'out', 'err', '_stop', '_createSymlink', '_copyDirectory'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->assertDirectoryExists(WWW_ROOT . 'test_theme');

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
        Plugin::load('TestPluginTwo');

        $this->Task->symlink();
        $this->assertFileNotExists(WWW_ROOT . 'test_plugin_two');
    }

    /**
     * testSymlinkingSpecifiedPlugin
     *
     * @return void
     */
    public function testSymlinkingSpecifiedPlugin()
    {
        Plugin::load('TestPlugin');
        Plugin::load('Company/TestPluginThree');

        $this->Task->symlink('TestPlugin');

        $path = WWW_ROOT . 'test_plugin';
        $link = new \SplFileInfo($path);
        $this->assertFileExists($path . DS . 'root.js');
        unlink($path);

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
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
        Plugin::load('TestPlugin');
        Plugin::load('Company/TestPluginThree');

        $this->Task->copy();

        $path = WWW_ROOT . 'test_plugin';
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'root.js');

        $folder = new Folder($path);
        $folder->delete();

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . DS . 'css' . DS . 'company.css');

        $folder = new Folder(WWW_ROOT . 'company');
        $folder->delete();
    }

    /**
     * testCopyOverwrite
     *
     * @return void
     */
    public function testCopyOverwrite()
    {
        Plugin::load('TestPlugin');

        $this->Task->copy();

        $pluginPath = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'webroot';

        $path = WWW_ROOT . 'test_plugin';
        $dir = new \SplFileInfo($path);
        $this->assertTrue($dir->isDir());
        $this->assertFileExists($path . DS . 'root.js');

        file_put_contents($path . DS . 'root.js', 'updated');

        $this->Task->copy();

        $this->assertFileNotEquals($path . DS . 'root.js', $pluginPath . DS . 'root.js');

        $this->Task->params['overwrite'] = true;
        $this->Task->copy();

        $this->assertFileEquals($path . DS . 'root.js', $pluginPath . DS . 'root.js');

        $folder = new Folder($path);
        $folder->delete();
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

        Plugin::load('TestPlugin');
        Plugin::load('Company/TestPluginThree');

        mkdir(WWW_ROOT . 'company');

        $this->Task->symlink();

        $this->assertTrue(is_link(WWW_ROOT . 'test_plugin'));

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        $this->assertTrue(is_link($path));

        $this->Task->remove();

        $this->assertFalse(is_link(WWW_ROOT . 'test_plugin'));
        $this->assertFalse(is_link($path));
        $this->assertDirectoryExists(WWW_ROOT . 'company', 'Ensure namespace folder isn\'t removed');

        rmdir(WWW_ROOT . 'company');
    }

    /**
     * testRemoveFolder method
     *
     * @return void
     */
    public function testRemoveFolder()
    {
        Plugin::load('TestPlugin');
        Plugin::load('Company/TestPluginThree');

        $this->Task->copy();

        $this->assertTrue(is_dir(WWW_ROOT . 'test_plugin'));

        $this->assertTrue(is_dir(WWW_ROOT . 'company' . DS . 'test_plugin_three'));

        $this->Task->remove();

        $this->assertDirectoryNotExists(WWW_ROOT . 'test_plugin');
        $this->assertDirectoryNotExists(WWW_ROOT . 'company' . DS . 'test_plugin_three');
        $this->assertDirectoryExists(WWW_ROOT . 'company', 'Ensure namespace folder isn\'t removed');

        rmdir(WWW_ROOT . 'company');
    }

    /**
     * testOverwrite
     *
     * @return void
     */
    public function testOverwrite()
    {
        Plugin::load('TestPlugin');
        Plugin::load('Company/TestPluginThree');

        $path = WWW_ROOT . 'test_plugin';

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
            $folder = new Folder($path);
            $folder->delete();
        } else {
            unlink($path);
        }

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        mkdir($path, 0777, true);
        $filectime = filectime($path);

        sleep(1);
        $this->Task->params['overwrite'] = true;
        $this->Task->copy('Company/TestPluginThree');

        $newfilectime = filectime($path);
        $this->assertTrue($newfilectime > $filectime);

        $folder = new Folder(WWW_ROOT . 'company');
        $folder->delete();
    }
}
