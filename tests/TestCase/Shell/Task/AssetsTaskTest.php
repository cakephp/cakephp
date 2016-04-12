<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;

/**
 * AssetsTaskTest class
 *
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

        $this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

        $this->Task = $this->getMock(
            'Cake\Shell\Task\AssetsTask',
            ['in', 'out', 'err', '_stop'],
            [$this->io]
        );
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
        $link = new \SplFileInfo($path);
        $this->assertTrue(file_exists($path . DS . 'root.js'));
        if (DS === '\\') {
            $this->assertTrue($link->isDir());
            $folder = new Folder($path);
            $folder->delete();
        } else {
            $this->assertTrue($link->isLink());
            unlink($path);
        }

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        $link = new \SplFileInfo($path);
        // If "company" directory exists beforehand "test_plugin_three" would
        // be a link. But if the directory is created by the shell itself
        // symlinking fails and the assets folder is copied as fallback.
        $this->assertTrue($link->isDir());
        $this->assertTrue(file_exists($path . DS . 'css' . DS . 'company.css'));
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
        $link = new \SplFileInfo($path);
        if (DS === '\\') {
            $this->assertTrue($link->isDir());
        } else {
            $this->assertTrue($link->isLink());
        }
        $this->assertTrue(file_exists($path . DS . 'css' . DS . 'company.css'));
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

        $shell = $this->getMock(
            'Cake\Shell\Task\AssetsTask',
            ['in', 'out', 'err', '_stop', '_createSymlink', '_copyDirectory'],
            [$this->io]
        );

        $this->assertTrue(is_dir(WWW_ROOT . 'test_theme'));

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
        $this->assertFalse(file_exists(WWW_ROOT . 'test_plugin_two'));
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
        $this->assertTrue(file_exists($path . DS . 'root.js'));
        unlink($path);

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        $link = new \SplFileInfo($path);
        $this->assertFalse($link->isDir());
        $this->assertFalse($link->isLink());
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
        $dir = new \SplFileInfo($path);
        $this->assertTrue($dir->isDir());
        $this->assertTrue(file_exists($path . DS . 'root.js'));

        $folder = new Folder($path);
        $folder->delete();

        $path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
        $link = new \SplFileInfo($path);
        $this->assertTrue($link->isDir());
        $this->assertTrue(file_exists($path . DS . 'css' . DS . 'company.css'));

        $folder = new Folder(WWW_ROOT . 'company');
        $folder->delete();
    }
}
