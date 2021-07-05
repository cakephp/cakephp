<?php
declare(strict_types=1);

/**
 * FolderTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Filesystem;

use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;

/**
 * FolderTest class
 */
class FolderTest extends TestCase
{
    /**
     * setUp clearstatcache() to flush file descriptors.
     */
    public function setUp(): void
    {
        parent::setUp();
        clearstatcache();
    }

    /**
     * Remove TMP/tests directory to its original state.
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $cleaner = function ($dir) use (&$cleaner): void {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . DS . $file;
                if (is_dir($path)) {
                    $cleaner($path);
                } else {
                    unlink($path);
                }
            }
            rmdir($dir);
        };
        if (file_exists(TMP . 'tests')) {
            $cleaner(TMP . 'tests');
        }
        parent::tearDown();
    }

    /**
     * testBasic method
     */
    public function testBasic(): void
    {
        $path = __DIR__;
        $Folder = new Folder($path);

        $result = $Folder->pwd();
        $this->assertSame($path, $result);

        $result = Folder::addPathElement($path, 'test');
        $expected = $path . DS . 'test';
        $this->assertSame($expected, $result);

        $result = $Folder->cd(ROOT);
        $expected = ROOT;
        $this->assertSame($expected, $result);

        $result = $Folder->cd(ROOT . DS . 'nonexistent');
        $this->assertFalse($result);
    }

    /**
     * testInPath method
     */
    public function testInPath(): void
    {
        // "/tests/test_app/"
        $basePath = TEST_APP;
        $Base = new Folder($basePath);

        $result = $Base->pwd();
        $this->assertSame($basePath, $result);

        // is "/" in "/tests/test_app/"
        $result = $Base->inPath(realpath(DS), true);
        $this->assertFalse($result);

        // is "/tests/test_app/" in "/tests/test_app/"
        $result = $Base->inPath($basePath, true);
        $this->assertTrue($result);

        // is "/tests/test_app" in "/tests/test_app/"
        $result = $Base->inPath(mb_substr($basePath, 0, -1), true);
        $this->assertTrue($result);

        // is "/tests/test_app/sub" in "/tests/test_app/"
        $result = $Base->inPath($basePath . 'sub', true);
        $this->assertTrue($result);

        // is "/tests" in "/tests/test_app/"
        $result = $Base->inPath(dirname($basePath), true);
        $this->assertFalse($result);

        // is "/tests/other/(...)tests/test_app" in "/tests/test_app/"
        $result = $Base->inPath(TMP . 'tests' . DS . 'other' . DS . $basePath, true);
        $this->assertFalse($result);

        // is "/tests/test_app/" in "/"
        $result = $Base->inPath(realpath(DS));
        $this->assertTrue($result);

        // is "/tests/test_app/" in "/tests/test_app/"
        $result = $Base->inPath($basePath);
        $this->assertTrue($result);

        // is "/tests/test_app/" in "/tests/test_app"
        $result = $Base->inPath(mb_substr($basePath, 0, -1));
        $this->assertTrue($result);

        // is "/tests/test_app/" in "/tests"
        $result = $Base->inPath(dirname($basePath));
        $this->assertTrue($result);

        // is "/tests/test_app/" in "/tests/test_app/sub"
        $result = $Base->inPath($basePath . 'sub');
        $this->assertFalse($result);

        // is "/other/tests/test_app/" in "/tests/test_app/"
        $VirtualBase = new Folder();
        $VirtualBase->path = '/other/tests/test_app';
        $result = $VirtualBase->inPath('/tests/test_app/');
        $this->assertFalse($result);
    }

    /**
     * Data provider for the testInPathInvalidPathArgument test
     *
     * @return array
     */
    public function inPathInvalidPathArgumentDataProvider(): array
    {
        return [
            [''],
            ['relative/path/'],
            ['unknown://stream-wrapper'],
        ];
    }

