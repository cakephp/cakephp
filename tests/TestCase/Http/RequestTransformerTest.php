<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Core\Configure;
use Cake\Http\RequestTransformer;
use Cake\Http\ServerRequestFactory;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Stream;

/**
 * Test for RequestTransformer
 */
class RequestTransformerTest extends TestCase
{
    /**
     * Test transforming GET params.
     *
     * @return void
     */
    public function testToCakeGetParams()
    {
        $psr = ServerRequestFactory::fromGlobals(null, ['a' => 'b', 'c' => ['d' => 'e']]);
        $cake = RequestTransformer::toCake($psr);
        $this->assertEquals('b', $cake->query('a'));
        $this->assertEquals(['d' => 'e'], $cake->query('c'));
        $this->assertEmpty($cake->data);
        $this->assertEmpty($cake->cookies);
    }

    /**
     * Test transforming POST params.
     *
     * @return void
     */
    public function testToCakePostParams()
    {
        $psr = ServerRequestFactory::fromGlobals(null, null, ['title' => 'first post', 'some' => 'data']);
        $cake = RequestTransformer::toCake($psr);
        $this->assertEquals('first post', $cake->data('title'));
        $this->assertEquals('data', $cake->data('some'));
        $this->assertEmpty($cake->query);
        $this->assertEmpty($cake->cookies);
    }

    /**
     * Test transforming COOKIE params.
     *
     * @return void
     */
    public function testToCakeCookies()
    {
        $psr = ServerRequestFactory::fromGlobals(null, null, null, ['gtm' => 'watchingyou']);
        $cake = RequestTransformer::toCake($psr);
        $this->assertEmpty($cake->query);
        $this->assertEmpty($cake->data);
        $this->assertEquals('watchingyou', $cake->cookie('gtm'));
    }

    /**
     * Test transforming header and server params.
     *
     * @return void
     */
    public function testToCakeHeadersAndEnvironment()
    {
        $server = [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
            'REQUEST_METHOD' => 'PATCH',
            'HTTP_ACCEPT' => 'application/json',
            'SERVER_PROTOCOL' => '1.1',
            'SERVER_PORT' => 443,
        ];
        $psr = ServerRequestFactory::fromGlobals($server);
        $psr = $psr->withHeader('Api-Token', 'abc123')
            ->withAddedHeader('X-thing', 'one')
            ->withAddedHeader('X-thing', 'two');

        $cake = RequestTransformer::toCake($psr);
        $this->assertEmpty($cake->query);
        $this->assertEmpty($cake->data);
        $this->assertEmpty($cake->cookie);

        $this->assertSame('application/json', $cake->header('accept'));
        $this->assertSame('abc123', $cake->header('Api-Token'));
        $this->assertSame('one,two', $cake->header('X-thing'));
        $this->assertSame('PATCH', $cake->method());
        $this->assertSame('https', $cake->scheme());
        $this->assertSame(443, $cake->port());
        $this->assertSame('example.com', $cake->host());
    }

    /**
     * Test transforming with no routing parameters
     * still has the required keys.
     *
     * @return void
     */
    public function testToCakeParamsEmpty()
    {
        $psr = ServerRequestFactory::fromGlobals();
        $cake = RequestTransformer::toCake($psr);

        $this->assertArrayHasKey('controller', $cake->params);
        $this->assertArrayHasKey('action', $cake->params);
        $this->assertArrayHasKey('plugin', $cake->params);
        $this->assertArrayHasKey('_ext', $cake->params);
        $this->assertArrayHasKey('pass', $cake->params);
    }

    /**
     * Test transforming with non-empty params.
     *
     * @return void
     */
    public function testToCakeParamsPopulated()
    {
        $psr = ServerRequestFactory::fromGlobals();
        $psr = $psr->withAttribute('params', ['controller' => 'Articles', 'action' => 'index']);
        $cake = RequestTransformer::toCake($psr);

        $this->assertEmpty($cake->query);
        $this->assertEmpty($cake->data);
        $this->assertEmpty($cake->cookie);

        $this->assertSame('Articles', $cake->param('controller'));
        $this->assertSame('index', $cake->param('action'));
        $this->assertArrayHasKey('plugin', $cake->params);
        $this->assertArrayHasKey('_ext', $cake->params);
        $this->assertArrayHasKey('pass', $cake->params);
    }

