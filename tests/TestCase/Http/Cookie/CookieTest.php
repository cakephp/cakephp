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

use Cake\Chronos\Chronos;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieInterface;
use Cake\TestSuite\TestCase;
use DateTimeInterface;

/**
 * HTTP cookies test.
 */
class CookieTest extends TestCase
{
    /**
     * Generate invalid cookie names.
     *
     * @return array
     */
    public function invalidNameProvider()
    {
        return [
            ['no='],
            ["no\rnewline"],
            ["no\nnewline"],
            ["no\ttab"],
            ['no,comma'],
            ['no;semi'],
        ];
    }

    /**
     * Test invalid cookie name
     *
     * @dataProvider invalidNameProvider
     */
    public function testValidateNameInvalidChars($name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('contains invalid characters.');
        new Cookie($name, 'value');
    }

    /**
     * Test empty cookie name
     *
     * @return void
     */
    public function testValidateNameEmptyName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The cookie name cannot be empty.');
        new Cookie('', '');
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
        $this->assertSame('cakephp=cakephp-rocks; path=/', $result);

        $date = Chronos::createFromFormat('m/d/Y h:i:s', '12/1/2027 12:00:00');

        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $cookie = $cookie->withDomain('cakephp.org')
            ->withExpiry($date)
            ->withHttpOnly(true)
            ->withSameSite(CookieInterface::SAMESITE_STRICT)
            ->withSecure(true);
        $result = $cookie->toHeaderValue();

        $expected = 'cakephp=cakephp-rocks; expires=Wed, 01-Dec-2027 12:00:00 GMT; path=/; domain=cakephp.org; samesite=Strict; secure; httponly';
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
        $this->assertSame('cakephp-rocks', $result);

        $cookie = new Cookie('cakephp', '');
        $result = $cookie->getValue();
        $this->assertSame('', $result);
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
     * Test getting the value from the cookie
     *
     * @return void
     */
    public function testGetStringValue()
    {
        $cookie = new Cookie('cakephp', 'thing');
        $this->assertSame('thing', $cookie->getStringValue());

        $value = ['user_id' => 1, 'token' => 'abc123'];
        $cookie = new Cookie('cakephp', $value);

        $this->assertSame($value, $cookie->getValue());
        $this->assertSame(json_encode($value), $cookie->getStringValue());
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
        $this->assertStringNotContainsString('example.com', $cookie->toHeaderValue(), 'old instance not modified');
        $this->assertStringContainsString('domain=example.com', $new->toHeaderValue());
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
        $this->assertStringNotContainsString('path=/api', $cookie->toHeaderValue(), 'old instance not modified');
        $this->assertStringContainsString('path=/api', $new->toHeaderValue());
    }

