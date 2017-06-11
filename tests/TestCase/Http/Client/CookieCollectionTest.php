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

use Cake\Http\Client\CookieCollection;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;

/**
 * HTTP cookies test.
 */
class CookieCollectionTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->cookies = new CookieCollection();
    }

    /**
     * test store
     *
     * @return void
     */
    public function testStore()
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1',
            'Set-Cookie: second=2; Path=/; Domain=.foo.example.com',
            'Set-Cookie: expiring=now; Expires=Wed, 09-Jun-1999 10:18:14 GMT',
        ];
        $response = new Response($headers, '');
        $result = $this->cookies->store($response, 'http://example.com/some/path');
        $this->assertNull($result);

        $result = $this->cookies->getAll();
        $this->assertCount(2, $result);
        $expected = [
            [
                'name' => 'first',
                'value' => '1',
                'path' => '/some/path',
                'domain' => 'example.com'
            ],
            [
                'name' => 'second',
                'value' => '2',
                'path' => '/',
                'domain' => '.foo.example.com'
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test store secure.
     *
     * @return void
     */
    public function testStoreSecure()
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1',
            'Set-Cookie: second=2; Secure; HttpOnly',
        ];
        $response = new Response($headers, '');
        $result = $this->cookies->store($response, 'http://example.com/some/path');
        $this->assertNull($result);

        $result = $this->cookies->getAll();
        $this->assertCount(2, $result);
        $expected = [
            [
                'name' => 'first',
                'value' => '1',
                'path' => '/some/path',
                'domain' => 'example.com'
            ],
            [
                'name' => 'second',
                'value' => '2',
                'path' => '/some/path',
                'domain' => 'example.com',
                'secure' => true,
                'httponly' => true,
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test storing an expired cookie clears existing ones too.
     *
     * @return void
     */
    public function testStoreExpiring()
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1',
            'Set-Cookie: second=2; Path=/',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'http://example.com/some/path');

        $result = $this->cookies->getAll();
        $this->assertCount(2, $result);

        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1; Expires=Wed, 09-Jun-1999 10:18:14 GMT',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'http://example.com/');
        $result = $this->cookies->getAll();
        $this->assertCount(2, $result, 'Path does not match, no expiration');

        // Use a more common date format that doesn't match
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1; Domain=.foo.example.com; Expires=Wed, 09-Jun-1999 10:18:14 GMT',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'http://example.com/some/path');
        $result = $this->cookies->getAll();
        $this->assertCount(2, $result, 'Domain does not match, no expiration');

        // Use an RFC1123 date
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1; Expires=Wed, 09 Jun 1999 10:18:14 GMT',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'http://example.com/some/path');
        $result = $this->cookies->getAll();
        $this->assertCount(1, $result, 'Domain does not match, no expiration');

        $expected = [
            [
                'name' => 'second',
                'value' => '2',
                'path' => '/',
                'domain' => 'example.com'
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test getting cookies with secure flags
     *
     * @return void
     */
    public function testGetMatchingSecure()
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1',
            'Set-Cookie: second=2; Secure; HttpOnly',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'https://example.com/');

        $result = $this->cookies->get('https://example.com/test');
        $expected = ['first' => '1', 'second' => '2'];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://example.com/test');
        $expected = ['first' => '1'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test getting cookies with secure flags
     *
     * @return void
     */
    public function testGetMatchingPath()
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1; Path=/foo',
            'Set-Cookie: second=2; Path=/',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'http://example.com/foo');

        $result = $this->cookies->get('http://example.com/foo');
        $expected = ['first' => '1', 'second' => 2];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://example.com/');
        $expected = ['second' => 2];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://example.com/test');
        $expected = ['second' => 2];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getting cookies matching on paths exactly
     *
     * @return void
     */
    public function testGetMatchingDomain()
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1; Domain=example.com',
            'Set-Cookie: second=2;',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'http://foo.example.com/');

        $result = $this->cookies->get('http://example.com');
        $expected = ['first' => 1];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://foo.example.com');
        $expected = ['first' => 1, 'second' => '2'];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://bar.foo.example.com');
        $expected = ['first' => 1, 'second' => '2'];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://api.example.com');
        $expected = ['first' => 1];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://google.com');
        $expected = [];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getting cookies matching on paths exactly
     *
     * @return void
     */
    public function testGetMatchingDomainWithDot()
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1; Domain=.example.com',
            'Set-Cookie: second=2;',
        ];
        $response = new Response($headers, '');
        $this->cookies->store($response, 'http://foo.example.com/');

        $result = $this->cookies->get('http://example.com');
        $expected = ['first' => 1];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://foo.example.com');
        $expected = ['first' => 1, 'second' => '2'];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://bar.foo.example.com');
        $expected = ['first' => 1, 'second' => '2'];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://api.example.com');
        $expected = ['first' => 1];
        $this->assertEquals($expected, $result);

        $result = $this->cookies->get('http://google.com');
        $expected = [];
        $this->assertEquals($expected, $result);
    }
}
