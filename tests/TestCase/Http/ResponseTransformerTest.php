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

use Cake\Http\ResponseTransformer;
use Cake\Network\Response as CakeResponse;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Response as PsrResponse;

/**
 * Test case for the response transformer.
 */
class ResponseTransformerTest extends TestCase
{
    /**
     * server used in testing
     *
     * @var array
     */
    protected $server;

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $_SERVER = $this->server;
    }

    /**
     * Test conversion getting the right class type.
     *
     * @return void
     */
    public function testToCakeCorrectType()
    {
        $psr = new PsrResponse('php://memory', 401, []);
        $result = ResponseTransformer::toCake($psr);
        $this->assertInstanceOf('Cake\Network\Response', $result);
    }

    /**
     * Test conversion getting the status code
     *
     * @return void
     */
    public function testToCakeStatusCode()
    {
        $psr = new PsrResponse('php://memory', 401, []);
        $result = ResponseTransformer::toCake($psr);
        $this->assertSame(401, $result->statusCode());

        $psr = new PsrResponse('php://memory', 200, []);
        $result = ResponseTransformer::toCake($psr);
        $this->assertSame(200, $result->statusCode());
    }

    /**
     * Test conversion getting headers.
     *
     * @return void
     */
    public function testToCakeHeaders()
    {
        $psr = new PsrResponse('php://memory', 200, ['X-testing' => 'value']);
        $result = ResponseTransformer::toCake($psr);
        $this->assertSame(['X-testing' => 'value'], $result->header());
    }

    /**
     * Test conversion getting headers.
     *
     * @return void
     */
    public function testToCakeHeaderMultiple()
    {
        $psr = new PsrResponse('php://memory', 200, ['X-testing' => ['value', 'value2']]);
        $result = ResponseTransformer::toCake($psr);
        $this->assertSame(['X-testing' => ['value', 'value2']], $result->header());
    }

    /**
     * Test conversion getting the body.
     *
     * @return void
     */
    public function testToCakeBody()
    {
        $psr = new PsrResponse('php://memory', 200, ['X-testing' => ['value', 'value2']]);
        $psr->getBody()->write('A message for you');
        $result = ResponseTransformer::toCake($psr);
        $this->assertSame('A message for you', $result->body());
    }

    /**
     * Test conversion setting the status code.
     *
     * @return void
     */
    public function testToPsrStatusCode()
    {
        $cake = new CakeResponse(['status' => 403]);
        $result = ResponseTransformer::toPsr($cake);
        $this->assertSame(403, $result->getStatusCode());
    }

    /**
     * Test conversion setting cookies
     *
     * @return void
     */
    public function testToPsrCookieSimple()
    {
        $cake = new CakeResponse(['status' => 200]);
        $cake->cookie([
            'name' => 'remember_me',
            'value' => 1
        ]);
        $result = ResponseTransformer::toPsr($cake);
        $this->assertEquals('remember_me=1; Path=/', $result->getHeader('Set-Cookie')[0]);
    }

    /**
     * Test conversion setting multiple cookies
     *
     * @return void
     */
    public function testToPsrCookieMultiple()
    {
        $cake = new CakeResponse(['status' => 200]);
        $cake->cookie([
            'name' => 'remember_me',
            'value' => 1
        ]);
        $cake->cookie([
            'name' => 'forever',
            'value' => 2
        ]);
        $result = ResponseTransformer::toPsr($cake);
        $this->assertEquals('remember_me=1; Path=/', $result->getHeader('Set-Cookie')[0]);
        $this->assertEquals('forever=2; Path=/', $result->getHeader('Set-Cookie')[1]);
    }

    /**
     * Test conversion setting cookie attributes
     *
     * @return void
     */
    public function testToPsrCookieAttributes()
    {
        $cake = new CakeResponse(['status' => 200]);
        $cake->cookie([
            'name' => 'remember me',
            'value' => '1 1',
            'path' => '/some/path',
            'domain' => 'example.com',
            'expire' => strtotime('2021-01-13 12:30:40'),
            'secure' => true,
            'httpOnly' => true,
        ]);
        $result = ResponseTransformer::toPsr($cake);
        $this->assertEquals(
            'remember+me=1+1; Expires=Wed, 13 Jan 2021 12:30:40 GMT; Path=/some/path; Domain=example.com; HttpOnly; Secure',
            $result->getHeader('Set-Cookie')[0],
            'Cookie attributes should exist, and name/value should be encoded'
        );
    }

    /**
     * Test conversion setting the content-type.
     *
     * @return void
     */
    public function testToPsrContentType()
    {
        $cake = new CakeResponse();
        $cake->type('js');
        $result = ResponseTransformer::toPsr($cake);
        $this->assertSame('application/javascript', $result->getHeaderLine('Content-Type'));
    }

    /**
     * Test conversion setting headers.
     *
     * @return void
     */
    public function testToPsrHeaders()
    {
        $cake = new CakeResponse(['status' => 403]);
        $cake->header([
            'X-testing' => ['one', 'two'],
            'Location' => 'http://example.com/testing'
        ]);
        $result = ResponseTransformer::toPsr($cake);
        $expected = [
            'X-testing' => ['one', 'two'],
            'Location' => ['http://example.com/testing'],
            'Content-Type' => ['text/html'],
        ];
        $this->assertSame($expected, $result->getHeaders());
    }

    /**
     * Test conversion setting a string body.
     *
     * @return void
     */
    public function testToPsrBodyString()
    {
        $cake = new CakeResponse(['status' => 403, 'body' => 'A response for you']);
        $result = ResponseTransformer::toPsr($cake);
        $this->assertSame($cake->body(), '' . $result->getBody());
    }

    /**
     * Test conversion setting a callable body.
     *
     * @return void
     */
    public function testToPsrBodyCallable()
    {
        $cake = new CakeResponse(['status' => 200]);
        $cake->body(function () {
            return 'callback response';
        });
        $result = ResponseTransformer::toPsr($cake);
        $this->assertSame('callback response', '' . $result->getBody());
    }

    /**
     * Test conversion setting a file body.
     *
     * @return void
     */
    public function testToPsrBodyFileResponse()
    {
        $cake = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_clearBuffer'])
            ->getMock();
        $cake->file(__FILE__, ['name' => 'some-file.php', 'download' => true]);

        $result = ResponseTransformer::toPsr($cake);
        $this->assertEquals(
            'attachment; filename="some-file.php"',
            $result->getHeaderLine('Content-Disposition')
        );
        $this->assertEquals(
            'binary',
            $result->getHeaderLine('Content-Transfer-Encoding')
        );
        $this->assertEquals(
            'bytes',
            $result->getHeaderLine('Accept-Ranges')
        );
        $this->assertContains('<?php', '' . $result->getBody());
    }

    /**
     * Test conversion setting a file body with range headers
     *
     * @return void
     */
    public function testToPsrBodyFileResponseFileRange()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=10-20';
        $cake = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_clearBuffer'])
            ->getMock();
        $path = TEST_APP . 'webroot/css/cake.generic.css';
        $cake->file($path, ['name' => 'test-asset.css', 'download' => true]);

        $result = ResponseTransformer::toPsr($cake);
        $this->assertEquals(
            'bytes 10-20/15640',
            $result->getHeaderLine('Content-Range'),
            'Content-Range header missing'
        );
    }
}