    /**
     * Test setting SameSite in cookies
     *
     * @return void
     */
    public function testWithSameSite()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $new = $cookie->withSameSite(CookieInterface::SAMESITE_LAX);
        $this->assertNotSame($new, $cookie, 'Should make a clone');
        $this->assertStringNotContainsString('samesite=Lax', $cookie->toHeaderValue(), 'old instance not modified');
        $this->assertStringContainsString('samesite=Lax', $new->toHeaderValue());
    }

    /**
     * Test setting SameSite in cookies
     *
     * @return void
     */
    public function testWithSameSiteException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Samesite value must be either of: ' . implode(', ', CookieInterface::SAMESITE_VALUES));

        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $cookie->withSameSite('invalid');
    }

    /**
     * Test default path in cookies
     *
     * @return void
     */
    public function testDefaultPath()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $this->assertStringContainsString('path=/', $cookie->toHeaderValue());
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
        $this->assertStringContainsString('01-Jan-2038', $new->toHeaderValue());
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
        $this->assertStringNotContainsString('expiry', $cookie->toHeaderValue());

        $this->assertStringContainsString('01-Jan-1970', $new->toHeaderValue());
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
        $this->assertStringNotContainsString('expires', $cookie->toHeaderValue());

        $this->assertStringContainsString('expires=Wed, 15-Jun-2022', $new->toHeaderValue());
    }

    /**
     * Test the withExpiry method changes timezone
     *
     * @return void
     */
    public function testWithExpiryChangesTimezone()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $date = Chronos::createFromDate(2022, 6, 15);
        $date = $date->setTimezone('America/New_York');

        $new = $cookie->withExpiry($date);
        $this->assertNotSame($new, $cookie, 'Should clone');
        $this->assertStringNotContainsString('expires', $cookie->toHeaderValue());

        $this->assertStringContainsString('expires=Wed, 15-Jun-2022', $new->toHeaderValue());
        $this->assertStringContainsString('GMT', $new->toHeaderValue());
        $this->assertSame((int)$date->format('U'), $new->getExpiresTimestamp());
    }

    /**
     * Test the isExpired method
     *
     * @return void
     */
    public function testIsExpired()
    {
        $date = Chronos::now();
        $cookie = new Cookie('cakephp', 'yay');
        $this->assertFalse($cookie->isExpired($date));

        $cookie = new Cookie('cakephp', 'yay', $date);
        $this->assertFalse($cookie->isExpired($date), 'same time, not expired');

        $date = $date->modify('+10 seconds');
        $this->assertTrue($cookie->isExpired($date), 'future now');

        $date = $date->modify('-1 minute');
        $this->assertFalse($cookie->isExpired($date), 'expires later');
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
        $new = $cookie->withoutAddedValue('type')
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
            'user' => ['name' => 'mark'],
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
                'profession' => 'developer',
            ],
        ];
        $cookie = new Cookie('cakephp', json_encode($data));
        $this->assertFalse($cookie->isExpanded());
        $this->assertSame('developer', $cookie->read('profile.profession'));
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
                'profession' => 'developer',
            ],
        ];
        $cookie = new Cookie('cakephp', $data);

        $result = $cookie->getValue();
        $this->assertEquals($data, $result);

        $result = $cookie->read('foo');
        $this->assertNull($result);

        $result = $cookie->read();
        $this->assertEquals($data, $result);

        $result = $cookie->read('profile.profession');
        $this->assertSame('developer', $result);
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
        $this->assertSame('value', $cookie->read('key'));
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
                'profession' => 'developer',
            ],
        ];
        $cookie = new Cookie('cakephp', $data);
        $this->assertSame('developer', $cookie->read('profile.profession'));

        $expected = '{"username":"florian","profile":{"profession":"developer"}}';
        $this->assertStringContainsString(urlencode($expected), $cookie->toHeaderValue());
    }

    /**
     * Tests getting the id
     *
     * @return void
     */
    public function testGetId()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $this->assertSame('cakephp;;/', $cookie->getId());

        $cookie = new Cookie('CAKEPHP', 'cakephp-rocks');
        $this->assertSame('CAKEPHP;;/', $cookie->getId());

        $cookie = new Cookie('test', 'val', null, '/path', 'example.com');
        $this->assertSame('test;example.com;/path', $cookie->getId());
    }

    public function testCreateFromHeaderString()
    {
        $header = 'cakephp=cakephp-rocks; expires=Wed, 01-Dec-2027 12:00:00 GMT; path=/; domain=cakephp.org; samesite=invalid; secure; httponly';
        $result = Cookie::createFromHeaderString($header);

        // Ignore invalid value when parsing headers
        // https://tools.ietf.org/html/draft-west-first-party-cookies-07#section-4.1
        $this->assertNull($result->getSameSite());
    }

    public function testDefaults()
    {
        Cookie::setDefaults(['path' => '/cakephp', 'expires' => time()]);
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $this->assertSame('/cakephp', $cookie->getPath());
        $this->assertInstanceOf(DateTimeInterface::class, $cookie->getExpiry());

        Cookie::setDefaults(['path' => '/', 'expires' => null]);
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getExpiry());
    }

    public function testInvalidExpiresForDefaults()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type `array` for expire');

        Cookie::setDefaults(['expires' => ['ompalompa']]);
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
    }

    public function testInvalidSameSiteForDefaults()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Samesite value must be either of: ' . implode(', ', CookieInterface::SAMESITE_VALUES));

        Cookie::setDefaults(['samesite' => 'ompalompa']);
        $cookie = new Cookie('cakephp', 'cakephp-rocks');
    }
}
