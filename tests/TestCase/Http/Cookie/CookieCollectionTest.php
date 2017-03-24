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
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;

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

        $rememberNo = new Cookie('remember_me', 'no');
        $second = $new->add($remember)->add($rememberNo);
        $this->assertCount(1, $second);
        $this->assertNotSame($second, $new);
        $this->assertSame($rememberNo, $second->get('remember_me'));
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
            ->withAddedHeader('Set-Cookie', 'session=123abc');
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

        $this->assertSame(0, $new->get('session')->getExpiry(), 'No expiry');
        $this->assertSame(
            '2021-06-09 10:18:14',
            date('Y-m-d H:i:s', $new->get('expiring')->getExpiry()),
            'Has expiry'
        );
    }
}
