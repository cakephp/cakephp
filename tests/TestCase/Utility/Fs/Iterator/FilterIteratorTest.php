<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility\Fs\Iterator;

use Cake\TestSuite\TestCase;
use Cake\Utility\Fs\Enum\FinderMode;
use Cake\Utility\Fs\Iterator\CallbackFilterIterator;
use Cake\Utility\Fs\Iterator\ExcludeDirectoryFilterIterator;
use Cake\Utility\Fs\Iterator\FileTypeFilterIterator;
use Cake\Utility\Fs\Iterator\HiddenFileFilterIterator;
use FilesystemIterator;
use org\bovigo\vfs\vfsStream;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * FilterIteratorTest class
 */
class FilterIteratorTest extends TestCase
{
    /**
     * Test virtual filesystem
     *
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;

    /**
     * Setup test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup('root', null, [
            'visible.txt' => 'content',
            '.hidden' => 'hidden content',
            'file.php' => '<?php',
            'src' => [
                'Controller.php' => '<?php',
                '.git' => [
                    'config' => 'git config',
                ],
                'vendor' => [
                    'package.php' => '<?php',
                ],
                'tests' => [
                    'Test.php' => '<?php',
                ],
            ],
        ]);
    }

    public function testHiddenFileFilterIterator(): void
    {
        $directory = new RecursiveDirectoryIterator(
            vfsStream::url('root'),
            FilesystemIterator::SKIP_DOTS,
        );

        $filtered = new HiddenFileFilterIterator($directory);
        $iterator = new RecursiveIteratorIterator($filtered);

        $files = [];
        foreach ($iterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('visible.txt', $files);
        $this->assertContains('file.php', $files);
        $this->assertNotContains('.hidden', $files);
        $this->assertNotContains('.git', $files);
    }

    public function testFileTypeFilterIteratorFiles(): void
    {
        $directory = new FilesystemIterator(
            vfsStream::url('root/src'),
            FilesystemIterator::SKIP_DOTS,
        );

        $filtered = new FileTypeFilterIterator($directory, FinderMode::FILES);

        $items = [];
        foreach ($filtered as $file) {
            $items[] = $file->getFilename();
        }

        $this->assertContains('Controller.php', $items);
        $this->assertNotContains('vendor', $items);
        $this->assertNotContains('tests', $items);
    }

    public function testFileTypeFilterIteratorDirectories(): void
    {
        $directory = new FilesystemIterator(
            vfsStream::url('root/src'),
            FilesystemIterator::SKIP_DOTS,
        );

        $filtered = new FileTypeFilterIterator($directory, FinderMode::DIRECTORIES);

        $items = [];
        foreach ($filtered as $file) {
            $items[] = $file->getFilename();
        }

        $this->assertContains('vendor', $items);
        $this->assertContains('tests', $items);
        $this->assertContains('.git', $items);
        $this->assertNotContains('Controller.php', $items);
    }

    public function testFileTypeFilterIteratorAll(): void
    {
        $directory = new FilesystemIterator(
            vfsStream::url('root/src'),
            FilesystemIterator::SKIP_DOTS,
        );

        $filtered = new FileTypeFilterIterator($directory, FinderMode::ALL);

        $items = [];
        foreach ($filtered as $file) {
            $items[] = $file->getFilename();
        }

        $this->assertContains('Controller.php', $items);
        $this->assertContains('vendor', $items);
        $this->assertContains('tests', $items);
    }

    public function testExcludeDirectoryFilterIterator(): void
    {
        $directory = new RecursiveDirectoryIterator(
            vfsStream::url('root/src'),
            FilesystemIterator::SKIP_DOTS,
        );

        $filtered = new ExcludeDirectoryFilterIterator($directory, ['vendor', 'tests']);
        $iterator = new RecursiveIteratorIterator($filtered);

        $files = [];
        foreach ($iterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('Controller.php', $files);
        $this->assertNotContains('package.php', $files); // in vendor
        $this->assertNotContains('Test.php', $files); // in tests
    }

    public function testExcludeDirectoryFilterIteratorFilesPass(): void
    {
        $directory = new RecursiveDirectoryIterator(
            vfsStream::url('root/src'),
            FilesystemIterator::SKIP_DOTS,
        );

        // Even if filename matches excluded name, files should pass
        $filtered = new ExcludeDirectoryFilterIterator($directory, ['Controller.php']);
        $iterator = new RecursiveIteratorIterator($filtered);

        $files = [];
        foreach ($iterator as $file) {
            $files[] = $file->getFilename();
        }

        // File with same name should still be included
        $this->assertContains('Controller.php', $files);
    }

    public function testCombineFilters(): void
    {
        $directory = new RecursiveDirectoryIterator(
            vfsStream::url('root'),
            FilesystemIterator::SKIP_DOTS,
        );

        // Combine hidden file filter and exclude directory filter
        $filtered = new HiddenFileFilterIterator($directory);
        $filtered = new ExcludeDirectoryFilterIterator($filtered, ['vendor']);
        $iterator = new RecursiveIteratorIterator($filtered);
        $filtered = new FileTypeFilterIterator($iterator, FinderMode::FILES);

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('visible.txt', $files);
        $this->assertContains('file.php', $files);
        $this->assertContains('Controller.php', $files);
        $this->assertNotContains('.hidden', $files); // hidden
        $this->assertNotContains('config', $files); // in .git (hidden)
        $this->assertNotContains('package.php', $files); // in vendor (excluded)
    }

    /**
     * Test CallbackFilterIterator basic filtering
     */
    public function testCallbackFilterIterator(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new CallbackFilterIterator(
            $recursiveIterator,
            fn(SplFileInfo $file) => str_ends_with($file->getFilename(), '.php'),
            $this->root->url(),
        );

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('file.php', $files);
        $this->assertContains('Controller.php', $files);
        $this->assertNotContains('visible.txt', $files);
    }
}
