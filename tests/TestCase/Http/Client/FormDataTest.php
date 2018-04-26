<?php
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

/**
 * Test case for FormData.
 */
class FormDataTest extends TestCase
{

    /**
     * Test getting the boundary.
     *
     * @return void
     */
    public function testBoundary()
    {
        $data = new FormData();
        $result = $data->boundary();
        $this->assertRegExp('/^[a-f0-9]{32}$/', $result);

        $result2 = $data->boundary();
        $this->assertEquals($result, $result2);
    }

    /**
     * test adding parts returns this.
     *
     * @return void
     */
    public function testAddReturnThis()
    {
        $data = new FormData();
        $return = $data->add('test', 'value');
        $this->assertSame($data, $return);
    }

    /**
     * Test adding parts that are simple.
     *
     * @return void
     */
    public function testAddSimple()
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
        $this->assertEquals($expected, $result);
    }

    /**
     * Test addMany method.
     *
     * @return void
     */
    public function testAddMany()
    {
        $data = new FormData();
        $array = [
            'key' => 'value',
            'empty' => '',
            'int' => '1',
            'float' => '2.3'
        ];
        $data->addMany($array);
        $this->assertCount(4, $data);
        $result = (string)$data;
        $expected = 'key=value&empty=&int=1&float=2.3';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding a part object.
     *
     * @return void
     */
    public function testAddPartObject()
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
            '',
        ];
        $this->assertEquals(implode("\r\n", $expected), (string)$data);
    }

    /**
     * Test adding parts that are arrays.
     *
     * @return void
     */
    public function testAddArray()
    {
        $data = new FormData();
        $data->add('Article', [
            'title' => 'first post',
            'published' => 'Y',
            'tags' => ['blog', 'cakephp']
        ]);
        $result = (string)$data;
        $expected = 'Article%5Btitle%5D=first+post&Article%5Bpublished%5D=Y&' .
            'Article%5Btags%5D%5B0%5D=blog&Article%5Btags%5D%5B1%5D=cakephp';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding a part with a file in it.
     *
     * @return void
     */
    public function testAddFile()
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
            ''
        ];
        $this->assertEquals(implode("\r\n", $expected), $result);
    }

    /**
     * Test adding a part with a filehandle.
     *
     * @return void
     */
    public function testAddFileHandle()
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
            ''
        ];
        $this->assertEquals(implode("\r\n", $expected), $result);
    }

    /**
     * Test contentType method.
     *
     * @return void
     */
    public function testContentType()
    {
        $data = new FormData();
        $data->add('key', 'value');
        $result = $data->contentType();
        $expected = 'application/x-www-form-urlencoded';
        $this->assertEquals($expected, $result);

        $file = CORE_PATH . 'VERSION.txt';
        $data = new FormData();
        $data->addFile('upload', fopen($file, 'r'));
        $boundary = $data->boundary();
        $result = $data->contentType();
        $expected = 'multipart/form-data; boundary="' . $boundary . '"';
        $this->assertEquals($expected, $result);
    }
}