    /**
     * @dataProvider inPathInvalidPathArgumentDataProvider
     * @param string $path
     */
    public function testInPathInvalidPathArgument($path): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $path argument is expected to be an absolute path.');
        $Folder = new Folder();
        $Folder->inPath($path);
    }

    /**
     * test creation of single and multiple paths.
     */
    public function testCreation(): void
    {
        $Folder = new Folder(TMP . 'tests');
        $result = $Folder->create(TMP . 'tests' . DS . 'first' . DS . 'second' . DS . 'third');
        $this->assertTrue($result);

        rmdir(TMP . 'tests' . DS . 'first' . DS . 'second' . DS . 'third');
        rmdir(TMP . 'tests' . DS . 'first' . DS . 'second');
        rmdir(TMP . 'tests' . DS . 'first');

        $Folder = new Folder(TMP . 'tests');
        $result = $Folder->create(TMP . 'tests' . DS . 'first');
        $this->assertTrue($result);
    }

    /**
     * test that creation of folders with trailing ds works
     */
    public function testCreateWithTrailingDs(): void
    {
        $Folder = new Folder(TMP . 'tests');
        $path = TMP . 'tests' . DS . 'trailing' . DS . 'dir' . DS;
        $result = $Folder->create($path);
        $this->assertTrue($result);

        $this->assertDirectoryExists($path, 'Folder was not made');

        $Folder = new Folder(TMP . 'tests' . DS . 'trailing');
        $this->assertTrue($Folder->delete());
    }

    /**
     * Test that relative paths to create() are added to cwd.
     */
    public function testCreateRelative(): void
    {
        $folder = new Folder(TMP);
        $path = TMP . 'tests' . DS . 'relative-test';
        $result = $folder->create('tests' . DS . 'relative-test');
        $this->assertTrue($result, 'should create');

        $this->assertDirectoryExists($path, 'Folder was not made');
        $folder = new Folder($path);
        $folder->delete();
    }

    /**
     * test recursive directory create failure.
     */
    public function testRecursiveCreateFailure(): void
    {
        $this->skipIf(DS === '\\', 'Cant perform operations using permissions on windows.');

        $path = TMP . 'tests/one';
        mkdir($path, 0777, true);
        chmod($path, 0444);

        try {
            $Folder = new Folder($path);
            $result = $Folder->create($path . DS . 'two/three');
            $this->assertFalse($result);
        } catch (\Exception $e) {
            $this->assertInstanceOf('PHPUnit\Framework\Error\Error', $e);
        }

        chmod($path, 0777);
        rmdir($path);
    }

    /**
     * testOperations method
     */
    public function testOperations(): void
    {
        $path = ROOT . DS . 'templates';
        $Folder = new Folder($path);

        $result = $Folder->pwd();
        $this->assertSame($path, $result);

        $new = TMP . 'tests' . DS . 'test_folder_new';
        $result = $Folder->create($new);
        $this->assertTrue($result);

        $copy = TMP . 'tests' . DS . 'test_folder_copy';
        $result = $Folder->copy($copy);
        $this->assertTrue($result);

        $copy = TMP . 'tests' . DS . 'test_folder_copy';
        $result = $Folder->copy($copy);
        $this->assertTrue($result);

        $copy = TMP . 'tests' . DS . 'test_folder_copy';
        $result = $Folder->chmod($copy, 0755, false);
        $this->assertTrue($result);

        $result = $Folder->cd($copy);
        $this->assertTrue((bool)$result);

        $mv = TMP . 'tests' . DS . 'test_folder_mv';
        $result = $Folder->move($mv);
        $this->assertTrue($result);

        $mv = TMP . 'tests' . DS . 'test_folder_mv_2';
        $result = $Folder->move($mv);
        $this->assertTrue($result);

        $result = $Folder->delete($new);
        $this->assertTrue($result);

        $result = $Folder->delete($mv);
        $this->assertTrue($result);

        $result = $Folder->delete($mv);
        $this->assertTrue($result);

        $new = CONFIG . 'acl.ini';
        $result = $Folder->create($new);
        $this->assertFalse($result);

        $expected = $new . ' is a file';
        $result = $Folder->errors();
        $this->assertSame($expected, $result[0]);

        $new = TMP . 'tests' . DS . 'test_folder_new';
        $result = $Folder->create($new);
        $this->assertTrue($result);

        $result = $Folder->cd($new);
        $this->assertTrue((bool)$result);

        $result = $Folder->delete();
        $this->assertTrue($result);

        $Folder = new Folder('nonexistent');
        $result = $Folder->pwd();
        $this->assertNull($result);
    }

    /**
     * testChmod method
     */
    public function testChmod(): void
    {
        $this->skipIf(DS === '\\', 'Folder permissions tests not supported on Windows.');

        $path = TMP . 'tests/';
        $Folder = new Folder($path);

        $subdir = 'test_folder_new';
        $new = $path . $subdir;

        $this->assertTrue($Folder->create($new));
        $this->assertTrue($Folder->create($new . DS . 'test1'));
        $this->assertTrue($Folder->create($new . DS . 'test2'));

        $filePath = $new . DS . 'test1.php';
        $File = new File($filePath);
        $this->assertTrue($File->create());

        $filePath = $new . DS . 'skip_me.php';
        $File = new File($filePath);
        $this->assertTrue($File->create());

        $this->assertTrue($Folder->chmod($new, 0755, true));
        $perms = substr(sprintf('%o', fileperms($new . DS . 'test2')), -4);
        $this->assertSame('0755', $perms);

        $this->assertTrue($Folder->chmod($new, 0744, true, ['skip_me.php', 'test2']));

        $perms = substr(sprintf('%o', fileperms($new . DS . 'test2')), -4);
        $this->assertSame('0755', $perms);

        $perms = substr(sprintf('%o', fileperms($new . DS . 'test1')), -4);
        $this->assertSame('0744', $perms);
    }

    /**
     * testRealPathForWebroot method
     */
    public function testRealPathForWebroot(): void
    {
        $Folder = new Folder('files' . DS);
        $this->assertEquals(realpath('files' . DS), $Folder->path);
    }

    /**
     * testZeroAsDirectory method
     */
    public function testZeroAsDirectory(): void
    {
        $path = TMP . 'tests';
        $Folder = new Folder($path, true);
        $new = $path . '/0';
        $this->assertTrue($Folder->create($new));

        $result = $Folder->read(true, true);
        $this->assertContains('0', $result[0]);

        $result = $Folder->read(true, ['logs']);
        $this->assertContains('0', $result[0]);

        $result = $Folder->delete($new);
        $this->assertTrue($result);
    }

    /**
     * test Adding path elements to a path
     */
    public function testAddPathElement(): void
    {
        $expected = DS . 'some' . DS . 'dir' . DS . 'another_path';

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', 'another_path');
        $this->assertSame($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir' . DS, 'another_path');
        $this->assertSame($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', ['another_path']);
        $this->assertSame($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir' . DS, ['another_path']);
        $this->assertSame($expected, $result);

        $expected = DS . 'some' . DS . 'dir' . DS . 'another_path' . DS . 'and' . DS . 'another';

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', ['another_path', 'and', 'another']);
        $this->assertSame($expected, $result);
    }

    /**
     * testFolderRead method
     */
    public function testFolderRead(): void
    {
        $Folder = new Folder(CAKE);

        $result = $Folder->read(true, true);
        $this->assertContains('Core', $result[0]);
        $this->assertContains('Cache', $result[0]);

        $Folder = new Folder(TMP . 'nonexistent');
        $expected = [[], []];
        $result = $Folder->read(true, true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderReadWithHiddenFiles method
     */
    public function testFolderReadWithHiddenFiles(): void
    {
        $this->skipIf(!is_writable(TMP), 'Cant test Folder::read with hidden files unless the tmp folder is writable.');
        $path = TMP . 'tests' . DS;

        $Folder = new Folder($path . 'folder_tree_hidden', true, 0777);
        mkdir($Folder->path . DS . '.svn');
        mkdir($Folder->path . DS . 'some_folder');
        touch($Folder->path . DS . 'not_hidden.txt');
        touch($Folder->path . DS . '.hidden.txt');

        $expected = [
            ['some_folder'],
            ['not_hidden.txt'],
        ];
        $result = $Folder->read(true, true);
        $this->assertEquals($expected, $result);

        $expected = [
            [
                '.svn',
                'some_folder',
            ],
            [
                '.hidden.txt',
                'not_hidden.txt',
            ],
        ];
        $result = $Folder->read(true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderSubdirectories method
     */
    public function testFolderSubdirectories(): void
    {
        $path = CAKE . 'Http';
        $folder = new Folder($path);

        $expected = [
            $path . DS . 'Client',
            $path . DS . 'Cookie',
            $path . DS . 'Exception',
            $path . DS . 'Middleware',
            $path . DS . 'Session',
        ];
        $result = $folder->subdirectories();
        $this->assertSame([], array_diff($expected, $result));
        $result = $folder->subdirectories($path);
        $this->assertSame([], array_diff($expected, $result));

        $expected = [
            'Client',
            'Cookie',
            'Exception',
            'Middleware',
            'Session',
        ];
        $result = $folder->subdirectories(null, false);
        $this->assertSame([], array_diff($expected, $result));
        $result = $folder->subdirectories($path, false);
        $this->assertSame([], array_diff($expected, $result));

        $expected = [];
        $result = $folder->subdirectories('NonExistentPath');
        $this->assertSame([], array_diff($expected, $result));
        $result = $folder->subdirectories($path . DS . 'Exception');
        $this->assertSame([], array_diff($expected, $result));
    }

    /**
     * testFolderTree method
     */
    public function testFolderTree(): void
    {
        $Folder = new Folder();
        $expected = [
            [
                CORE_PATH . 'config',
            ],
            [
                CORE_PATH . 'config' . DS . 'config.php',
            ],
        ];

        $result = $Folder->tree(CORE_PATH . 'config', false);
        $this->assertSame([], array_diff($expected[0], $result[0]));
        $this->assertSame([], array_diff($result[0], $expected[0]));

        $result = $Folder->tree(CORE_PATH . 'config', false, 'dir');
        $this->assertSame([], array_diff($expected[0], $result));
        $this->assertSame([], array_diff($expected[0], $result));

        $result = $Folder->tree(CORE_PATH . 'config', false, 'files');
        $this->assertSame([], array_diff($expected[1], $result));
        $this->assertSame([], array_diff($expected[1], $result));
    }

    /**
     * testFolderTreeWithHiddenFiles method
     */
    public function testFolderTreeWithHiddenFiles(): void
    {
        $this->skipIf(!is_writable(TMP), 'Can\'t test Folder::tree with hidden files unless the tmp folder is writable.');
        $path = TMP . 'tests' . DS;

        $Folder = new Folder($path . 'folder_tree_hidden', true, 0777);
        mkdir($Folder->path . DS . '.svn', 0777, true);
        touch($Folder->path . DS . '.svn/InHiddenFolder.php');
        mkdir($Folder->path . DS . '.svn/inhiddenfolder');
        touch($Folder->path . DS . '.svn/inhiddenfolder/NestedInHiddenFolder.php');
        touch($Folder->path . DS . 'not_hidden.txt');
        touch($Folder->path . DS . '.hidden.txt');
        mkdir($Folder->path . DS . 'visible_folder/.git', 0777, true);

        $expected = [
            [
                $Folder->path,
                $Folder->path . DS . 'visible_folder',
            ],
            [
                $Folder->path . DS . 'not_hidden.txt',
            ],
        ];

        $result = $Folder->tree(null, true);
        $this->assertEquals($expected, $result);

        $result = $Folder->tree(null, ['.']);
        $this->assertEquals($expected, $result);

        $expected = [
            [
                $Folder->path,
                $Folder->path . DS . 'visible_folder',
                $Folder->path . DS . 'visible_folder' . DS . '.git',
                $Folder->path . DS . '.svn',
                $Folder->path . DS . '.svn' . DS . 'inhiddenfolder',
            ],
            [
                $Folder->path . DS . 'not_hidden.txt',
                $Folder->path . DS . '.hidden.txt',
                $Folder->path . DS . '.svn' . DS . 'inhiddenfolder' . DS . 'NestedInHiddenFolder.php',
                $Folder->path . DS . '.svn' . DS . 'InHiddenFolder.php',
            ],
        ];

        $result = $Folder->tree(null, false);
        sort($result[0]);
        sort($expected[0]);
        sort($result[1]);
        sort($expected[1]);
        $this->assertEquals($expected, $result);

        $Folder->delete();
    }

    /**
     * testWindowsPath method
     */
    public function testWindowsPath(): void
    {
        $this->assertFalse(Folder::isWindowsPath('0:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('C:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('d:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('\\\\vmware-host\\Shared Folders\\file'));
    }

    /**
     * testIsAbsolute method
     */
    public function testIsAbsolute(): void
    {
        $this->assertFalse(Folder::isAbsolute('path/to/file'));
        $this->assertFalse(Folder::isAbsolute('cake/'));
        $this->assertFalse(Folder::isAbsolute('path\\to\\file'));
        $this->assertFalse(Folder::isAbsolute('0:\\path\\to\\file'));
        $this->assertFalse(Folder::isAbsolute('\\path/to/file'));
        $this->assertFalse(Folder::isAbsolute('\\path\\to\\file'));
        $this->assertFalse(Folder::isAbsolute('notRegisteredStreamWrapper://example'));
        $this->assertFalse(Folder::isAbsolute('://example'));

        $this->assertTrue(Folder::isAbsolute('/usr/local'));
        $this->assertTrue(Folder::isAbsolute('//path/to/file'));
        $this->assertTrue(Folder::isAbsolute('C:\\cake'));
        $this->assertTrue(Folder::isAbsolute('C:\\path\\to\\file'));
        $this->assertTrue(Folder::isAbsolute('d:\\path\\to\\file'));
        $this->assertTrue(Folder::isAbsolute('\\\\vmware-host\\Shared Folders\\file'));
        $this->assertTrue(Folder::isAbsolute('http://www.example.com'));
    }

    /**
     * testIsSlashTerm method
     */
    public function testIsSlashTerm(): void
    {
        $this->assertFalse(Folder::isSlashTerm('cake'));

        $this->assertTrue(Folder::isSlashTerm('C:\\cake\\'));
        $this->assertTrue(Folder::isSlashTerm('/usr/local/'));
    }

    /**
     * testStatic method
     */
    public function testSlashTerm(): void
    {
        $result = Folder::slashTerm('/path/to/file');
        $this->assertSame('/path/to/file/', $result);
    }

    /**
     * testNormalizeFullPath method
     */
    public function testNormalizeFullPath(): void
    {
        $path = '/path/to\file';
        $expected = '/path/to/file';
        $result = Folder::normalizeFullPath($path);
        $this->assertSame($expected, $result);

        $path = '\\path\\to\file';
        $expected = '/path/to/file';
        $result = Folder::normalizeFullPath($path);
        $this->assertSame($expected, $result);

        $path = 'C:\\path/to/file';
        $expected = 'C:\\path\\to\\file';
        $result = Folder::normalizeFullPath($path);
        $this->assertSame($expected, $result);
    }

    /**
     * correctSlashFor method
     */
    public function testCorrectSlashFor(): void
    {
        $path = '/path/to/file';
        $result = Folder::correctSlashFor($path);
        $this->assertSame('/', $result);

        $path = '\\path\\to\\file';
        $result = Folder::correctSlashFor($path);
        $this->assertSame('/', $result);

        $path = 'C:\\path\to\\file';
        $result = Folder::correctSlashFor($path);
        $this->assertSame('\\', $result);
    }

    /**
     * testFind method
     */
    public function testFind(): void
    {
        $Folder = new Folder();
        $Folder->cd(CORE_PATH . 'config');
        $result = $Folder->find();
        $expected = ['config.php'];
        $this->assertSame(array_diff($expected, $result), []);
        $this->assertSame(array_diff($expected, $result), []);

        $result = $Folder->find('.*', true);
        $expected = ['bootstrap.php', 'config.php'];
        $this->assertSame($expected, $result);

        $result = $Folder->find('.*\.php');
        $expected = ['bootstrap.php', 'config.php'];
        $this->assertSame(array_diff($expected, $result), []);
        $this->assertSame(array_diff($expected, $result), []);

        $result = $Folder->find('.*\.php', true);
        $expected = ['bootstrap.php', 'config.php'];
        $this->assertSame($expected, $result);

        $result = $Folder->find('.*ig\.php');
        $expected = ['config.php'];
        $this->assertSame($expected, $result);

        $result = $Folder->find('config\.php');
        $expected = ['config.php'];
        $this->assertSame($expected, $result);

        $Folder = new Folder(TMP . 'tests/', true);
        new File($Folder->pwd() . DS . 'paths.php', true);
        $Folder->create($Folder->pwd() . DS . 'testme');
        $Folder->cd('testme');
        $result = $Folder->find('paths\.php');
        $expected = [];
        $this->assertSame($expected, $result);

        $Folder->cd($Folder->pwd() . '/..');
        $result = $Folder->find('paths\.php');
        $expected = ['paths.php'];
        $this->assertSame($expected, $result);
    }

    /**
     * testFindRecursive method
     */
    public function testFindRecursive(): void
    {
        $Folder = new Folder(CORE_PATH . 'config');
        $result = $Folder->findRecursive('(config|paths)\.php');
        $expected = [
            CORE_PATH . 'config' . DS . 'config.php',
        ];
        $this->assertSame([], array_diff($expected, $result));

        $result = $Folder->findRecursive('(config|bootstrap)\.php', true);
        $expected = [
            CORE_PATH . 'config' . DS . 'bootstrap.php',
            CORE_PATH . 'config' . DS . 'config.php',
        ];
        $this->assertSame($expected, $result);

        $path = TMP . 'tests' . DS;
        $Folder = new Folder($path, true);
        $Folder->create($path . 'sessions');
        $Folder->create($path . 'testme');

        $Folder->cd($path . 'testme');
        $File = new File($Folder->pwd() . DS . 'paths.php');
        $File->create();

        $Folder->cd($path . 'sessions');
        $result = $Folder->findRecursive('paths\.php');
        $expected = [];
        $this->assertSame($expected, $result);

        $Folder->cd($path . 'testme');
        $File = new File($Folder->pwd() . DS . 'my.php');
        $File->create();

        $Folder->cd($path);

        $result = $Folder->findRecursive('(paths|my)\.php');
        $expected = [
            $path . 'testme' . DS . 'my.php',
            $path . 'testme' . DS . 'paths.php',
        ];
        $this->assertSame(sort($expected), sort($result));

        $result = $Folder->findRecursive('(paths|my)\.php', true);
        $expected = [
            $path . 'testme' . DS . 'my.php',
            $path . 'testme' . DS . 'paths.php',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testConstructWithNonExistentPath method
     */
    public function testConstructWithNonExistentPath(): void
    {
        $path = TMP . 'tests' . DS;
        $Folder = new Folder($path . 'config_nonexistent', true);
        $this->assertDirectoryExists($path . 'config_nonexistent');
        $Folder->cd($path);
    }

    /**
     * testDirSize method
     */
    public function testDirSize(): void
    {
        $path = TMP . 'tests' . DS;
        $Folder = new Folder($path . 'config_nonexistent', true);
        $this->assertSame(0, $Folder->dirSize());

        $File = new File($Folder->pwd() . DS . 'my.php', true, 0777);
        $File->create();
        $File->write('something here');
        $File->close();
        $this->assertSame(14, $Folder->dirSize());
    }

    /**
     * test that errors and messages can be restarted
     */
    public function testReset(): void
    {
        $path = TMP . 'tests' . DS . 'folder_delete_test';
        mkdir($path, 0777, true);
        $folder = $path . DS . 'sub';
        mkdir($folder);
        $file = $folder . DS . 'file';
        touch($file);

        chmod($folder, 0555);
        chmod($file, 0444);

        $Folder = new Folder($folder);
        $return = $Folder->delete();
        $this->assertFalse($return);

        $messages = $Folder->messages();
        $errors = $Folder->errors();
        $expected = [
            $file . ' NOT removed',
            $folder . ' NOT removed',
        ];
        sort($expected);
        sort($errors);
        $this->assertEmpty($messages);
        $this->assertEquals($expected, $errors);

        chmod($file, 0644);
        chmod($folder, 0755);

        $return = $Folder->delete();
        $this->assertTrue($return);

        $messages = $Folder->messages();
        $errors = $Folder->errors();
        $expected = [
            $file . ' removed',
            $folder . ' removed',
        ];
        sort($expected);
        sort($messages);
        $this->assertEmpty($errors);
        $this->assertEquals($expected, $messages);
    }

    /**
     * testDelete method
     */
    public function testDelete(): void
    {
        $path = TMP . 'tests' . DS . 'folder_delete_test';
        mkdir($path, 0777, true);
        touch($path . DS . 'file_1');
        mkdir($path . DS . 'level_1_1');
        touch($path . DS . 'level_1_1/file_1_1');
        mkdir($path . DS . 'level_1_1/level_2_1');
        touch($path . DS . 'level_1_1/level_2_1/file_2_1');
        touch($path . DS . 'level_1_1/level_2_1/file_2_2');
        mkdir($path . DS . 'level_1_1/level_2_2');

        $Folder = new Folder($path, true);
        $return = $Folder->delete();
        $this->assertTrue($return);

        $messages = $Folder->messages();
        $errors = $Folder->errors();
        $this->assertEquals([], $errors);

        $expected = [
            $path . DS . 'file_1 removed',
            $path . DS . 'level_1_1' . DS . 'file_1_1 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_1 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_2 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_1 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_2 removed',
            $path . DS . 'level_1_1 removed',
            $path . ' removed',
        ];
        sort($expected);
        sort($messages);
        $this->assertEquals($expected, $messages);
    }

    /**
     * testCopy method
     *
     * Verify that subdirectories existing in both destination and source directory
     * are merged recursively.
     */
    public function testCopy(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertFileExists($folderThree . DS . 'file1.php');
        $this->assertFileExists($folderThree . DS . 'folderA' . DS . 'fileA.php');

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertFileExists($folderThree . DS . 'file1.php');
        $this->assertFileExists($folderThree . DS . 'file2.php');
        $this->assertFileExists($folderThree . DS . 'folderA' . DS . 'fileA.php');
        $this->assertFileExists($folderThree . DS . 'folderB' . DS . 'fileB.php');

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * testCopyWithMerge method
     *
     * Verify that subdirectories existing in both destination and source directory
     * are merged recursively.
     */
    public function testCopyWithMerge(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertFileExists($folderThree . DS . 'file1.php');
        $this->assertFileExists($folderThree . DS . 'folderA' . DS . 'fileA.php');

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy($folderThree, ['scheme' => Folder::MERGE]);
        $this->assertTrue($result);
        $this->assertFileExists($folderThree . DS . 'file1.php');
        $this->assertFileExists($folderThree . DS . 'file2.php');
        $this->assertFileExists($folderThree . DS . 'folderA' . DS . 'fileA.php');
        $this->assertFileExists($folderThree . DS . 'folderB' . DS . 'fileB.php');
    }

    /**
     * testCopyWithSkip method
     *
     * Verify that directories and files are copied recursively
     * even if the destination directory already exists.
     * Subdirectories existing in both destination and source directory
     * are skipped and not merged or overwritten.
     */
    public function testCopyWithSkip(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderTwo, ['scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . DS . 'file1.php');
        $this->assertFileExists($folderTwo . DS . 'folderA' . DS . 'fileA.php');

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderTwo, ['scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . DS . 'file1.php');
        $this->assertFileExists($folderTwo . DS . 'folderA' . DS . 'fileA.php');

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderTwo, true);
        new Folder($folderTwo . DS . 'folderB', true);
        file_put_contents($folderTwo . DS . 'file2.php', 'touched');
        file_put_contents($folderTwo . DS . 'folderB' . DS . 'fileB.php', 'untouched');

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy($folderThree, ['scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertFileExists($folderThree . DS . 'file2.php');
        $this->assertStringEqualsFile($folderThree . DS . 'file2.php', 'touched');
        $this->assertStringEqualsFile($folderThree . DS . 'folderB' . DS . 'fileB.php', 'untouched');
    }

    /**
     * Test that SKIP mode skips files too.
     */
    public function testCopyWithSkipFileSkipped(): void
    {
        $path = TMP . 'folder_test';
        $folderOne = $path . DS . 'folder1';
        $folderTwo = $path . DS . 'folder2';

        new Folder($path, true);
        new Folder($folderOne, true);
        new Folder($folderTwo, true);
        file_put_contents($folderOne . DS . 'fileA.txt', 'Folder One File');
        file_put_contents($folderTwo . DS . 'fileA.txt', 'Folder Two File');

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderTwo, ['scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertStringEqualsFile($folderTwo . DS . 'fileA.txt', 'Folder Two File');
    }

    /**
     * testCopyWithOverwrite
     *
     * Verify that subdirectories existing in both destination and source directory
     * are overwritten/replaced recursively.
     */
    public function testCopyWithOverwrite(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $Folder->copy($folderThree, ['scheme' => Folder::OVERWRITE]);

        $this->assertFileExists($folderThree . DS . 'file1.php');
        $this->assertFileExists($folderThree . DS . 'folderA' . DS . 'fileA.php');

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy($folderThree, ['scheme' => Folder::OVERWRITE]);
        $this->assertTrue($result);

        $this->assertFileExists($folderThree . DS . 'folderA' . DS . 'fileA.php');

        $Folder = new Folder($folderOne);
        unlink($fileOneA);
        $result = $Folder->copy($folderThree, ['scheme' => Folder::OVERWRITE]);
        $this->assertTrue($result);

        $this->assertFileExists($folderThree . DS . 'file1.php');
        $this->assertFileExists($folderThree . DS . 'file2.php');
        $this->assertFileDoesNotExist($folderThree . DS . 'folderA' . DS . 'fileA.php');
        $this->assertFileExists($folderThree . DS . 'folderB' . DS . 'fileB.php');
    }

    /**
     * testCopyWithoutRecursive
     *
     * Verify that only the files exist in the target directory.
     */
    public function testCopyWithoutRecursive(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $Folder->copy($folderThree, ['recursive' => false]);

        $this->assertFileExists($folderThree . DS . 'file1.php');
        $this->assertDirectoryDoesNotExist($folderThree . DS . 'folderA');
        $this->assertFileDoesNotExist($folderThree . DS . 'folderA' . DS . 'fileA.php');
    }

    /**
     * Setup filesystem for copy tests
     * $path: folder_test/
     *
     * - folder1/file1.php
     * - folder1/folderA/fileA.php
     * - folder2/file2.php
     * - folder2/folderB/fileB.php
     * - folder3/
     *
     * @return array Filenames to extract in the test methods
     */
    protected function _setupFilesystem(): array
    {
        $path = TMP . 'tests';

        $folderOne = $path . DS . 'folder1';
        $folderOneA = $folderOne . DS . 'folderA';
        $folderTwo = $path . DS . 'folder2';
        $folderTwoB = $folderTwo . DS . 'folderB';
        $folderThree = $path . DS . 'folder3';

        $fileOne = $folderOne . DS . 'file1.php';
        $fileTwo = $folderTwo . DS . 'file2.php';
        $fileOneA = $folderOneA . DS . 'fileA.php';
        $fileTwoB = $folderTwoB . DS . 'fileB.php';

        new Folder($path, true);
        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        new Folder($folderTwoB, true);
        new Folder($folderThree, true);
        touch($fileOne);
        touch($fileTwo);
        touch($fileOneA);
        touch($fileTwoB);

        return compact(
            'path',
            'folderOne',
            'folderOneA',
            'folderTwo',
            'folderTwoB',
            'folderThree',
            'fileOne',
            'fileOneA',
            'fileTwo',
            'fileTwoB'
        );
    }

    /**
     * testMove method
     *
     * Verify that directories and files are moved recursively
     * even if the destination directory already exists.
     * Subdirectories existing in both destination and source directory
     * are merged recursively.
     */
    public function testMove(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . '/file1.php');
        $this->assertDirectoryExists($folderTwo . '/folderB');
        $this->assertFileExists($folderTwo . '/folderB/fileB.php');
        $this->assertFileDoesNotExist($fileOne);
        $this->assertFileExists($folderTwo . '/folderA');
        $this->assertFileDoesNotExist($folderOneA);
        $this->assertFileDoesNotExist($fileOneA);

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        touch($fileOne);
        touch($fileOneA);

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . '/file1.php');
        $this->assertDirectoryExists($folderTwo . '/folderA');
        $this->assertFileExists($folderTwo . '/folderA/fileA.php');
        $this->assertFileDoesNotExist($fileOne);
        $this->assertFileDoesNotExist($folderOneA);
        $this->assertFileDoesNotExist($fileOneA);

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        new Folder($folderTwoB, true);
        touch($fileOne);
        touch($fileOneA);
        new Folder($folderOne . '/folderB', true);
        touch($folderOne . '/folderB/fileB.php');
        file_put_contents($folderTwoB . '/fileB.php', 'untouched');

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . '/file1.php');
        $this->assertStringEqualsFile($folderTwoB . '/fileB.php', '');
        $this->assertFileDoesNotExist($fileOne);
        $this->assertFileDoesNotExist($folderOneA);
        $this->assertFileDoesNotExist($fileOneA);

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * testMoveWithSkip method
     *
     * Verify that directories and files are moved recursively
     * even if the destination directory already exists.
     * Subdirectories existing in both destination and source directory
     * are skipped and not merged or overwritten.
     */
    public function testMoveWithSkip(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo, ['scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . '/file1.php');
        $this->assertDirectoryExists($folderTwo . '/folderB');
        $this->assertFileExists($folderTwoB . '/fileB.php');
        $this->assertFileDoesNotExist($fileOne);
        $this->assertFileDoesNotExist($folderOneA);
        $this->assertFileDoesNotExist($fileOneA);

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        touch($fileOne);
        touch($fileOneA);

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo, ['scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . '/file1.php');
        $this->assertDirectoryExists($folderTwo . '/folderA');
        $this->assertFileExists($folderTwo . '/folderA/fileA.php');
        $this->assertFileDoesNotExist($fileOne);
        $this->assertFileDoesNotExist($folderOneA);
        $this->assertFileDoesNotExist($fileOneA);

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        new Folder($folderTwoB, true);
        touch($fileOne);
        touch($fileOneA);
        file_put_contents($folderTwoB . '/fileB.php', 'untouched');

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo, ['scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . '/file1.php');
        $this->assertStringEqualsFile($folderTwoB . '/fileB.php', 'untouched');
        $this->assertFileDoesNotExist($fileOne);
        $this->assertFileDoesNotExist($folderOneA);
        $this->assertFileDoesNotExist($fileOneA);

        $Folder = new Folder($path);
        $Folder->delete();
    }

    public function testMoveWithoutRecursive(): void
    {
        // phpcs:disable
        /**
         * @var string $path
         * @var string $folderOne
         * @var string $folderOneA
         * @var string $folderTwo
         * @var string $folderTwoB
         * @var string $folderThree
         * @var string $fileOne
         * @var string $fileTwo
         * @var string $fileOneA
         * @var string $fileTwoB
         */
        extract($this->_setupFilesystem());
        // phpcs:enable

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo, ['recursive' => false]);
        $this->assertTrue($result);
        $this->assertFileExists($folderTwo . '/file1.php');
        $this->assertDirectoryDoesNotExist($folderTwo . '/folderA');
        $this->assertFileDoesNotExist($folderTwo . '/folderA/fileA.php');
    }

    /**
     * testSortByTime method
     *
     * Verify that the order using modified time is correct.
     */
    public function testSortByTime(): void
    {
        $Folder = new Folder(TMP . 'tests', true);

        $file2 = new File($Folder->pwd() . DS . 'file_2.tmp');
        $file2->create();

        sleep(1);

        $file1 = new File($Folder->pwd() . DS . 'file_1.tmp');
        $file1->create();

        $results = $Folder->find('.*', Folder::SORT_TIME);

        $this->assertSame(['file_2.tmp', 'file_1.tmp'], $results);
    }

    /**
     * Verify that the order using name is correct.
     */
    public function testSortByName(): void
    {
        $Folder = new Folder(TMP . 'tests', true);

        $fileA = new File($Folder->pwd() . DS . 'a.txt');
        $fileA->create();

        $fileC = new File($Folder->pwd() . DS . 'c.txt');
        $fileC->create();

        sleep(1);

        $fileB = new File($Folder->pwd() . DS . 'b.txt');
        $fileB->create();

        $results = $Folder->find('.*', Folder::SORT_NAME);

        $this->assertSame(['a.txt', 'b.txt', 'c.txt'], $results);
    }

    /**
     * testIsRegisteredStreamWrapper
     */
    public function testIsRegisteredStreamWrapper(): void
    {
        foreach (stream_get_wrappers() as $wrapper) {
            $this->assertTrue(Folder::isRegisteredStreamWrapper($wrapper . '://path/to/file'));
            $this->assertFalse(Folder::isRegisteredStreamWrapper('bad.' . $wrapper . '://path/to/file'));
        }

        $wrapper = 'unit.test1-';
        $this->assertFalse(Folder::isRegisteredStreamWrapper($wrapper . '://path/to/file'));
        stream_wrapper_register($wrapper, self::class);
        $this->assertTrue(Folder::isRegisteredStreamWrapper($wrapper . '://path/to/file'));
        stream_wrapper_unregister($wrapper);
    }
}
