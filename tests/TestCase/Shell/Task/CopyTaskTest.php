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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Shell\PluginAssetsTask;
use Cake\TestSuite\TestCase;

/**
 * ExtractTaskTest class
 *
 */
class CopyTaskTest extends TestCase
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
                DS === '\\', 'Skip Copy Task tests on windows to prevent side effects for UrlHelper tests on AppVeyor.'
        );

        $this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

        $this->shell = $this->getMock(
                'Cake\Shell\PluginShell', ['in', 'out', 'err', '_stop', 'copy'], [$this->io]
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
        unset($this->shell);
        Plugin::unload();
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

        $this->shell->Copy->main();

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
