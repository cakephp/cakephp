<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
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
use SplFileInfo;

/**
 * FileTest class
 */
class FileTest extends TestCase
{
    /**
     * File property
     *
     * @var File
     */
    protected $File;

    /**
     * setup the test case
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $file = __FILE__;
        $this->File = new File($file);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->File->close();
        unset($this->File);

        $Folder = new Folder();
        $Folder->delete(TMP . 'tests/permissions');
    }

    /**
     * testBasic method
     *
     * @return void
     */
    public function testBasic()
    {
        $file = CORE_PATH . DS . 'LICENSE';

        $this->File = new File($file, false);

        $result = $this->File->name;
        $expecting = basename($file);
        $this->assertSame($expecting, $result);

        $result = $this->File->info();
        $expecting = [
            'dirname' => dirname($file),
            'basename' => basename($file),
            'filename' => 'LICENSE',
            'filesize' => filesize($file),
            'mime' => 'text/plain',
        ];
        if (
            !function_exists('finfo_open') &&
            (!function_exists('mime_content_type') ||
            function_exists('mime_content_type') &&
            mime_content_type($this->File->pwd()) === false)
        ) {
            $expecting['mime'] = false;
        }
        $this->assertEquals($expecting, $result);

        $result = $this->File->ext();
        $this->assertFalse($result);

        $result = $this->File->name();
        $expecting = 'LICENSE';
        $this->assertSame($expecting, $result);

        $result = $this->File->md5();
        $expecting = md5_file($file);
        $this->assertEquals($expecting, $result);

        $result = $this->File->md5(true);
        $expecting = md5_file($file);
        $this->assertEquals($expecting, $result);

        $result = $this->File->size();
        $expecting = filesize($file);
        $this->assertEquals($expecting, $result);

        $result = $this->File->owner();
        $expecting = fileowner($file);
        $this->assertEquals($expecting, $result);

        $result = $this->File->group();
        $expecting = filegroup($file);
        $this->assertEquals($expecting, $result);

        $result = $this->File->Folder();
        $this->assertInstanceOf('Cake\Filesystem\Folder', $result);
    }

    /**
     * testUtf8Filenames
     *
     * @link https://github.com/cakephp/cakephp/issues/11749
     * @return void
     */
    public function testUtf8Filenames()
    {
        $File = new File(TMP . 'tests/permissions/نام فارسی.php', true);
        $this->assertSame('نام فارسی', $File->name());
        $this->assertTrue($File->exists());
        $this->assertTrue($File->readable());
    }

    /**
     * Test _basename method
     *
     * @dataProvider baseNameValueProvider
     * @return void
     */
    public function testBasename($path, $suffix, $isRoot)
    {
        if (!$isRoot) {
            $path = TMP . 'tests/permissions' . $path;
        }
        $File = new File($path, false);

        // some paths are directories like '/etc/sudoers.d'
        if (!is_dir($path)) {
            // Check the name after running __construct()
            $result = $File->name;
            $expecting = basename($path);
            $this->assertSame($expecting, $result);
        }

        // Check name()
        $splInfo = new SplFileInfo($path);
        $File->name = ltrim($splInfo->getFilename(), '/\\');
        $File->path = $path;

        if ($suffix === null) {
            $File->info(); // to set and unset 'extension' in bellow
            unset($File->info['extension']);
            $this->assertEquals(basename($path), $File->name());
        } else {
            $File->info['extension'] = $suffix;
            $this->assertSame(basename($path, '.' . $suffix), $File->name());
        }
    }

    /**
     * Data provider for testBasename().
     *
     * @return array
     */
    public function baseNameValueProvider()
    {
        return [
            ['folder/نام.txt', null, false],
            ['folder/نام فارسی.txt', null, false],
            ['نام.txt', null, true],
            ['نام فارسی.txt', null, true],
            ['/نام.txt', null, true],
            ['/نام فارسی.txt', null, true],
            ['folder/نام.txt', 'txt', false],
            ['folder/نام فارسی.txt', 'txt', false],
            ['نام.txt', 'txt', true],
            ['نام فارسی.txt', 'txt', true],
            ['/نام.txt', 'txt', true],
            ['/نام فارسی.txt', 'txt', true],
            ['abcde.ab', 'abe', false],
            ['/etc/sudoers.d', null, true],
            ['/etc/.d', 'd', true],
            ['/etc/sudoers.d', 'd', true],
            ['/etc/passwd', null, true],
            ['/etc/', null, true],
            ['.', null, true],
            ['/', null, true],
        ];
    }

