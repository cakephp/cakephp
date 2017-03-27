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
            ->withExpiry($date)
            ->withHttpOnly(true)
            ->withSecure(true);
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
     * @expectedExceptionMessage The provided arg must be of type `string` but `integer` given
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
     * @expectedExceptionMessage The provided arg must be of type `string` but `array` given
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
     * @expectedExceptionMessage The provided arg must be of type `string` but `array` given
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
     * @expectedExceptionMessage The provided arg must be of type `string` but `integer` given
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
     * @expectedExceptionMessage The provided arg must be of type `bool` but `string` given
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
     * @expectedExceptionMessage The provided arg must be of type `bool` but `string` given
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
     * @expectedExceptionMessage The provided arg must be of type `bool` but `string` given
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
     * @expectedExceptionMessage The provided arg must be of type `bool` but `string` given
     */
    public function testWithSecureInvalid()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $cookie->withSecure('no');
    }

    /**
     * Test setting secure in cookies
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
        $this->assertNotContains('expiry', $cookie->toHeaderValue());

        $now = Chronos::parse('-1 year');
        $this->assertContains($now->format('Y'), $new->toHeaderValue());
    }

    /**
     * Test the withExpiry method
     *
     * @return void
     */
    public function testWithExpiry()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withExpiry(Chronos::createFromDate(2022, 6, 15));
        $this->assertNotSame($new, $cookie, 'Should clone');
        $this->assertNotContains('expires', $cookie->toHeaderValue());

        $this->assertContains('expires=Wed, 15-Jun-2022', $new->toHeaderValue());
    }

    /**
     * Test the withName method
     *
     * @return void
     */
    public function testWithName()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withName('user');
        $this->assertNotSame($new, $cookie, 'Should clone');
        $this->assertNotSame('user', $cookie->getName());
        $this->assertSame('user', $new->getName());
    }

    /**
     * Test the withAddedValue method
     *
     * @return void
     */
    public function testWithAddedValue()
    {
        $cookie = new Cookie('cakephp', '{"type":"mvc", "icing": true}');
        $new = $cookie->withAddedValue('type', 'mvc')
            ->withAddedValue('user.name', 'mark');
        $this->assertNotSame($new, $cookie, 'Should clone');
        $this->assertNull($cookie->read('user.name'));
        $this->assertSame('mvc', $new->read('type'));
        $this->assertSame('mark', $new->read('user.name'));
    }

    /**
     * Test the withoutAddedValue method
     *
     * @return void
     */
    public function testWithoutAddedValue()
    {
        $cookie = new Cookie('cakephp', '{"type":"mvc", "user": {"name":"mark"}}');
        $new = $cookie->withoutAddedValue('type', 'mvc')
            ->withoutAddedValue('user.name');
        $this->assertNotSame($new, $cookie, 'Should clone');

        $this->assertNotNull($cookie->read('type'));
        $this->assertNull($new->read('type'));
        $this->assertNull($new->read('user.name'));
    }

    /**
     * Test check() with serialized source data.
     *
     * @return void
     */
    public function testCheckStringSourceData()
    {
        $cookie = new Cookie('cakephp', '{"type":"mvc", "user": {"name":"mark"}}');
        $this->assertTrue($cookie->check('type'));
        $this->assertTrue($cookie->check('user.name'));
        $this->assertFalse($cookie->check('nope'));
        $this->assertFalse($cookie->check('user.nope'));
    }

    /**
     * Test check() with array source data.
     *
     * @return void
     */
    public function testCheckArraySourceData()
    {
        $data = [
            'type' => 'mvc',
            'user' => ['name' => 'mark']
        ];
        $cookie = new Cookie('cakephp', $data);
        $this->assertTrue($cookie->check('type'));
        $this->assertTrue($cookie->check('user.name'));
        $this->assertFalse($cookie->check('nope'));
        $this->assertFalse($cookie->check('user.nope'));
    }

    /**
     * test read() and set on different types
     *
     * @return void
     */
    public function testReadExpandsOnDemand()
    {
        $data = [
            'username' => 'florian',
            'profile' => [
                'profession' => 'developer'
            ]
        ];
        $cookie = new Cookie('cakephp', json_encode($data));
        $this->assertFalse($cookie->isExpanded());
        $this->assertEquals('developer', $cookie->read('profile.profession'));
        $this->assertTrue($cookie->isExpanded(), 'Cookies expand when read.');

        $cookie = $cookie->withValue(json_encode($data));
        $this->assertTrue($cookie->check('profile.profession'), 'Cookies expand when read.');
        $this->assertTrue($cookie->isExpanded());

        $cookie = $cookie->withValue(json_encode($data))
            ->withAddedValue('face', 'punch');
        $this->assertTrue($cookie->isExpanded());
        $this->assertSame('punch', $cookie->read('face'));
    }

    /**
     * test read() on structured data.
     *
     * @return void
     */
    public function testReadComplexData()
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
    }

    /**
     * Test reading complex data serialized in 1.x and early 2.x
     *
     * @return void
     */
    public function testReadLegacyComplexData()
    {
        $data = 'key|value,key2|value2';
        $cookie = new Cookie('cakephp', $data);
        $this->assertEquals('value', $cookie->read('key'));
        $this->assertNull($cookie->read('nope'));
    }

    /**
     * Test that toHeaderValue() collapses data.
     *
     * @return void
     */
    public function testToHeaderValueCollapsesComplexData()
    {
        $data = [
            'username' => 'florian',
            'profile' => [
                'profession' => 'developer'
            ]
        ];
        $cookie = new Cookie('cakephp', $data);
        $this->assertEquals('developer', $cookie->read('profile.profession'));

        $expected = '{"username":"florian","profile":{"profession":"developer"}}';
        $this->assertContains(urlencode($expected), $cookie->toHeaderValue());
    }

    /**
     * Tests getting the id
     *
     * @return void
     */
    public function testGetId()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $this->assertEquals('cakephp;;', $cookie->getId());

        $cookie = new Cookie('test', 'val', null, '/path', 'example.com');
        $this->assertEquals('test;example.com;/path', $cookie->getId());
    }
}
