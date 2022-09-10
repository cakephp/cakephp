<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Client;

use Cake\Http\Client\FormData;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * Test case for FormData.
 */
class FormDataTest extends TestCase
{
    /**
     * Test getting the boundary.
     */
    public function testBoundary(): void
    {
        $data = new FormData();
        $result = $data->boundary();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result);

        $result2 = $data->boundary();
        $this->assertSame($result, $result2);
    }

    /**
     * test adding parts returns this.
     */
    public function testAddReturnThis(): void
    {
        $data = new FormData();
        $return = $data->add('test', 'value');
        $this->assertSame($data, $return);
    }

    /**
     * Test adding parts that are simple.
     */
    public function testAddSimple(): void
    {
        $data = new FormData();
        $data->add('test', 'value')
            ->add('empty', '')
            ->add('int', 1)
            ->add('float', 2.3)
            ->add('password', '@secret');

        $this->assertCount(5, $data);
        $boundary = $data->boundary();
        $result = (string)$data;
        $expected = 'test=value&empty=&int=1&float=2.3&password=%40secret';
        $this->assertSame($expected, $result);
    }

    /**
     * Test addMany method.
     */
    public function testAddMany(): void
    {
        $data = new FormData();
        $array = [
            'key' => 'value',
            'empty' => '',
            'int' => '1',
            'float' => '2.3',
        ];
        $data->addMany($array);
        $this->assertCount(4, $data);
        $result = (string)$data;
        $expected = 'key=value&empty=&int=1&float=2.3';
        $this->assertSame($expected, $result);
    }

    /**
     * Test adding a part object.
     */
    public function testAddPartObject(): void
    {
        $data = new FormData();
        $boundary = $data->boundary();

        $part = $data->newPart('test', 'value');
        $part->contentId('abc123');
        $data->add($part);

        $this->assertTrue($data->isMultipart());
        $this->assertFalse($data->hasFile());
        $this->assertCount(1, $data, 'Should have 1 part');
        $expected = [
            '--' . $boundary,
            'Content-Disposition: form-data; name="test"',
            'Content-ID: <abc123>',
            '',
            'value',
            '--' . $boundary . '--',
            '',
        ];
        $this->assertSame(implode("\r\n", $expected), (string)$data);
    }

    /**
     * Test adding parts that are arrays.
     */
    public function testAddArray(): void
    {
        $data = new FormData();
        $data->add('Article', [
            'title' => 'first post',
            'published' => 'Y',
            'tags' => ['blog', 'cakephp'],
        ]);
        $result = (string)$data;
        $expected = 'Article%5Btitle%5D=first+post&Article%5Bpublished%5D=Y&' .
            'Article%5Btags%5D%5B0%5D=blog&Article%5Btags%5D%5B1%5D=cakephp';
        $this->assertSame($expected, $result);
    }

    /**
     * Test adding a part with a file in it.
     */
    public function testAddFile(): void
    {
        $file = CORE_PATH . 'VERSION.txt';
        $contents = file_get_contents($file);

        $data = new FormData();
        $data->addFile('upload', fopen($file, 'r'));
        $boundary = $data->boundary();
        $result = (string)$data;

        $expected = [
            '--' . $boundary,
            'Content-Disposition: form-data; name="upload"; filename="VERSION.txt"',
            'Content-Type: text/plain; charset=us-ascii',
            '',
            $contents,
            '--' . $boundary . '--',
            '',
        ];
        $this->assertSame(implode("\r\n", $expected), $result);
    }

    /**
     * Test adding a part with a filehandle.
     */
    public function testAddFileHandle(): void
    {
        $file = CORE_PATH . 'VERSION.txt';
        $fh = fopen($file, 'r');

        $data = new FormData();
        $data->add('upload', $fh);
        $boundary = $data->boundary();
        $result = (string)$data;

        rewind($fh);
        $contents = stream_get_contents($fh);

        $expected = [
            '--' . $boundary,
            'Content-Disposition: form-data; name="upload"; filename="VERSION.txt"',
            'Content-Type: text/plain; charset=us-ascii',
            '',
            $contents,
            '--' . $boundary . '--',
            '',
        ];
        $this->assertSame(implode("\r\n", $expected), $result);
    }

    /**
     * Test adding a part with a UploadedFileInterface instance.
     */
    public function testAddFileUploadedFile(): void
    {
        $file = new UploadedFile(
            CORE_PATH . 'VERSION.txt',
            filesize(CORE_PATH . 'VERSION.txt'),
            0,
            'VERSION.txt',
            'text/plain'
        );

        $data = new FormData();
        $data->add('upload', $file);
        $boundary = $data->boundary();
        $result = (string)$data;

        $expected = [
            '--' . $boundary,
            'Content-Disposition: form-data; name="upload"; filename="VERSION.txt"',
            'Content-Type: text/plain',
            '',
            (string)$file->getStream(),
            '--' . $boundary . '--',
            '',
        ];
        $this->assertSame(implode("\r\n", $expected), $result);
    }

    /**
     * Test contentType method.
     */
    public function testContentType(): void
    {
        $data = new FormData();
        $data->add('key', 'value');
        $result = $data->contentType();
        $expected = 'application/x-www-form-urlencoded';
        $this->assertSame($expected, $result);

        $file = CORE_PATH . 'VERSION.txt';
        $data = new FormData();
        $data->addFile('upload', fopen($file, 'r'));
        $boundary = $data->boundary();
        $result = $data->contentType();
        $expected = 'multipart/form-data; boundary=' . $boundary;
        $this->assertSame($expected, $result);
    }
}