    /**
     * testPermission method
     *
     * @return void
     */
    public function testPermission()
    {
        $this->skipIf(DS === '\\', 'File permissions tests not supported on Windows.');

        $dir = TMP . 'tests' . DS . 'permissions' . DS;
        $old = umask();

        umask(0002);
        $file = $dir . 'permission_' . uniqid();
        $expecting = decoct(0664 & ~umask());
        $File = new File($file, true);
        $result = $File->perms();
        $this->assertSame($expecting, $result);
        $File->delete();

        umask(0022);
        $file = $dir . 'permission_' . uniqid();
        $expecting = decoct(0644 & ~umask());
        $File = new File($file, true);
        $result = $File->perms();
        $this->assertSame($expecting, $result);
        $File->delete();

        umask(0422);
        $file = $dir . 'permission_' . uniqid();
        $expecting = decoct(0244 & ~umask());
        $File = new File($file, true);
        $result = $File->perms();
        $this->assertSame($expecting, $result);
        $File->delete();

        umask(0444);
        $file = $dir . 'permission_' . uniqid();
        $expecting = decoct(0222 & ~umask());
        $File = new File($file, true);
        $result = $File->perms();
        $this->assertSame($expecting, $result);
        $File->delete();

        umask($old);
    }

    /**
     * testRead method
     *
     * @return void
     */
    public function testRead()
    {
        $file = __FILE__;
        $this->File = new File($file);

        $result = $this->File->read();
        $expecting = file_get_contents(__FILE__);
        $this->assertEquals($expecting, $result);
        $this->assertIsNotResource($this->File->handle);

        $this->File->lock = true;
        $result = $this->File->read();
        $expecting = file_get_contents(__FILE__);
        $this->assertSame(trim($expecting), $result);
        $this->File->lock = null;

        $data = $expecting;
        $expecting = substr($data, 0, 3);
        $result = $this->File->read(3);
        $this->assertSame($expecting, $result);
        $this->assertIsResource($this->File->handle);

        $expecting = substr($data, 3, 3);
        $result = $this->File->read(3);
        $this->assertSame($expecting, $result);
    }

    /**
     * testOffset method
     *
     * @return void
     */
    public function testOffset()
    {
        $this->File->close();

        $result = $this->File->offset();
        $this->assertFalse($result);

        $this->assertNull($this->File->handle);
        $success = $this->File->offset(0);
        $this->assertTrue($success);
        $this->assertIsResource($this->File->handle);

        $result = $this->File->offset();
        $expected = 0;
        $this->assertSame($expected, $result);

        $data = file_get_contents(__FILE__);
        $success = $this->File->offset(5);
        $expected = substr($data, 5, 3);
        $result = $this->File->read(3);
        $this->assertTrue($success);
        $this->assertSame($expected, $result);

        $result = $this->File->offset();
        $expected = 5 + 3;
        $this->assertSame($expected, $result);
    }

    /**
     * testOpen method
     *
     * @return void
     */
    public function testOpen()
    {
        $this->File->handle = null;

        $r = $this->File->open();
        $this->assertIsResource($this->File->handle);
        $this->assertTrue($r);

        $handle = $this->File->handle;
        $r = $this->File->open();
        $this->assertTrue($r);
        $this->assertSame($handle, $this->File->handle);
        $this->assertIsResource($this->File->handle);

        $r = $this->File->open('r', true);
        $this->assertTrue($r);
        $this->assertNotSame($handle, $this->File->handle);
        $this->assertIsResource($this->File->handle);
    }

    /**
     * testClose method
     *
     * @return void
     */
    public function testClose()
    {
        $this->File->handle = null;
        $this->assertNull($this->File->handle);
        $this->assertTrue($this->File->close());
        $this->assertNull($this->File->handle);

        $this->File->handle = fopen(__FILE__, 'r');
        $this->assertIsResource($this->File->handle);
        $this->assertTrue($this->File->close());
        $this->assertFalse(is_resource($this->File->handle));
    }

    /**
     * testCreate method
     *
     * @return void
     */
    public function testCreate()
    {
        $tmpFile = TMP . 'tests/cakephp.file.test.tmp';
        $File = new File($tmpFile, true, 0777);
        $this->assertTrue($File->exists());
    }

    /**
     * Tests the exists() method.
     *
     * @return void
     */
    public function testExists()
    {
        $tmpFile = TMP . 'tests/cakephp.file.test.tmp';
        $file = new File($tmpFile, true, 0777);
        $this->assertTrue($file->exists(), 'absolute path should exist');

        $file = new File('file://' . $tmpFile, false);
        $this->assertTrue($file->exists(), 'file:// should exist.');

        $file = new File('/something/bad', false);
        $this->assertFalse($file->exists(), 'missing file should not exist.');
    }

