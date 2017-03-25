<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Cookie;

use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use DateTime;

/**
 * Cookie collection test.
 */
class CookieCollectionTest extends TestCase
{

    /**
     * Test constructor
     *
     * @return void
     */
    public function testConstructorWithEmptyArray()
    {
        $collection = new CookieCollection([]);
        $this->assertCount(0, $collection);
    }

    /**
     * Test valid cookies
     *
     * @return void
     */
    public function testConstructorWithCookieArray()
    {
        $cookies = [
            new Cookie('one', 'one'),
            new Cookie('two', 'two')
        ];

        $collection = new CookieCollection($cookies);
        $this->assertCount(2, $collection);
    }

    /**
     * Test iteration
     *
     * @return void
     */
    public function testIteration()
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b'),
            new Cookie('three', 'tree')
        ];

        $collection = new CookieCollection($cookies);
        $names = [];
        foreach ($collection as $cookie) {
            $names[] = $cookie->getName();
        }
        $this->assertSame(['remember_me', 'gtm', 'three'], $names);
    }

    /**
     * Test adding cookies
     *
     * @return void
     */
    public function testAdd()
    {
        $cookies = [];

        $collection = new CookieCollection($cookies);
        $this->assertCount(0, $collection);

        $remember = new Cookie('remember_me', 'a');
        $new = $collection->add($remember);
        $this->assertNotSame($new, $collection->add($remember));
        $this->assertCount(0, $collection, 'Original instance not modified');
        $this->assertCount(1, $new);
        $this->assertFalse($collection->has('remember_me'), 'Original instance not modified');
        $this->assertTrue($new->has('remember_me'));
        $this->assertSame($remember, $new->get('remember_me'));
    }

    /**
     * Cookie collections need to support duplicate cookie names because
     * of use cases in Http\Client
     *
     * @return void
     */
    public function testAddDuplicates()
    {
        $remember = new Cookie('remember_me', 'yes');
        $rememberNo = new Cookie('remember_me', 'no');
        $collection = new CookieCollection([]);
        $new = $collection->add($remember)->add($rememberNo);

        $this->assertCount(2, $new);
        $this->assertNotSame($new, $collection);
        $this->assertSame($remember, $new->get('remember_me'), 'get() fetches first cookie');
    }

    /**
     * Test has()
     *
     * @return void
     */
    public function testHas()
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b')
        ];

        $collection = new CookieCollection($cookies);
        $this->assertFalse($collection->has('nope'));
        $this->assertTrue($collection->has('remember_me'));
        $this->assertTrue($collection->has('REMEMBER_me'), 'case insensitive cookie names');
    }

    /**
     * Test removing cookies
     *
     * @return void
     */
    public function testRemove()
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b')
        ];

        $collection = new CookieCollection($cookies);
        $this->assertInstanceOf(Cookie::class, $collection->get('REMEMBER_me'), 'case insensitive cookie names');
        $new = $collection->remove('remember_me');
        $this->assertTrue($collection->has('remember_me'), 'old instance not modified');

        $this->assertNotSame($new, $collection);
        $this->assertFalse($new->has('remember_me'), 'should be removed');
        $this->assertNull($new->get('remember_me'), 'should be removed');
    }

    /**
     * Test getting cookies by name
     *
     * @return void
     */
    public function testGetByName()
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b')
        ];

        $collection = new CookieCollection($cookies);
        $this->assertNull($collection->get('nope'));
        $this->assertInstanceOf(Cookie::class, $collection->get('REMEMBER_me'), 'case insensitive cookie names');
        $this->assertInstanceOf(Cookie::class, $collection->get('remember_me'));
        $this->assertSame($cookies[0], $collection->get('remember_me'));
    }

    /**
     * Test that the constructor takes only an array of objects implementing
     * the CookieInterface
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected `Cake\Http\Cookie\CookieCollection[]` as $cookies but instead got `array` at index 1
     * @return void
     */
    public function testConstructorWithInvalidCookieObjects()
    {
        $array = [
            new Cookie('one', 'one'),
            []
        ];

        new CookieCollection($array);
    }

    /**
     * Test adding cookies from a response.
     *
     * @return void
     */
    public function testAddFromResponse()
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app'
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', 'test=value')
            ->withAddedHeader('Set-Cookie', 'expiring=soon; Expires=Wed, 09-Jun-2021 10:18:14 GMT; Path=/; HttpOnly; Secure;')
            ->withAddedHeader('Set-Cookie', 'session=123abc; Domain=www.example.com');
        $new = $collection->addFromResponse($response, $request);
        $this->assertNotSame($new, $collection, 'Should clone collection');

        $this->assertTrue($new->has('test'));
        $this->assertTrue($new->has('session'));
        $this->assertTrue($new->has('expiring'));
        $this->assertSame('value', $new->get('test')->getValue());
        $this->assertSame('123abc', $new->get('session')->getValue());
        $this->assertSame('soon', $new->get('expiring')->getValue());

        $this->assertSame('/app', $new->get('test')->getPath(), 'cookies should inherit request path');
        $this->assertSame('/', $new->get('expiring')->getPath(), 'path attribute should be used.');

        $this->assertSame(0, $new->get('test')->getExpiry(), 'No expiry');
        $this->assertSame(
            '2021-06-09 10:18:14',
            date('Y-m-d H:i:s', $new->get('expiring')->getExpiry()),
            'Has expiry'
        );
        $session = $new->get('session');
        $this->assertSame(0, $session->getExpiry(), 'No expiry');
        $this->assertSame('www.example.com', $session->getDomain(), 'Has domain');
    }

    /**
     * Test adding cookies that contain URL encoded data
     *
     * @return void
     */
    public function testAddFromResponseValueUrldecodeData()
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app'
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', 'test=val%3Bue; Path=/example; Secure;');
        $new = $collection->addFromResponse($response, $request);
        $this->assertTrue($new->has('test'));

        $test = $new->get('test');
        $this->assertSame('val;ue', $test->getValue());
        $this->assertSame('/example', $test->getPath());
    }

    /**
     * Test adding cookies from a response ignores expired cookies
     *
     * @return void
     */
    public function testAddFromResponseIgnoreExpired()
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app'
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', 'test=value')
            ->withAddedHeader('Set-Cookie', 'expired=soon; Expires=Wed, 09-Jun-2012 10:18:14 GMT; Path=/;');
        $new = $collection->addFromResponse($response, $request);
        $this->assertFalse($new->has('expired'), 'Should drop expired cookies');
    }

    /**
     * Test adding cookies from the collection to request.
     *
     * @return void
     */
    public function testAddToRequest()
    {
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('api', 'A', null, '/api', 'example.com'))
            ->add(new Cookie('blog', 'b', null, '/blog', 'blog.example.com'))
            ->add(new Cookie('expired', 'ex', new DateTime('-2 seconds'), '/', 'example.com'));
        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/api'
            ]
        ]);
        $request = $collection->addToRequest($request);
        $this->assertCount(1, $request->getCookieParams());
        $this->assertSame(['api' => 'A'], $request->getCookieParams());

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/'
            ]
        ]);
        $request = $collection->addToRequest($request);
        $this->assertCount(0, $request->getCookieParams());

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/blog'
            ]
        ]);
        $request = $collection->addToRequest($request);
        $this->assertCount(0, $request->getCookieParams(), 'domain matching should apply');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'foo.blog.example.com',
                'REQUEST_URI' => '/blog'
            ]
        ]);
        $request = $collection->addToRequest($request);
        $this->assertCount(1, $request->getCookieParams(), 'domain matching should apply');
        $this->assertSame(['blog' => 'b'], $request->getCookieParams());
    }

    /**
     * Test adding cookies ignores leading dot
     *
     * @return void
     */
    public function testAddToRequestLeadingDot()
    {
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('public', 'b', null, '/', '.example.com'));
        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/blog'
            ]
        ]);
        $request = $collection->addToRequest($request);
        $this->assertSame(['public' => 'b'], $request->getCookieParams());
    }

    /**
     * Test adding cookies checks the secure crumb
     *
     * @return void
     */
    public function testAddToRequestSecureCrumb()
    {
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('secret', 'A', null, '/', 'example.com', true))
            ->add(new Cookie('public', 'b', null, '/', '.example.com', false));
        $request = new ServerRequest([
            'environment' => [
                'HTTPS' => 'on',
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/api'
            ]
        ]);
        $request = $collection->addToRequest($request);
        $this->assertSame(['secret' => 'A', 'public' => 'b'], $request->getCookieParams());

        // no HTTPS set.
        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/api'
            ]
        ]);
        $request = $collection->addToRequest($request);
        $this->assertSame(['public' => 'b'], $request->getCookieParams());
    }
}
