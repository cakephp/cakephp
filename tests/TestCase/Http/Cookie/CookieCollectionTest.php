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
        $this->assertNull($collection->remove('remember_me'));
        $this->assertFalse($collection->has('remember_me'));
        $this->assertNull($collection->get('remember_me'));
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
}