    /**
     * testOpeningNonExistentFileCreatesIt method
     *
     * @return void
     */
    public function testOpeningNonExistentFileCreatesIt()
    {
        $someFile = new File(TMP . 'some_file.txt', false);
        $this->assertTrue($someFile->open());
        $this->assertSame('', $someFile->read());
        $someFile->close();
        $someFile->delete();
    }

    /**
     * testPrepare method
     *
     * @return void
     */
    public function testPrepare()
    {
        $string = "some\nvery\ncool\r\nteststring here\n\n\nfor\r\r\n\n\r\n\nhere";
        if (DS === '\\') {
            $expected = "some\r\nvery\r\ncool\r\nteststring here\r\n\r\n\r\n";
            $expected .= "for\r\n\r\n\r\n\r\n\r\nhere";
        } else {
            $expected = "some\nvery\ncool\nteststring here\n\n\nfor\n\n\n\n\nhere";
        }
        $this->assertSame($expected, File::prepare($string));

        $expected = "some\r\nvery\r\ncool\r\nteststring here\r\n\r\n\r\n";
        $expected .= "for\r\n\r\n\r\n\r\n\r\nhere";
        $this->assertSame($expected, File::prepare($string, true));
    }

    /**
     * testReadable method
     *
     * @return void
     */
    public function testReadable()
    {
        $someFile = new File(TMP . 'some_file.txt', false);
        $this->assertTrue($someFile->open());
        $this->assertTrue($someFile->readable());
        $someFile->close();
        $someFile->delete();
    }

    /**
     * testWritable method
     *
     * @return void
     */
    public function testWritable()
    {
        $someFile = new File(TMP . 'some_file.txt', false);
        $this->assertTrue($someFile->open());
        $this->assertTrue($someFile->writable());
        $someFile->close();
        $someFile->delete();
    }

    /**
     * testExecutable method
     *
     * @return void
     */
    public function testExecutable()
    {
        $someFile = new File(TMP . 'some_file.txt', false);
        $this->assertTrue($someFile->open());
        $this->assertFalse($someFile->executable());
        $someFile->close();
        $someFile->delete();
    }

    /**
     * testLastAccess method
     *
     * @return void
     */
    public function testLastAccess()
    {
        $someFile = new File(TMP . 'some_file.txt', false);
        $this->assertFalse($someFile->lastAccess());
        $this->assertTrue($someFile->open());
        $this->assertWithinRange(time(), $someFile->lastAccess(), 2);
        $someFile->close();
        $someFile->delete();
    }

    /**
     * testLastChange method
     *
     * @return void
     */
    public function testLastChange()
    {
        $someFile = new File(TMP . 'some_file.txt', false);
        $this->assertFalse($someFile->lastChange());
        $this->assertTrue($someFile->open('r+'));
        $this->assertWithinRange(time(), $someFile->lastChange(), 2);

        $someFile->write('something');
        $this->assertWithinRange(time(), $someFile->lastChange(), 2);

        $someFile->close();
        $someFile->delete();
    }

    /**
     * testWrite method
     *
     * @return void
     */
    public function testWrite()
    {
        $tmpFile = $this->_getTmpFile();
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        $TmpFile = new File($tmpFile);
        $this->assertFileDoesNotExist($tmpFile);
        $this->assertNull($TmpFile->handle);

        $testData = ['CakePHP\'s', ' test suite', ' was here ...', ''];
        foreach ($testData as $data) {
            $r = $TmpFile->write($data);
            $this->assertTrue($r);
            $this->assertFileExists($tmpFile);
            $this->assertStringEqualsFile($tmpFile, $data);
            $this->assertIsResource($TmpFile->handle);
            $TmpFile->close();
        }
        unlink($tmpFile);
    }

    /**
     * testAppend method
     *
     * @return void
     */
    public function testAppend()
    {
        $tmpFile = $this->_getTmpFile();
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        $TmpFile = new File($tmpFile);
        $this->assertFileDoesNotExist($tmpFile);

        $fragments = ['CakePHP\'s', ' test suite', ' was here ...'];
        $data = null;
        $size = 0;
        foreach ($fragments as $fragment) {
            $r = $TmpFile->append($fragment);
            $this->assertTrue($r);
            $this->assertFileExists($tmpFile);
            $data = $data . $fragment;
            $this->assertStringEqualsFile($tmpFile, $data);
            $newSize = $TmpFile->size();
            $this->assertTrue($newSize > $size);
            $size = $newSize;
            $TmpFile->close();
        }

        $TmpFile->append('');
        $this->assertStringEqualsFile($tmpFile, $data);
        $TmpFile->close();
    }

