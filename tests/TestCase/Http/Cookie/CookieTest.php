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
        $encryptedCookieValue = 'Q2FrZQ==.N2Y1ODQ3ZDAzYzQzY2NkYTBlYTkwMmRkZjFmN'
        . 'GI3Mjk4ZWY5ZmExYTA4YmM2ZThjOWFhZWY1Njc4ZDZlMjE4Y/fhI6zv+siabYg0Cnm2j'
        . '2P51Sghk7WsVxZr94g5fhmkLJ4ve7j54v9r5/vHSIHtog==';

        $expected = 'Q2FrZQ==.N2Y1ODQ3ZDAzYzQzY2NkYTBlYTkwMmRkZjFmNGI3Mjk4ZWY5Z'
        . 'mExYTA4YmM2ZThjOWFhZWY1Njc4ZDZlMjE4Y/fhI6zv+siabYg0Cnm2j2P51Sghk7WsV'
        . 'xZr94g5fhmkLJ4ve7j54v9r5/vHSIHtog==';

        $cookie = new Cookie('cakephp', $encryptedCookieValue);
        $this->assertEquals($expected, $cookie->getValue());

        $cookie->decrypt('someverysecretkeythatisatleast32charslong');
        $this->assertEquals('cakephp-rocks-and-is-awesome', $cookie->getValue());
    }

    /**
     * Testing encrypting the cookie
     *
     * @return void
     */
    public function testEncrypt()
    {
        $cookie = new Cookie('cakephp', 'cakephp-rocks-and-is-awesome');
        $cookie->encrypt('someverysecretkeythatisatleast32charslong');

        $expected = 'cakephp-rocks-and-is-awesome';
        $this->assertNotEmpty($expected, $cookie->getValue());
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

        $date = Chronos::createFromFormat('m/d/Y h:m:s', '12/1/2050 12:00:00');

        $cookie = new Cookie('cakephp', 'cakephp-rocks');
        $cookie->setDomain('cakephp.org');
        $cookie->expiresAt($date);
        $result = $cookie->toHeaderValue();

        $expected = 'cakephp=cakephp-rocks; expires=Wed, 01-Dec-2049 12:00:00 '
        . 'GMT; domain=cakephp.org';

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
