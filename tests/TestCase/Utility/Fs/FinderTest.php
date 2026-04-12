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
namespace Cake\Test\TestCase\Utility\Fs;

use Cake\TestSuite\TestCase;
use Cake\Utility\Fs\Enum\DepthOperator;
use Cake\Utility\Fs\Finder;
use Iterator;
use org\bovigo\vfs\vfsStream;
use SplFileInfo;

/**
 * FinderTest class
 */
class FinderTest extends TestCase
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
            'src' => [
                'Controller' => [
                    'AppController.php' => '<?php',
                    'UsersController.php' => '<?php',
                ],
                'Model' => [
                    'Entity' => [
                        'User.php' => '<?php',
                    ],
                    'Table' => [
                        'UsersTable.php' => '<?php',
                    ],
                ],
                'View' => [
                    'layout.php' => '<?php',
                ],
            ],
            'tests' => [
                'TestCase' => [
                    'Controller' => [
                        'UsersControllerTest.php' => '<?php',
                    ],
                ],
            ],
            'webroot' => [
                '.htaccess' => 'rules',
                'index.php' => '<?php',
                'css' => [
                    'style.css' => 'body {}',
                ],
                'js' => [
                    'app.js' => 'console.log();',
                ],
            ],
        ]);
    }

    public function testBasicFind(): void
    {
        $finder = new Finder();
        $files = $finder->in(vfsStream::url('root/src'))->files();

        $this->assertInstanceOf(Iterator::class, $files);

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertCount(5, $paths);
        $this->assertStringContainsString('AppController.php', implode(',', $paths));
        $this->assertStringContainsString('UsersController.php', implode(',', $paths));
        $this->assertStringContainsString('User.php', implode(',', $paths));
    }

    public function testMultiplePaths(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->in(vfsStream::url('root/tests'))
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        $this->assertEquals(6, $count);
    }

    public function testNamePattern(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*Controller.php')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        $this->assertCount(2, $paths);
        $this->assertContains('AppController.php', $paths);
        $this->assertContains('UsersController.php', $paths);
    }

    public function testExclude(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->exclude('tests')
            ->exclude('webroot')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertStringNotContainsString('TestCase', implode(',', $paths));
        $this->assertStringNotContainsString('webroot', implode(',', $paths));
        $this->assertStringContainsString('src', implode(',', $paths));
    }

    public function testDepth(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(3, DepthOperator::LESS_THAN)
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // Should find files at depth 0, 1, and 2
        $this->assertEquals(5, $count);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(1)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Should find files in Controller/ and View/
        $this->assertGreaterThan(0, count($paths));
    }

    public function testPath(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->path('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertStringContainsString('Controller', implode(',', $paths));
        $this->assertStringNotContainsString('Model', implode(',', $paths));
    }

    public function testNotPath(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->notPath('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertStringNotContainsString('Controller', implode(',', $paths));
        $this->assertStringContainsString('Model', implode(',', $paths));
    }

    public function testIgnoreHiddenFiles(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->ignoreHiddenFiles(false)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        $this->assertContains('.htaccess', $paths);
    }

    public function testIgnoreHiddenFilesByDefault(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Hidden files should be ignored by default
        $this->assertNotContains('.htaccess', $paths);
        $this->assertContains('index.php', $paths);
        $this->assertContains('style.css', $paths);
    }

    public function testChaining(): void
    {
        $finder = new Finder();
        $result = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->exclude('View')
            ->depth(3, DepthOperator::LESS_THAN);

        $this->assertInstanceOf(Finder::class, $result);
    }

    public function testGlobPatternSimpleWildcard(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->name('*.css')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        $this->assertContains('style.css', $paths);
        $this->assertCount(1, $paths);
    }

    public function testGlobPatternRecursiveWildcard(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->files();

        // Should find all PHP files recursively (name filters filename only)
        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        $this->assertEquals(5, $count);
    }

    public function testGlobPatternWithPath(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('User*.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('User.php', $filenames);
        $this->assertContains('UsersTable.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(3, $filenames);
    }

    public function testGlobPatternMultipleExtensions(): void
    {
        $finder = new Finder();

        // Test .js files
        $jsFiles = $finder
            ->in(vfsStream::url('root/webroot'))
            ->name('*.js')
            ->files();

        $count = 0;
        foreach ($jsFiles as $file) {
            $this->assertEquals('js', $file->getExtension());
            $count++;
        }
        $this->assertEquals(1, $count);

        // Test .css files
        $finder2 = new Finder();
        $cssFiles = $finder2
            ->in(vfsStream::url('root/webroot'))
            ->name('*.css')
            ->files();

        $count = 0;
        foreach ($cssFiles as $file) {
            $this->assertEquals('css', $file->getExtension());
            $count++;
        }
        $this->assertEquals(1, $count);
    }

    public function testGlobPatternWithExclude(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->exclude('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringNotContainsString('Controller', $pathString);
        $this->assertStringContainsString('Model', $pathString);
    }

    public function testGlobPatternSpecificDirectory(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->path('Controller')
            ->name('*Controller.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(2, $filenames);
    }

    public function testPatternMethod(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/**/*.php')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // Should find all PHP files under src/
        $this->assertEquals(5, $count);
    }

    public function testPatternMethodControllers(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/Controller/*.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(2, $filenames);
    }

    public function testPatternMethodMultiple(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/**/*Controller.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(2, $filenames);
    }

    public function testPatternMethodCssFiles(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('webroot/**/*.css')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $this->assertEquals('css', $file->getExtension());
            $count++;
        }

        $this->assertEquals(1, $count);
    }

    public function testPatternWithExclude(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/**/*.php')
            ->exclude('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringNotContainsString('Controller', $pathString);
        $this->assertStringContainsString('Model', $pathString);
    }

    public function testPatternWithDepth(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->pattern('**/*.php')
            ->depth(1)
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        // Should find files at depth 1 (src/Controller/, src/View/)
        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertContains('layout.php', $filenames);
    }

    public function testMultiplePatterns(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('**/*.css')
            ->pattern('**/*.js')
            ->files();

        $extensions = [];
        foreach ($files as $file) {
            $extensions[] = $file->getExtension();
        }

        $this->assertContains('css', $extensions);
        $this->assertContains('js', $extensions);
        $this->assertCount(2, $extensions);
    }

    public function testComplexFiltering(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->path('Model')
            ->notPath('Entity')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringContainsString('Model', $pathString);
        $this->assertStringContainsString('Table', $pathString);
        $this->assertStringNotContainsString('Entity', $pathString);
    }

    public function testMultipleInPaths(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src/Controller'))
            ->in(vfsStream::url('root/src/View'))
            ->name('*.php')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // 2 controllers + 1 view file
        $this->assertEquals(3, $count);
    }

    public function testPatternWithMultipleInPaths(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->in(vfsStream::url('root/tests'))
            ->pattern('**/*Controller*.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertContains('UsersControllerTest.php', $filenames);
    }

    public function testNoMatches(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('**/*.nonexistent')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        $this->assertEquals(0, $count);
    }

    public function testNameAndPathCombination(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->name('User*.php')
            ->path('Table')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('UsersTable.php', $filenames);
        $this->assertNotContains('UsersController.php', $filenames);
        $this->assertCount(1, $filenames);
    }

    public function testDepthZero(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->depth(0)
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('index.php', $filenames);
        $this->assertNotContains('style.css', $filenames); // In subdirectory
    }

    public function testExcludeMultiple(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->name('*.php')
            ->exclude('tests')
            ->exclude('webroot')
            ->exclude('View')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringNotContainsString('tests', $pathString);
        $this->assertStringNotContainsString('webroot', $pathString);
        $this->assertStringNotContainsString('View', $pathString);
        $this->assertStringContainsString('Controller', $pathString);
    }

    public function testNotName(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->notName('*Controller.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('User.php', $filenames);
        $this->assertContains('UsersTable.php', $filenames);
        $this->assertContains('layout.php', $filenames);
        $this->assertNotContains('AppController.php', $filenames);
        $this->assertNotContains('UsersController.php', $filenames);
    }

    public function testNotNameMultiple(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->name('*.*')
            ->notName('*.php')
            ->notName('.htaccess')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('style.css', $filenames);
        $this->assertContains('app.js', $filenames);
        $this->assertNotContains('index.php', $filenames);
        $this->assertNotContains('.htaccess', $filenames);
    }

    public function testNameWithNotName(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('User*.php')
            ->notName('*Table.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        // Should match User*.php (User.php, UsersController.php) but exclude *Table.php (UsersTable.php)
        $this->assertContains('User.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertNotContains('UsersTable.php', $filenames);
        $this->assertCount(2, $filenames);
    }

    public function testRecursiveFalse(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->recursive(false)
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        // Should only find files at top level of src/
        // src/ directory itself has no files, only subdirectories
        $this->assertCount(0, $filenames);
    }

    public function testNonRecursiveWithFiles(): void
    {
        // Create structure with files at top level
        vfsStream::setup('test', null, [
            'top.php' => '<?php',
            'another.php' => '<?php',
            'subdir' => [
                'deep.php' => '<?php',
            ],
        ]);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('test'))
            ->recursive(false)
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertCount(2, $filenames);
        $this->assertContains('top.php', $filenames);
        $this->assertContains('another.php', $filenames);
        $this->assertNotContains('deep.php', $filenames);
    }

    public function testNonRecursiveWithNameFilter(): void
    {
        vfsStream::setup('test', null, [
            'match.php' => '<?php',
            'ignore.txt' => 'text',
            'another.php' => '<?php',
        ]);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('test'))
            ->recursive(false)
            ->name('*.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertCount(2, $filenames);
        $this->assertContains('match.php', $filenames);
        $this->assertContains('another.php', $filenames);
        $this->assertNotContains('ignore.txt', $filenames);
    }

    public function testNonRecursiveIgnoresHiddenFiles(): void
    {
        vfsStream::setup('test', null, [
            'visible.txt' => 'content',
            '.hidden' => 'hidden content',
        ]);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('test'))
            ->recursive(false)
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertCount(1, $filenames);
        $this->assertContains('visible.txt', $filenames);
        $this->assertNotContains('.hidden', $filenames);
    }

    public function testNonRecursiveMultipleDirectories(): void
    {
        vfsStream::setup('test', null, [
            'dir1' => [
                'a.txt' => 'content',
            ],
            'dir2' => [
                'b.txt' => 'content',
            ],
        ]);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('test/dir1'))
            ->in(vfsStream::url('test/dir2'))
            ->recursive(false)
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertCount(2, $filenames);
        $this->assertContains('a.txt', $filenames);
        $this->assertContains('b.txt', $filenames);
    }

    public function testExcludeNestedDirectory(): void
    {
        // Create structure with nested directories to verify early pruning
        $structure = [
            'project' => [
                'src' => [
                    'file1.php' => '<?php',
                    'vendor' => [
                        'package1' => [
                            'file2.php' => '<?php',
                            'src' => [
                                'deep.php' => '<?php',
                            ],
                        ],
                        'package2' => [
                            'file3.php' => '<?php',
                        ],
                    ],
                ],
            ],
        ];

        vfsStream::setup('exclude_test', null, $structure);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('exclude_test/project'))
            ->exclude('vendor')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        // Should only find file1.php, not anything in vendor/
        $this->assertCount(1, $paths);
        $this->assertStringContainsString('file1.php', $paths[0]);
        $this->assertStringNotContainsString('vendor', implode(',', $paths));
        $this->assertStringNotContainsString('file2.php', implode(',', $paths));
        $this->assertStringNotContainsString('file3.php', implode(',', $paths));
        $this->assertStringNotContainsString('deep.php', implode(',', $paths));
    }

    public function testDirectories(): void
    {
        $finder = new Finder();
        $directories = $finder->in(vfsStream::url('root/src'))->directories();

        $this->assertInstanceOf(Iterator::class, $directories);

        $paths = [];
        foreach ($directories as $dir) {
            $paths[] = $dir->getFilename();
        }

        // Should find: Controller, Model, View, Entity, Table (all directories)
        $this->assertContains('Controller', $paths);
        $this->assertContains('Model', $paths);
        $this->assertContains('View', $paths);
        $this->assertContains('Entity', $paths);
        $this->assertContains('Table', $paths);

        // Should not contain any files
        $this->assertNotContains('AppController.php', $paths);
        $this->assertNotContains('User.php', $paths);
    }

    public function testDirectoriesNonRecursive(): void
    {
        $finder = new Finder();
        $directories = $finder
            ->in(vfsStream::url('root/src'))
            ->recursive(false)
            ->directories();

        $paths = [];
        foreach ($directories as $dir) {
            $paths[] = $dir->getFilename();
        }

        // Should only find top-level directories: Controller, Model, View
        $this->assertCount(3, $paths);
        $this->assertContains('Controller', $paths);
        $this->assertContains('Model', $paths);
        $this->assertContains('View', $paths);

        // Should not find nested directories
        $this->assertNotContains('Entity', $paths);
        $this->assertNotContains('Table', $paths);
    }

    public function testDirectoriesWithExclude(): void
    {
        $finder = new Finder();
        $directories = $finder
            ->in(vfsStream::url('root/src'))
            ->exclude('Model')
            ->directories();

        $paths = [];
        foreach ($directories as $dir) {
            $paths[] = $dir->getFilename();
        }

        $this->assertContains('Controller', $paths);
        $this->assertContains('View', $paths);

        // Model and its subdirectories should be excluded
        $this->assertNotContains('Model', $paths);
        $this->assertNotContains('Entity', $paths);
        $this->assertNotContains('Table', $paths);
    }

    public function testDirectoriesWithDepth(): void
    {
        $finder = new Finder();
        $directories = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(0)
            ->directories();

        $paths = [];
        foreach ($directories as $dir) {
            $paths[] = $dir->getFilename();
        }

        // Only top-level directories
        $this->assertCount(3, $paths);
        $this->assertContains('Controller', $paths);
        $this->assertContains('Model', $paths);
        $this->assertContains('View', $paths);
    }

    public function testDirectoriesWithNamePattern(): void
    {
        $finder = new Finder();
        $directories = $finder
            ->in(vfsStream::url('root/tests'))
            ->name('*Case')
            ->recursive(false)
            ->directories();

        $paths = [];
        foreach ($directories as $dir) {
            $paths[] = $dir->getFilename();
        }

        // Should match TestCase directory under tests/
        $this->assertCount(1, $paths);
        $this->assertContains('TestCase', $paths);
    }

    public function testAllMode(): void
    {
        $finder = new Finder();
        $items = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(0)
            ->all();

        $files = [];
        $directories = [];
        foreach ($items as $item) {
            if ($item->isDir()) {
                $directories[] = $item->getFilename();
            } else {
                $files[] = $item->getFilename();
            }
        }

        // Should find both files and directories at depth 0
        $this->assertGreaterThan(0, $files);
        $this->assertGreaterThan(0, $directories);
        $this->assertContains('Controller', $directories);
        $this->assertContains('Model', $directories);
        $this->assertContains('View', $directories);
    }

    public function testAllModeRecursive(): void
    {
        $finder = new Finder();
        $items = $finder
            ->in(vfsStream::url('root/src/Model'))
            ->all();

        $files = [];
        $directories = [];
        foreach ($items as $item) {
            if ($item->isDir()) {
                $directories[] = $item->getFilename();
            } else {
                $files[] = $item->getFilename();
            }
        }

        // Should find both Entity and Table directories
        $this->assertContains('Entity', $directories);
        $this->assertContains('Table', $directories);
        // And their files
        $this->assertContains('User.php', $files);
        $this->assertContains('UsersTable.php', $files);
    }

    public function testRegexPathPattern(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->path('/Controller/')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        // Should match files with 'Controller' in path using regex
        $this->assertGreaterThan(0, $paths);
        $this->assertStringContainsString('Controller', implode(',', $paths));
        $this->assertStringContainsString('UsersController.php', implode(',', $paths));
    }

    public function testRegexPathPatternWithDelimiters(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->path('#Model/(Entity|Table)#')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        // Should match files in Model/Entity or Model/Table
        $this->assertCount(2, $paths);
        $this->assertStringContainsString('User.php', implode(',', $paths));
        $this->assertStringContainsString('UsersTable.php', implode(',', $paths));
    }

    public function testMultipleNamePatterns(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->name('*.css')
            ->name('*.js')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Should match both CSS and JS files
        $this->assertCount(2, $paths);
        $this->assertContains('style.css', $paths);
        $this->assertContains('app.js', $paths);
    }

    public function testDepthNotEqual(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(0, DepthOperator::NOT_EQUAL)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        // Should exclude depth 0 files, only get nested files
        $this->assertGreaterThan(0, $paths);
        // All paths should have at least one subdirectory
        foreach ($paths as $path) {
            $relativePath = str_replace(vfsStream::url('root/src') . '/', '', $path);
            $this->assertStringContainsString('/', $relativePath);
        }
    }

    public function testDepthGreaterThan(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(0, DepthOperator::GREATER_THAN)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        // Should only get files deeper than depth 0
        $this->assertGreaterThan(0, $paths);
        foreach ($paths as $path) {
            $relativePath = str_replace(vfsStream::url('root/src') . '/', '', $path);
            $this->assertStringContainsString('/', $relativePath);
        }
    }

    public function testDepthLessThanOrEqual(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(1, DepthOperator::LESS_THAN_OR_EQUAL)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Should get files at depth 0 and 1
        $this->assertGreaterThan(0, $paths);
        // Should include layout.php (depth 1: src/View/layout.php)
        $this->assertContains('layout.php', $paths);
        // Should NOT include User.php (depth 2: src/Model/Entity/User.php)
        $this->assertNotContains('User.php', $paths);
    }

    public function testDepthGreaterThanOrEqual(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(1, DepthOperator::GREATER_THAN_OR_EQUAL)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Should get files at depth 1 and deeper
        $this->assertGreaterThan(0, $paths);
        // Should include both depth 1 and depth 2 files
        $this->assertContains('layout.php', $paths); // depth 1
        $this->assertContains('User.php', $paths); // depth 2
    }

    public function testDepthRangeQuery(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth(0, DepthOperator::GREATER_THAN) // Greater than 0
            ->depth(2, DepthOperator::LESS_THAN) // Less than 2
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Should only get files at depth 1 (between 0 and 2)
        $this->assertGreaterThan(0, $paths);
        $this->assertContains('layout.php', $paths); // depth 1
        $this->assertContains('AppController.php', $paths); // depth 1
        $this->assertNotContains('User.php', $paths); // depth 2
    }

    public function testIgnoreHiddenFilesFalse(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->ignoreHiddenFiles(false)
            ->recursive(false)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Should include .htaccess file
        $this->assertContains('.htaccess', $paths);
        $this->assertContains('index.php', $paths);
    }

    public function testEmptyDirectory(): void
    {
        // Create an empty directory
        vfsStream::create(['empty' => []], $this->root);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/empty'))
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // Should return no files
        $this->assertSame(0, $count);
    }

    public function testConflictingNameFilters(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*Controller.php')
            ->notName('*Controller.php')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // Should return no files due to conflicting filters
        $this->assertSame(0, $count);
    }

    public function testMultiplePathPatternsOr(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->path('Controller')
            ->path('Entity')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        // Should match files with either Controller OR Entity in path
        $this->assertGreaterThan(0, $paths);
        $this->assertStringContainsString('Controller', implode(',', $paths));
        $this->assertStringContainsString('Entity', implode(',', $paths));
        $this->assertStringContainsString('User.php', implode(',', $paths));
        $this->assertStringContainsString('UsersController.php', implode(',', $paths));
    }

    /**
     * Test filter() with single callback
     */
    public function testFilterSingleCallback(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->filter(function (SplFileInfo $file) {
                // Only files with "Controller" in name
                return str_contains($file->getFilename(), 'Controller');
            })
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertCount(2, $filenames);
        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
    }

    /**
     * Test filter() with multiple chained callbacks (AND logic)
     */
    public function testFilterMultipleCallbacks(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->filter(fn(SplFileInfo $file) => str_contains($file->getFilename(), 'User'))
            ->filter(fn(SplFileInfo $file) => !str_contains($file->getFilename(), 'Table'))
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        // Should find User.php and UsersController.php, but not UsersTable.php
        $this->assertCount(2, $filenames);
        $this->assertContains('User.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertNotContains('UsersTable.php', $filenames);
    }

    /**
     * Test filter() with relative path parameter
     */
    public function testFilterWithRelativePath(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->filter(function (SplFileInfo $file, string $relativePath) {
                // Only files in src directory
                return str_starts_with($relativePath, 'src');
            })
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
            $this->assertStringContainsString('src', $file->getPathname());
        }

        $this->assertGreaterThan(0, $count);
    }

    /**
     * Test filter() combined with other filters
     */
    public function testFilterWithOtherFilters(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*Controller.php')
            ->filter(fn(SplFileInfo $file) => strlen($file->getFilename()) > 18)
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        // UsersController.php (20 chars) is >18, AppController.php (17 chars) is not
        $this->assertCount(1, $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertNotContains('AppController.php', $filenames);
    }

    /**
     * Test filter() returning empty results
     */
    public function testFilterNoMatches(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->filter(fn(SplFileInfo $file) => str_contains($file->getFilename(), 'NonExistent'))
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        $this->assertSame(0, $count);
    }
}