    /**
     * testDelete method
     *
     * @return void
     */
    public function testDelete()
    {
        $tmpFile = $this->_getTmpFile();
        if (!file_exists($tmpFile)) {
            touch($tmpFile);
        }
        $file = new File($tmpFile);
        $this->assertFileExists($tmpFile);

        $file->read();
        $this->assertTrue($file->delete());
        $this->assertFalse($file->exists());
        $this->assertFileDoesNotExist($tmpFile);

        $TmpFile = new File('/this/does/not/exist');
        $result = $TmpFile->delete();
        $this->assertFalse($result);
    }

    /**
     * Windows has issues unlinking files if there are
     * active filehandles open.
     *
     * @return void
     */
    public function testDeleteAfterRead()
    {
        $tmpFile = $this->_getTmpFile();
        if (!file_exists($tmpFile)) {
            touch($tmpFile);
        }
        $File = new File($tmpFile);
        $File->read();
        $this->assertTrue($File->delete());
    }

    /**
     * testCopy method
     *
     * @return void
     */
    public function testCopy()
    {
        $dest = TMP . 'tests/cakephp.file.test.tmp';
        $file = __FILE__;
        $this->File = new File($file);
        $result = $this->File->copy($dest);
        $this->assertTrue($result);

        $result = $this->File->copy($dest, true);
        $this->assertTrue($result);

        $result = $this->File->copy($dest, false);
        $this->assertFalse($result);

        $this->File->close();
        unlink($dest);

        $TmpFile = new File('/this/does/not/exist');
        $result = $TmpFile->copy($dest);
        $this->assertFalse($result);

        $TmpFile->close();
    }

    /**
     * Test mime()
     *
     * @return void
     */
    public function testMime()
    {
        $this->skipIf(!function_exists('finfo_open') && !function_exists('mime_content_type'), 'Not able to read mime type');
        $path = TEST_APP . 'webroot/img/cake.power.gif';
        $file = new File($path);
        $expected = 'image/gif';
        if (function_exists('mime_content_type') && mime_content_type($file->pwd()) === false) {
            $expected = false;
        }
        $this->assertEquals($expected, $file->mime());
    }

    /**
     * getTmpFile method
     *
     * @return string
     */
    protected function _getTmpFile()
    {
        $tmpFile = TMP . 'tests/cakephp.file.test.tmp';
        if (is_writable(dirname($tmpFile)) && (!file_exists($tmpFile) || is_writable($tmpFile))) {
            return $tmpFile;
        }

        $trace = debug_backtrace();
        $caller = $trace[0]['function'];
        $shortPath = dirname($tmpFile);

        $message = sprintf('[FileTest] Skipping %s because "%s" not writeable!', $caller, $shortPath);
        $this->markTestSkipped($message);
    }

    /**
     * testReplaceText method
     *
     * @return void
     */
    public function testReplaceText()
    {
        $TestFile = new File(TEST_APP . 'vendor/welcome.php');
        $TmpFile = new File(TMP . 'tests' . DS . 'cakephp.file.test.tmp');

        // Copy the test file to the temporary location
        $TestFile->copy($TmpFile->path, true);

        // Replace the contents of the temporary file
        $result = $TmpFile->replaceText('welcome.php', 'welcome.tmp');
        $this->assertTrue($result);

        // Double check
        $expected = 'This is the welcome.tmp file in vendors directory';
        $contents = $TmpFile->read();
        $this->assertStringContainsString($expected, $contents);

        $search = ['This is the', 'welcome.php file', 'in tmp directory'];
        $replace = ['This should be a', 'welcome.tmp file', 'in the Lib directory'];

        // Replace the contents of the temporary file
        $result = $TmpFile->replaceText($search, $replace);
        $this->assertTrue($result);

        // Double check
        $expected = 'This should be a welcome.tmp file in vendors directory';
        $contents = $TmpFile->read();
        $this->assertStringContainsString($expected, $contents);

        $TmpFile->delete();
    }

    /**
     * Tests that no path is being set for passed file paths that
     * do not exist.
     *
     * @return void
     */
    public function testNoPartialPathBeingSetForNonExistentPath()
    {
        $TmpFile = new File('/non/existent/file');
        $this->assertNull($TmpFile->pwd());
        $this->assertNull($TmpFile->path);
    }
}