    /**
     * Test transforming uploaded files
     *
     * @return void
     */
    public function testToCakeUploadedFiles()
    {
        $files = [
            'no_file' => [
                'name' => ['file' => ''],
                'type' => ['file' => ''],
                'tmp_name' => ['file' => ''],
                'error' => ['file' => UPLOAD_ERR_NO_FILE],
                'size' => ['file' => 0]
            ],
            'image_main' => [
                'name' => ['file' => 'born on.txt'],
                'type' => ['file' => 'text/plain'],
                'tmp_name' => ['file' => __FILE__],
                'error' => ['file' => 0],
                'size' => ['file' => 17178]
            ],
            0 => [
                'name' => ['image' => 'scratch.text'],
                'type' => ['image' => 'text/plain'],
                'tmp_name' => ['image' => __FILE__],
                'error' => ['image' => 0],
                'size' => ['image' => 1490]
            ],
            'pictures' => [
                'name' => [
                    0 => ['file' => 'a-file.png'],
                    1 => ['file' => 'a-moose.png']
                ],
                'type' => [
                    0 => ['file' => 'image/png'],
                    1 => ['file' => 'image/jpg']
                ],
                'tmp_name' => [
                    0 => ['file' => __FILE__],
                    1 => ['file' => __FILE__]
                ],
                'error' => [
                    0 => ['file' => 0],
                    1 => ['file' => 0]
                ],
                'size' => [
                    0 => ['file' => 17188],
                    1 => ['file' => 2010]
                ],
            ]
        ];
        $post = [
            'pictures' => [
                0 => ['name' => 'A cat'],
                1 => ['name' => 'A moose']
            ],
            0 => [
                'name' => 'A dog'
            ]
        ];
        $psr = ServerRequestFactory::fromGlobals(null, null, $post, null, $files);
        $request = RequestTransformer::toCake($psr);
        $expected = [
            'image_main' => [
                'file' => [
                    'name' => 'born on.txt',
                    'type' => 'text/plain',
                    'tmp_name' => __FILE__,
                    'error' => 0,
                    'size' => 17178,
                ]
            ],
            'no_file' => [
                'file' => [
                    'name' => '',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                    'size' => 0,
                ]
            ],
            'pictures' => [
                0 => [
                    'name' => 'A cat',
                    'file' => [
                        'name' => 'a-file.png',
                        'type' => 'image/png',
                        'tmp_name' => __FILE__,
                        'error' => 0,
                        'size' => 17188,
                    ]
                ],
                1 => [
                    'name' => 'A moose',
                    'file' => [
                        'name' => 'a-moose.png',
                        'type' => 'image/jpg',
                        'tmp_name' => __FILE__,
                        'error' => 0,
                        'size' => 2010,
                    ]
                ]
            ],
            0 => [
                'name' => 'A dog',
                'image' => [
                    'name' => 'scratch.text',
                    'type' => 'text/plain',
                    'tmp_name' => __FILE__,
                    'error' => 0,
                    'size' => 1490
                ]
            ]
        ];
        $this->assertEquals($expected, $request->data);
    }

    /**
     * Test that the transformed request sets the session path
     * as expected.
     *
     * @return void
     */
    public function testToCakeBaseSessionPath()
    {
        Configure::write('App.baseUrl', false);

        $server = [
            'DOCUMENT_ROOT' => '/cake/repo/branches',
            'PHP_SELF' => '/thisapp/webroot/index.php',
            'REQUEST_URI' => '/posts/view/1',
        ];
        $psr = ServerRequestFactory::fromGlobals($server);
        $cake = RequestTransformer::toCake($psr);

        $this->assertEquals('/thisapp/', ini_get('session.cookie_path'));
    }

    /**
     * Test transforming session objects
     *
     * @return void
     */
    public function testToCakeSession()
    {
        $psr = ServerRequestFactory::fromGlobals();
        $session = new Session(['defaults' => 'php']);
        $session->write('test', 'value');
        $psr = $psr->withAttribute('session', $session);
        $cake = RequestTransformer::toCake($psr);

        $this->assertSame($session, $cake->session());
    }

    /**
     * Test transforming request bodies
     *
     * @return void
     */
    public function testToCakeRequestBody()
    {
        $psr = ServerRequestFactory::fromGlobals();
        $stream = new Stream('php://memory', 'rw');
        $stream->write('{"hello":"world"}');
        $stream->rewind();
        $psr = $psr->withBody($stream);

        $cake = RequestTransformer::toCake($psr);
        $this->assertSame(['hello' => 'world'], $cake->input('json_decode', true));
    }
}
