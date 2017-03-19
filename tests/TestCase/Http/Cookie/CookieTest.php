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

use Cake\Chronos\Chronos;
use Cake\Http\Cookie\Cookie;
use Cake\TestSuite\TestCase;

/**
 * HTTP cookies test.
 */
class CookieTest extends TestCase
{

    /**
     * Encryption key used in the tests
     *
     * @var string
     */
    protected $encryptionKey = 'someverysecretkeythatisatleast32charslong';

    /**
     * Test invalid cookie name
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The cookie name `no, this wont, work` contains invalid characters.
     */
    public function testValidateNameInvalidChars()
    {
        new Cookie('no, this wont, work', '');
    }

    /**
     * Test empty cookie name
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The cookie name cannot be empty.
     * @return void
     */
    public function testValidateNameEmptyName()
    {
        new Cookie('', '');
    }

    /**
     * Test decrypting the cookie
     *
     * @return void
     */
    public function testDecrypt()
    {
        $value = 'cakephp-rocks-and-is-awesome';
        $cookie = new Cookie('cakephp', $value);
        $cookie->encrypt($this->encryptionKey);
        $this->assertTextStartsWith('Q2FrZQ==.', $cookie->getValue());
        $cookie->decrypt($this->encryptionKey);
        $this->assertSame($value, $cookie->getValue());
    }

    /**
     * Testing encrypting the cookie
     *
     * @return void
     */
    public function testEncrypt()
    {
        $value = 'cakephp-rocks-and-is-awesome';

        $cookie = new Cookie('cakephp', $value);
        $cookie->encrypt($this->encryptionKey);

        $this->assertNotEquals($value, $cookie->getValue());
        $this->assertNotEmpty($cookie->getValue());
    }

    /**
     * Tests the header value
     *
     * @return void
     */
    public function testToHeaderValue()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $result = $cookie->toHeaderValue();
        $this->assertEquals('cakephp=cakephp-rocks', $result);

        $date = Chronos::createFromFormat('m/d/Y h:m:s', '12/1/2027 12:00:00');

        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $cookie = $cookie->withDomain('cakephp.org')
            ->withHttpOnly(true)
            ->withSecure(true);
        $cookie->expiresAt($date);
        $result = $cookie->toHeaderValue();

        $expected = 'cakephp=cakephp-rocks; expires=Tue, 01-Dec-2026 12:00:00 GMT; domain=cakephp.org; secure; httponly';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getting the value from the cookie
     *
     * @return void
     */
    public function testGetValue()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $result = $cookie->getValue();
        $this->assertEquals('cakephp-rocks', $result);

        $cookie = new Cookie('cakephp', '');
        $result = $cookie->getValue();
        $this->assertEquals('', $result);
    }

    /**
     * Test setting values in cookies
     *
     * @return void
     */
    public function testWithValue()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withValue('new');
        $this->assertNotSame($new, $cookie, 'Should make a clone');
        $this->assertSame('cakephp-rocks', $cookie->getValue(), 'old instance not modified');
        $this->assertSame('new', $new->getValue());
    }

    /**
     * Test setting domain in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithDomainInvalidConstructor()
    {
        new Cookie('cakephp', 'rocks', null, '', 1234);
    }

    /**
     * Test setting domain in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithDomainInvalid()
    {
        $cookie = new Cookie('cakephp', 'rocks');
        $cookie->withDomain(['oops']);
    }

    /**
     * Test setting domain in cookies
     *
     * @return void
     */
    public function testWithDomain()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withDomain('example.com');
        $this->assertNotSame($new, $cookie, 'Should make a clone');
        $this->assertNotContains('example.com', $cookie->toHeaderValue(), 'old instance not modified');
        $this->assertContains('domain=example.com', $new->toHeaderValue());
    }

    /**
     * Test setting path in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithPathInvalid()
    {
        $cookie = new Cookie('cakephp', 'rocks');
        $cookie->withPath(['oops']);
    }

    /**
     * Test setting path in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithPathInvalidConstructor()
    {
        new Cookie('cakephp', 'rocks', null, 123);
    }

    /**
     * Test setting path in cookies
     *
     * @return void
     */
    public function testWithPath()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withPath('/api');
        $this->assertNotSame($new, $cookie, 'Should make a clone');
        $this->assertNotContains('path=/api', $cookie->toHeaderValue(), 'old instance not modified');
        $this->assertContains('path=/api', $new->toHeaderValue());
    }

    /**
     * Test setting httponly in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithHttpOnlyInvalidConstructor()
    {
        new Cookie('cakephp', 'cakephp-rocks', null, '', '', false, 'invalid');
    }

    /**
     * Test setting httponly in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithHttpOnlyInvalid()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $cookie->withHttpOnly('no');
    }

    /**
     * Test setting httponly in cookies
     *
     * @return void
     */
    public function testWithHttpOnly()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withHttpOnly(true);
        $this->assertNotSame($new, $cookie, 'Should clone');
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertTrue($new->isHttpOnly());
    }

    /**
     * Test setting secure in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithSecureInvalidConstructor()
    {
        new Cookie('cakephp', 'cakephp-rocks', null, '', '', 'invalid');
    }

    /**
     * Test setting secure in cookies
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testWithSecureInvalid()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $cookie->withSecure('no');
    }

    /**
     * Test setting httponly in cookies
     *
     * @return void
     */
    public function testWithSecure()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withSecure(true);
        $this->assertNotSame($new, $cookie, 'Should clone');
        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($new->isSecure());
    }

    /**
     * Test the never expiry method
     *
     * @return void
     */
    public function testWithNeverExpire()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withNeverExpire();
        $this->assertNotSame($new, $cookie, 'Should clone');
        $this->assertContains('01-Jan-2038', $new->toHeaderValue());
    }

    /**
     * Test the expired method
     *
     * @return void
     */
    public function testWithExpired()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withExpired();
        $this->assertNotSame($new, $cookie, 'Should clone');

        $now = Chronos::parse('-1 year');
        $this->assertContains($now->format('Y'), $new->toHeaderValue());
    }

    /**
     * testInflateAndExpand
     *
     * @return void
     */
    public function testInflateAndExpand()
    {
        $data = [
            'username' => 'florian',
            'profile' => [
                'profession' => 'developer'
            ]
        ];
        $cookie = new Cookie('cakephp', $data);

        $result = $cookie->getValue();
        $this->assertEquals($data, $result);

        $result = $cookie->read('foo');
        $this->assertNull($result);

        $result = $cookie->read();
        $this->assertEquals($data, $result);

        $result = $cookie->read('profile.profession');
        $this->assertEquals('developer', $result);

        $result = $cookie->flatten();
        $this->assertInstanceOf(Cookie::class, $result);

        $expected = '{"username":"florian","profile":{"profession":"developer"}}';
        $result = $cookie->getValue();

        $this->assertEquals($expected, $result);
    }
}
