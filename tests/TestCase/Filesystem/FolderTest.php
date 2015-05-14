<?php
/**
 * FolderTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        clearstatcache();
    }

    /**
     * Remove TMP/tests directory to its original state.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $cleaner = function ($dir) use (&$cleaner) {
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
     *
     * @return void
     */
    public function testBasic()
    {
        $path = __DIR__;
        $Folder = new Folder($path);

        $result = $Folder->pwd();
        $this->assertEquals($path, $result);

        $result = Folder::addPathElement($path, 'test');
        $expected = $path . DS . 'test';
        $this->assertEquals($expected, $result);

        $result = $Folder->cd(ROOT);
        $expected = ROOT;
        $this->assertEquals($expected, $result);

        $result = $Folder->cd(ROOT . DS . 'non-existent');
        $this->assertFalse($result);
    }

    /**
     * testInPath method
     *
     * @return void
     */
    public function testInPath()
    {
        $path = dirname(__DIR__);
        $inside = dirname($path) . DS;

        $Folder = new Folder($path);

        $result = $Folder->pwd();
        $this->assertEquals($path, $result);

        $result = Folder::isSlashTerm($inside);
        $this->assertTrue($result);

        $result = $Folder->realpath('tests' . DS);
        $this->assertEquals($path . DS . 'tests' . DS, $result);

        $result = $Folder->inPath('tests' . DS);
        $this->assertTrue($result);

        $result = $Folder->inPath(DS . 'non-existing' . $inside);
        $this->assertFalse($result);

        $result = $Folder->inPath($path . DS . 'Model', true);
        $this->assertTrue($result);
    }

    /**
     * test creation of single and multiple paths.
     *
     * @return void
     */
    public function testCreation()
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
     *
     * @return void
     */
    public function testCreateWithTrailingDs()
    {
        $Folder = new Folder(TMP . 'tests');
        $path = TMP . 'tests' . DS . 'trailing' . DS . 'dir' . DS;
        $result = $Folder->create($path);
        $this->assertTrue($result);

        $this->assertTrue(is_dir($path), 'Folder was not made');

        $Folder = new Folder(TMP . 'tests' . DS . 'trailing');
        $this->assertTrue($Folder->delete());
    }

    /**
     * Test that relative paths to create() are added to cwd.
     *
     * @return void
     */
    public function testCreateRelative()
    {
        $folder = new Folder(TMP);
        $path = TMP . 'tests' . DS . 'relative-test';
        $result = $folder->create('tests' . DS . 'relative-test');
        $this->assertTrue($result, 'should create');

        $this->assertTrue(is_dir($path), 'Folder was not made');
        $folder = new Folder($path);
        $folder->delete();
    }

    /**
     * test recursive directory create failure.
     *
     * @return void
     */
    public function testRecursiveCreateFailure()
    {
        $this->skipIf(DS === '\\', 'Cant perform operations using permissions on windows.');

        $path = TMP . 'tests/one';
        mkdir($path, 0777, true);
        chmod($path, '0444');

        try {
            $Folder = new Folder($path);
            $result = $Folder->create($path . DS . 'two/three');
            $this->assertFalse($result);
        } catch (\PHPUnit_Framework_Error $e) {
            $this->assertTrue(true);
        }

        chmod($path, '0777');
        rmdir($path);
    }

    /**
     * testOperations method
     *
     * @return void
     */
    public function testOperations()
    {
        $path = CAKE . 'Template';
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
        $this->assertEquals($expected, $result[0]);

        $new = TMP . 'tests' . DS . 'test_folder_new';
        $result = $Folder->create($new);
        $this->assertTrue($result);

        $result = $Folder->cd($new);
        $this->assertTrue((bool)$result);

        $result = $Folder->delete();
        $this->assertTrue($result);

        $Folder = new Folder('non-existent');
        $result = $Folder->pwd();
        $this->assertNull($result);
    }

    /**
     * testChmod method
     *
     * @return void
     */
    public function testChmod()
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
        $this->assertEquals('0755', $perms);

        $this->assertTrue($Folder->chmod($new, 0744, true, ['skip_me.php', 'test2']));

        $perms = substr(sprintf('%o', fileperms($new . DS . 'test2')), -4);
        $this->assertEquals('0755', $perms);

        $perms = substr(sprintf('%o', fileperms($new . DS . 'test1')), -4);
        $this->assertEquals('0744', $perms);
    }

    /**
     * testRealPathForWebroot method
     *
     * @return void
     */
    public function testRealPathForWebroot()
    {
        $Folder = new Folder('files' . DS);
        $this->assertEquals(realpath('files' . DS), $Folder->path);
    }

    /**
     * testZeroAsDirectory method
     *
     * @return void
     */
    public function testZeroAsDirectory()
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
     *
     * @return void
     */
    public function testAddPathElement()
    {
        $expected = DS . 'some' . DS . 'dir' . DS . 'another_path';

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', 'another_path');
        $this->assertEquals($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir' . DS, 'another_path');
        $this->assertEquals($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', ['another_path']);
        $this->assertEquals($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir' . DS, ['another_path']);
        $this->assertEquals($expected, $result);

        $expected = DS . 'some' . DS . 'dir' . DS . 'another_path' . DS . 'and' . DS . 'another';

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', ['another_path', 'and', 'another']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderRead method
     *
     * @return void
     */
    public function testFolderRead()
    {
        $Folder = new Folder(CAKE);

        $result = $Folder->read(true, true);
        $this->assertContains('Core', $result[0]);
        $this->assertContains('Cache', $result[0]);

        $Folder = new Folder(TMP . 'non-existent');
        $expected = [[], []];
        $result = $Folder->read(true, true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderReadWithHiddenFiles method
     *
     * @return void
     */
    public function testFolderReadWithHiddenFiles()
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
                'some_folder'
            ],
            [
                '.hidden.txt',
                'not_hidden.txt'
            ],
        ];
        $result = $Folder->read(true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderTree method
     *
     * @return void
     */
    public function testFolderTree()
    {
        $Folder = new Folder();
        $expected = [
            [
                CORE_PATH . 'config',
            ],
            [
                CORE_PATH . 'config' . DS . 'config.php',
            ]
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
     *
     * @return void
     */
    public function testFolderTreeWithHiddenFiles()
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
     *
     * @return void
     */
    public function testWindowsPath()
    {
        $this->assertFalse(Folder::isWindowsPath('0:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('C:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('d:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('\\\\vmware-host\\Shared Folders\\file'));
    }

    /**
     * testIsAbsolute method
     *
     * @return void
     */
    public function testIsAbsolute()
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
     *
     * @return void
     */
    public function testIsSlashTerm()
    {
        $this->assertFalse(Folder::isSlashTerm('cake'));

        $this->assertTrue(Folder::isSlashTerm('C:\\cake\\'));
        $this->assertTrue(Folder::isSlashTerm('/usr/local/'));
    }

    /**
     * testStatic method
     *
     * @return void
     */
    public function testSlashTerm()
    {
        $result = Folder::slashTerm('/path/to/file');
        $this->assertEquals('/path/to/file/', $result);
    }

    /**
     * testNormalizePath method
     *
     * @return void
     */
    public function testNormalizePath()
    {
        $path = '/path/to/file';
        $result = Folder::normalizePath($path);
        $this->assertEquals('/', $result);

        $path = '\\path\\\to\\\file';
        $result = Folder::normalizePath($path);
        $this->assertEquals('/', $result);

        $path = 'C:\\path\\to\\file';
        $result = Folder::normalizePath($path);
        $this->assertEquals('\\', $result);
    }

    /**
     * correctSlashFor method
     *
     * @return void
     */
    public function testCorrectSlashFor()
    {
        $path = '/path/to/file';
        $result = Folder::correctSlashFor($path);
        $this->assertEquals('/', $result);

        $path = '\\path\\to\\file';
        $result = Folder::correctSlashFor($path);
        $this->assertEquals('/', $result);

        $path = 'C:\\path\to\\file';
        $result = Folder::correctSlashFor($path);
        $this->assertEquals('\\', $result);
    }

    /**
     * testInCakePath method
     *
     * @return void
     */
    public function testInCakePath()
    {
        $Folder = new Folder();
        $Folder->cd(ROOT);
        $path = 'C:\\path\\to\\file';
        $result = $Folder->inCakePath($path);
        $this->assertFalse($result);

        $path = ROOT;
        $Folder->cd(ROOT);
        $result = $Folder->inCakePath($path);
        $this->assertFalse($result);

        $path = DS . 'config';
        $Folder->cd(ROOT . DS . 'config');
        $result = $Folder->inCakePath($path);
        $this->assertTrue($result);
    }

    /**
     * testFind method
     *
     * @return void
     */
    public function testFind()
    {
        $Folder = new Folder();
        $Folder->cd(CORE_PATH . 'config');
        $result = $Folder->find();
        $expected = ['config.php'];
        $this->assertSame(array_diff($expected, $result), []);
        $this->assertSame(array_diff($expected, $result), []);

        $result = $Folder->find('.*', true);
        $expected = ['bootstrap.php', 'cacert.pem', 'config.php'];
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
        $File = new File($Folder->pwd() . DS . 'paths.php', true);
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
     *
     * @return void
     */
    public function testFindRecursive()
    {
        $Folder = new Folder(CORE_PATH);
        $result = $Folder->findRecursive('(config|paths)\.php');
        $expected = [
            CORE_PATH . 'config' . DS . 'config.php'
        ];
        $this->assertSame([], array_diff($expected, $result));
        $this->assertSame([], array_diff($expected, $result));

        $result = $Folder->findRecursive('(config|woot)\.php', true);
        $expected = [
            CORE_PATH . 'config' . DS . 'config.php'
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
            $path . 'testme' . DS . 'paths.php'
        ];
        $this->assertSame(sort($expected), sort($result));

        $result = $Folder->findRecursive('(paths|my)\.php', true);
        $expected = [
            $path . 'testme' . DS . 'my.php',
            $path . 'testme' . DS . 'paths.php'
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testConstructWithNonExistentPath method
     *
     * @return void
     */
    public function testConstructWithNonExistentPath()
    {
        $path = TMP . 'tests' . DS;
        $Folder = new Folder($path . 'config_non_existent', true);
        $this->assertTrue(is_dir($path . 'config_non_existent'));
        $Folder->cd($path);
    }

    /**
     * testDirSize method
     *
     * @return void
     */
    public function testDirSize()
    {
        $path = TMP . 'tests' . DS;
        $Folder = new Folder($path . 'config_non_existent', true);
        $this->assertEquals(0, $Folder->dirSize());

        $File = new File($Folder->pwd() . DS . 'my.php', true, 0777);
        $File->create();
        $File->write('something here');
        $File->close();
        $this->assertEquals(14, $Folder->dirSize());
    }

    /**
     * test that errors and messages can be resetted
     *
     * @return void
     */
    public function testReset()
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
     *
     * @return void
     */
    public function testDelete()
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
            $path . ' removed'
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
     *
     * @return void
     */
    public function testCopy()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * testCopyWithMerge method
     *
     * Verify that subdirectories existing in both destination and source directory
     * are merged recursively.
     *
     * @return void
     */
    public function testCopyWithMerge()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::MERGE]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));
    }

    /**
     * testCopyWithSkip method
     *
     * Verify that directories and files are copied recursively
     * even if the destination directory already exists.
     * Subdirectories existing in both destination and source directory
     * are skipped and not merged or overwritten.
     *
     * @return void
     */
    public function testCopyWithSkip()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        $Folder = new Folder($folderOne);
        $result = $Folder->copy(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderTwo, true);
        new Folder($folderTwo . DS . 'folderB', true);
        file_put_contents($folderTwo . DS . 'file2.php', 'touched');
        file_put_contents($folderTwo . DS . 'folderB' . DS . 'fileB.php', 'untouched');

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertEquals('touched', file_get_contents($folderThree . DS . 'file2.php'));
        $this->assertEquals('untouched', file_get_contents($folderThree . DS . 'folderB' . DS . 'fileB.php'));
    }

    /**
     * Test that SKIP mode skips files too.
     *
     * @return void
     */
    public function testCopyWithSkipFileSkipped()
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
        $result = $Folder->copy(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertEquals('Folder Two File', file_get_contents($folderTwo . DS . 'fileA.txt'));
    }

    /**
     * testCopyWithOverwrite
     *
     * Verify that subdirectories existing in both destination and source directory
     * are overwritten/replaced recursively.
     *
     * @return void
     */
    public function testCopyWithOverwrite()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::OVERWRITE]);

        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::OVERWRITE]);
        $this->assertTrue($result);

        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderOne);
        unlink($fileOneA);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::OVERWRITE]);
        $this->assertTrue($result);

        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertTrue(!file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));
    }

    /**
     * testCopyWithoutResursive
     *
     * Verify that only the files exist in the target directory.
     *
     * @return void
     */
    public function testCopyWithoutRecursive()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy(['to' => $folderThree, 'recursive' => false]);

        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertFalse(is_dir($folderThree . DS . 'folderA'));
        $this->assertFalse(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
    }

    /**
     * Setup filesystem for copy tests
     * $path: folder_test/
     * - folder1/file1.php
     * - folder1/folderA/fileA.php
     * - folder2/file2.php
     * - folder2/folderB/fileB.php
     * - folder3/
     *
     * @return array Filenames to extract in the test methods
     */
    protected function _setupFilesystem()
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
     *
     * @return void
     */
    public function testMove()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . '/file1.php'));
        $this->assertTrue(is_dir($folderTwo . '/folderB'));
        $this->assertTrue(file_exists($folderTwo . '/folderB/fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertTrue(file_exists($folderTwo . '/folderA'));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        touch($fileOne);
        touch($fileOneA);

        $Folder = new Folder($folderOne);
        $result = $Folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . '/file1.php'));
        $this->assertTrue(is_dir($folderTwo . '/folderA'));
        $this->assertTrue(file_exists($folderTwo . '/folderA/fileA.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

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
        $this->assertTrue(file_exists($folderTwo . '/file1.php'));
        $this->assertEquals('', file_get_contents($folderTwoB . '/fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

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
     *
     * @return void
     */
    public function testMoveWithSkip()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->move(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . '/file1.php'));
        $this->assertTrue(is_dir($folderTwo . '/folderB'));
        $this->assertTrue(file_exists($folderTwoB . '/fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        touch($fileOne);
        touch($fileOneA);

        $Folder = new Folder($folderOne);
        $result = $Folder->move(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . '/file1.php'));
        $this->assertTrue(is_dir($folderTwo . '/folderA'));
        $this->assertTrue(file_exists($folderTwo . '/folderA/fileA.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

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
        $result = $Folder->move(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . '/file1.php'));
        $this->assertEquals('untouched', file_get_contents($folderTwoB . '/fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $Folder = new Folder($path);
        $Folder->delete();
    }
    
    public function testMoveWithoutRecursive()
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->move(['to' => $folderTwo, 'recursive' => false]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . '/file1.php'));
        $this->assertFalse(is_dir($folderTwo . '/folderA'));
        $this->assertFalse(file_exists($folderTwo . '/folderA/fileA.php'));
    }
}
