<?php
declare(strict_types=1);

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

use Cake\Http\Client\Request as ClientRequest;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use DateTime;
use InvalidArgumentException;

/**
 * Cookie collection test.
 */
class CookieCollectionTest extends TestCase
{
    /**
     * Test constructor
     */
    public function testConstructorWithEmptyArray(): void
    {
        $collection = new CookieCollection([]);
        $this->assertCount(0, $collection);
    }

    /**
     * Test valid cookies
     */
    public function testConstructorWithCookieArray(): void
    {
        $cookies = [
            new Cookie('one', 'one'),
            new Cookie('two', 'two'),
        ];

        $collection = new CookieCollection($cookies);
        $this->assertCount(2, $collection);
    }

    /**
     * Test iteration
     */
    public function testIteration(): void
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b'),
            new Cookie('three', 'tree'),
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
     */
    public function testAdd(): void
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
     */
    public function testAddDuplicates(): void
    {
        $remember = new Cookie('remember_me', 'yes');
        $rememberNo = new Cookie('remember_me', 'no', null, '/path2');
        $this->assertNotEquals($remember->getId(), $rememberNo->getId(), 'Cookies should have different ids');

        $collection = new CookieCollection([]);
        $new = $collection->add($remember)->add($rememberNo);

        $this->assertCount(2, $new, 'Cookies with different ids create duplicates.');
        $this->assertNotSame($new, $collection);
        $this->assertSame($remember, $new->get('remember_me'), 'get() fetches first cookie');
    }

