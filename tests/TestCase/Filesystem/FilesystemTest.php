<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Filesystem;

use Cake\Filesystem\Filesystem;
use Cake\TestSuite\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Filesystem class
 *
 * @coversDefaultClass \Cake\Filesystem\Filesystem
 */
class FilesystemTest extends TestCase
{
    protected $vfs;

    protected $fs;

    protected $vfsPath;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->vfs = vfsStream::setup('root');
        $this->vfsPath = vfsStream::url('root');

        $this->fs = new Filesystem();

        clearstatcache();
    }

    /**
     * @return void
     * @covers ::mkdir
     */
    public function testMkdir()
    {
        $path = $this->vfsPath . DS . 'tests' . DS . 'first' . DS . 'second' . DS . 'third';
        $this->fs->mkdir($path);
        $this->assertTrue(is_dir($path));
    }

    /**
     * @return void
     * @covers ::dumpFile
     */
    public function testDumpFile()
    {
        $path = $this->vfsPath . DS . 'foo.txt';

        $this->fs->dumpFile($path, 'bar');
        $this->assertEquals(file_get_contents($path), 'bar');

        $path = $this->vfsPath . DS . 'empty.txt';
        $this->fs->dumpFile($path, '');
        $this->assertSame(file_get_contents($path), '');
    }

    /**
     * @return void
     * @covers ::copyDir
     */
    public function testCopyDir()
    {
        $return = $this->fs->copyDir(WWW_ROOT, $this->vfsPath . DS . 'dest');

        $this->assertTrue($return);
    }

    /**
     * @return void
     * @covers ::deleteDir
     */
    public function testDeleteDir()
    {
        $structure = [
            'Core' => [
                'AbstractFactory' => [
                    'test.php' => 'some text content',
                    'other.php' => 'Some more text content',
                    'Invalid.csv' => 'Something else',
                ],
                'AnEmptyFolder' => [],
                'badlocation.php' => 'some bad content',
            ],
        ];
        vfsStream::create($structure);

        $return = $this->fs->deleteDir($this->vfsPath . DS . 'Core');

        $this->assertTrue($return);
    }

    /**
     * Tests deleteDir() on directory that contains symlinks
     *
     * @return void
     */
    public function testDeleteDirWithLinks()
    {
        $path = TMP . 'fs_links_test';
        // phpcs:ignore
        @mkdir($path);
        $target = $path . DS . 'target';
        // phpcs:ignore
        @mkdir($target);

        $link = $path . DS . 'link';
        // phpcs:ignore
        @symlink($target, $link);

        $this->assertTrue($this->fs->deleteDir($path));
        $this->assertFalse(file_exists($link));
    }
}
