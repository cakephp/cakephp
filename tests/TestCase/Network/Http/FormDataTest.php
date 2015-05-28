<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network\Http;

use Cake\Network\Http\FormData;
use Cake\Network\Http\FormData\Part;
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
            ->add('float', 2.3);

        $this->assertCount(4, $data);
        $boundary = $data->boundary();
        $result = (string)$data;
        $expected = [
            '--' . $boundary,
            'Content-Disposition: form-data; name="test"',
            '',
            'value',
            '--' . $boundary,
            'Content-Disposition: form-data; name="empty"',
            '',
            '',
            '--' . $boundary,
            'Content-Disposition: form-data; name="int"',
            '',
            '1',
            '--' . $boundary,
            'Content-Disposition: form-data; name="float"',
            '',
            '2.3',
            '--' . $boundary . '--',
            '',
            '',
        ];
        $this->assertEquals(implode("\r\n", $expected), $result);
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
        $boundary = $data->boundary();
        $result = (string)$data;

        $expected = [
            '--' . $boundary,
            'Content-Disposition: form-data; name="Article[title]"',
            '',
            'first post',
            '--' . $boundary,
            'Content-Disposition: form-data; name="Article[published]"',
            '',
            'Y',
            '--' . $boundary,
            'Content-Disposition: form-data; name="Article[tags][0]"',
            '',
            'blog',
            '--' . $boundary,
            'Content-Disposition: form-data; name="Article[tags][1]"',
            '',
            'cakephp',
            '--' . $boundary . '--',
            '',
            '',
        ];
        $this->assertEquals(implode("\r\n", $expected), $result);
    }

    /**
     * Test adding a part with a file in it.
     *
     * @return void
     */
    public function testAddArrayWithFile()
    {
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_USER_DEPRECATED);

        $file = CORE_PATH . 'VERSION.txt';
        $contents = file_get_contents($file);

        $data = new FormData();
        $data->add('Article', [
            'title' => 'first post',
            'thumbnail' => '@' . $file
        ]);
        $boundary = $data->boundary();
        $result = (string)$data;

        $expected = [
            '--' . $boundary,
            'Content-Disposition: form-data; name="Article[title]"',
            '',
            'first post',
            '--' . $boundary,
            'Content-Disposition: form-data; name="Article[thumbnail]"; filename="VERSION.txt"',
            'Content-Type: text/plain; charset=us-ascii',
            '',
            $contents,
            '--' . $boundary . '--',
            '',
            '',
        ];
        $this->assertEquals(implode("\r\n", $expected), $result);

        error_reporting($errorLevel);
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
        $data->add('upload', fopen($file, 'r'));
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
}