    /**
     * Test has()
     */
    public function testHas(): void
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b'),
        ];

        $collection = new CookieCollection($cookies);
        $this->assertFalse($collection->has('nope'));
        $this->assertTrue($collection->has('remember_me'));
        $this->assertTrue($collection->has('REMEMBER_me'), 'case insensitive cookie names');
    }

    /**
     * Test removing cookies
     */
    public function testRemove(): void
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b'),
        ];

        $collection = new CookieCollection($cookies);
        $this->assertInstanceOf(Cookie::class, $collection->get('REMEMBER_me'), 'case insensitive cookie names');
        $new = $collection->remove('remember_me');
        $this->assertTrue($collection->has('remember_me'), 'old instance not modified');

        $this->assertNotSame($new, $collection);
        $this->assertFalse($new->has('remember_me'), 'should be removed');

        $this->expectException(InvalidArgumentException::class);
        $new->get('remember_me');
    }

    /**
     * Test getting cookies by name
     */
    public function testGetByName(): void
    {
        $cookies = [
            new Cookie('remember_me', 'a'),
            new Cookie('gtm', 'b'),
        ];

        $collection = new CookieCollection($cookies);
        $this->assertFalse($collection->has('nope'));
        $this->assertInstanceOf(Cookie::class, $collection->get('REMEMBER_me'), 'case insensitive cookie names');
        $this->assertInstanceOf(Cookie::class, $collection->get('remember_me'));
        $this->assertSame($cookies[0], $collection->get('remember_me'));
    }

    /**
     * Test that the constructor takes only an array of objects implementing
     * the CookieInterface
     */
    public function testConstructorWithInvalidCookieObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected `Cake\Http\Cookie\CookieCollection[]` as $cookies but instead got `array` at index 1');
        $array = [
            new Cookie('one', 'one'),
            [],
        ];

        new CookieCollection($array);
    }

    /**
     * Test adding cookies from a response.
     */
    public function testAddFromResponse(): void
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app',
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', 'test=value')
            ->withAddedHeader('Set-Cookie', 'expiring=soon; Expires=Mon, 09-Jun-2031 10:18:14 GMT; Path=/; HttpOnly; Secure;')
            ->withAddedHeader('Set-Cookie', 'session=123abc; Domain=www.example.com')
            ->withAddedHeader('Set-Cookie', 'maxage=value; Max-Age=60; Expires=Mon, 09-Jun-2031 10:18:14 GMT;');
        $new = $collection->addFromResponse($response, $request);
        $this->assertNotSame($new, $collection, 'Should clone collection');

        $this->assertTrue($new->has('test'));
        $this->assertTrue($new->has('session'));
        $this->assertTrue($new->has('expiring'));
        $this->assertSame('value', $new->get('test')->getValue());
        $this->assertSame('123abc', $new->get('session')->getValue());
        $this->assertSame('soon', $new->get('expiring')->getValue());
        $this->assertSame('value', $new->get('maxage')->getValue());

        $this->assertSame('/app', $new->get('test')->getPath(), 'cookies should inherit request path');
        $this->assertSame('/', $new->get('expiring')->getPath(), 'path attribute should be used.');

        $this->assertNull($new->get('test')->getExpiry(), 'No expiry');
        $this->assertSame(
            '2031-06-09 10:18:14',
            $new->get('expiring')->getExpiry()->format('Y-m-d H:i:s'),
            'Has expiry'
        );
        $session = $new->get('session');
        $this->assertNull($session->getExpiry(), 'No expiry');
        $this->assertSame('www.example.com', $session->getDomain(), 'Has domain');

        $maxage = $new->get('maxage');
        $this->assertLessThanOrEqual(
            (new DateTime('60 seconds'))->format('Y-m-d H:i:s'),
            $maxage->getExpiry()->format('Y-m-d H:i:s'),
            'Has max age'
        );
    }

    /**
     * Test adding cookies that contain URL encoded data
     */
    public function testAddFromResponseValueUrldecodeData(): void
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app',
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
     * Test adding cookies from a response ignores empty headers
     */
    public function testAddFromResponseIgnoreEmpty(): void
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app',
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', '');
        $new = $collection->addFromResponse($response, $request);
        $this->assertCount(0, $new, 'no cookies parsed');
    }

    /**
     * Test adding cookies from a response ignores expired cookies
     */
    public function testAddFromResponseIgnoreExpired(): void
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app',
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', 'test=value')
            ->withAddedHeader('Set-Cookie', 'expired=soon; Expires=Wed, 09-Jun-2012 10:18:14 GMT; Path=/;');
        $new = $collection->addFromResponse($response, $request);
        $this->assertFalse($new->has('expired'), 'Should drop expired cookies');
    }

    /**
     * Test adding cookies from a response removes existing cookies if
     * the new response marks them as expired.
     */
    public function testAddFromResponseRemoveExpired(): void
    {
        $collection = new CookieCollection([
            new Cookie('expired', 'not yet', null, '/', 'example.com'),
        ]);
        $request = new ServerRequest([
            'url' => '/app',
            'environment' => [
                'HTTP_HOST' => 'example.com',
            ],
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', 'test=value')
            ->withAddedHeader('Set-Cookie', 'expired=soon; Expires=Wed, 09-Jun-2012 10:18:14 GMT; Path=/;');
        $new = $collection->addFromResponse($response, $request);
        $this->assertFalse($new->has('expired'), 'Should drop expired cookies');
    }

    /**
     * Test adding cookies from a response with bad expires values
     */
    public function testAddFromResponseInvalidExpires(): void
    {
        $collection = new CookieCollection();
        $request = new ServerRequest([
            'url' => '/app',
        ]);
        $response = (new Response())
            ->withAddedHeader('Set-Cookie', 'test=value')
            ->withAddedHeader('Set-Cookie', 'expired=no; Expires=1w; Path=/; HttpOnly; Secure;');
        $new = $collection->addFromResponse($response, $request);
        $this->assertTrue($new->has('test'));
        $this->assertTrue($new->has('expired'));
        $expired = $new->get('expired');
        $this->assertNull($expired->getExpiry());
    }

    /**
     * Test adding cookies from responses updates cookie values.
     */
    public function testAddFromResponseUpdateExisting(): void
    {
        $collection = new CookieCollection([
            new Cookie('key', 'old value', null, '/', 'example.com'),
        ]);
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [
                'HTTP_HOST' => 'example.com',
            ],
        ]);
        $response = (new Response())->withAddedHeader('Set-Cookie', 'key=new value');
        $new = $collection->addFromResponse($response, $request);
        $this->assertTrue($new->has('key'));
        $this->assertSame('new value', $new->get('key')->getValue());
    }

    /**
     * Test adding cookies from the collection to request.
     */
    public function testAddToRequest(): void
    {
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('default', '1', null, '/', 'example.com'))
            ->add(new Cookie('api', 'A', null, '/api', 'example.com'))
            ->add(new Cookie('blog', 'b', null, '/blog', 'blog.example.com'))
            ->add(new Cookie('expired', 'ex', new DateTime('-2 seconds'), '/', 'example.com'));
        $request = new ClientRequest('http://example.com/api');
        $request = $collection->addToRequest($request);
        $this->assertSame('default=1; api=A', $request->getHeaderLine('Cookie'));

        $request = new ClientRequest('http://example.com/');
        $request = $collection->addToRequest($request);
        $this->assertSame('default=1', $request->getHeaderLine('Cookie'));

        $request = new ClientRequest('http://example.com');
        $request = $collection->addToRequest($request);
        $this->assertSame('default=1', $request->getHeaderLine('Cookie'));

        $request = new ClientRequest('http://example.com/blog');
        $request = $collection->addToRequest($request);
        $this->assertSame('default=1', $request->getHeaderLine('Cookie'), 'domain matching should apply');

        $request = new ClientRequest('http://foo.blog.example.com/blog');
        $request = $collection->addToRequest($request);
        $this->assertSame('default=1; blog=b', $request->getHeaderLine('Cookie'));
    }

    /**
     * Test adding no cookies
     */
    public function testAddToRequestNoCookies(): void
    {
        $collection = new CookieCollection();
        $request = new ClientRequest('http://example.com/api');
        $request = $collection->addToRequest($request);
        $this->assertFalse($request->hasHeader('Cookie'), 'No header should be set.');
    }

    /**
     * Testing the cookie size limit warning
     */
    public function testCookieSizeWarning(): void
    {
        $this->expectWarning();
        $this->expectWarningMessage('The cookie `default` exceeds the recommended maximum cookie length of 4096 bytes.');

        $string = Security::insecureRandomBytes(9000);
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('default', $string, null, '/', 'example.com'));
        $request = new ClientRequest('http://example.com/api');
        $collection->addToRequest($request);
    }

    /**
     * Test adding cookies from the collection to request.
     */
    public function testAddToRequestExtraCookies(): void
    {
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('api', 'A', null, '/api', 'example.com'))
            ->add(new Cookie('blog', 'b', null, '/blog', 'blog.example.com'))
            ->add(new Cookie('expired', 'ex', new DateTime('-2 seconds'), '/', 'example.com'));
        $request = new ClientRequest('http://example.com/api');
        $request = $collection->addToRequest($request, ['b' => 'B']);
        $this->assertSame('b=B; api=A', $request->getHeaderLine('Cookie'));

        $request = new ClientRequest('http://example.com/api');
        $request = $collection->addToRequest($request, ['api' => 'custom']);
        $this->assertSame('api=custom', $request->getHeaderLine('Cookie'), 'Extra cookies overwrite values in jar');
    }

    /**
     * Test adding cookies ignores leading dot
     */
    public function testAddToRequestLeadingDot(): void
    {
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('public', 'b', null, '/', '.example.com'));
        $request = new ClientRequest('http://example.com/blog');
        $request = $collection->addToRequest($request);
        $this->assertSame('public=b', $request->getHeaderLine('Cookie'));
    }

    /**
     * Test adding cookies checks the secure crumb
     */
    public function testAddToRequestSecureCrumb(): void
    {
        $collection = new CookieCollection();
        $collection = $collection
            ->add(new Cookie('secret', 'A', null, '/', 'example.com', true))
            ->add(new Cookie('public', 'b', null, '/', '.example.com', false));
        $request = new ClientRequest('https://example.com/api');
        $request = $collection->addToRequest($request);
        $this->assertSame('secret=A; public=b', $request->getHeaderLine('Cookie'));

        // no HTTPS set.
        $request = new ClientRequest('http://example.com/api');
        $request = $collection->addToRequest($request);
        $this->assertSame('public=b', $request->getHeaderLine('Cookie'));
    }

    /**
     * test createFromHeader() building cookies from a header string.
     */
    public function testCreateFromHeader(): void
    {
        $header = [
            'http=name; HttpOnly; Secure;',
            'expires=expiring; Expires=Mon, 17-Apr-2023 10:22:22; Path=/api; HttpOnly; Secure;',
            'expired=expired; version=1; Expires=Wed, 15-Jun-2015 10:22:22;',
            'invalid=invalid-secure; Expires=Mon, 17-Apr-2023 10:22:22; Secure=true; SameSite=none',
            '7=numeric',
        ];
        $cookies = CookieCollection::createFromHeader($header);
        $this->assertCount(4, $cookies);
        $this->assertTrue($cookies->has('http'));
        $this->assertTrue($cookies->has('expires'));
        $this->assertFalse($cookies->has('version'));
        $this->assertTrue($cookies->has('expired'), 'Expired cookies should be present');
        $this->assertFalse($cookies->has('invalid'), 'Invalid cookies should not be present');
        $this->assertTrue($cookies->has('7'));
    }

    /**
     * test createFromServerRequest() building cookies from a header string.
     */
    public function testCreateFromServerRequest(): void
    {
        $request = new ServerRequest(['cookies' => ['name' => 'val', 'cakephp' => 'rocks']]);
        $cookies = CookieCollection::createFromServerRequest($request);
        $this->assertCount(2, $cookies);
        $this->assertTrue($cookies->has('name'));
        $this->assertTrue($cookies->has('cakephp'));

        $cookie = $cookies->get('name');
        $this->assertSame('val', $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame('', $cookie->getDomain(), 'No domain on request cookies');
    }
}
