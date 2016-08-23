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
 * @link          http://cakephp.org CakePHP Project
 * @since         3.1.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestCase;
use Cake\Utility\Security;

/**
 * CookieEncryptedUsingControllerTest class
 */
class CookieEncryptedUsingControllerTest extends IntegrationTestCase
{
    /**
     * reset environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');

        Security::salt('abcdabcdabcdabcdabcdabcdabcdabcdabcd');
        Router::connect('/:controller/:action/*', [], ['routeClass' => 'InflectedRoute']);
        DispatcherFactory::clear();
        DispatcherFactory::add('Routing');
        DispatcherFactory::add('ControllerFactory');
        $this->useHttpServer(false);
    }

    /**
     * Can encrypt/decrypt the cookie value.
     */
    public function testCanEncryptAndDecryptWithAes()
    {
        $this->cookieEncrypted('NameOfCookie', 'Value of Cookie', 'aes');

        $this->get('/cookie_component_test/view/');
        $this->assertStringStartsWith('Q2FrZQ==.', $this->viewVariable('ValueFromRequest'), 'Encrypted');
        $this->assertEquals('Value of Cookie', $this->viewVariable('ValueFromCookieComponent'), 'Decrypted');
    }

    /**
     * Can encrypt/decrypt the cookie value by default.
     */
    public function testCanEncryptAndDecryptCookieValue()
    {
        $this->cookieEncrypted('NameOfCookie', 'Value of Cookie');

        $this->get('/cookie_component_test/view/');
        $this->assertStringStartsWith('Q2FrZQ==.', $this->viewVariable('ValueFromRequest'), 'Encrypted');
        $this->assertEquals('Value of Cookie', $this->viewVariable('ValueFromCookieComponent'), 'Decrypted');
    }

    /**
     * Can encrypt/decrypt even if the cookie value are array.
     */
    public function testCanEncryptAndDecryptEvenIfCookieValueIsArray()
    {
        $this->cookieEncrypted('NameOfCookie', ['Value1 of Cookie', 'Value2 of Cookie']);

        $this->get('/cookie_component_test/view/');
        $this->assertStringStartsWith('Q2FrZQ==.', $this->viewVariable('ValueFromRequest'), 'Encrypted');
        $this->assertEquals(
            ['Value1 of Cookie', 'Value2 of Cookie'],
            $this->viewVariable('ValueFromCookieComponent'),
            'Decrypted'
        );
    }

    /**
     * Can specify the encryption key.
     */
    public function testCanSpecifyEncryptionKey()
    {
        $key = 'another salt xxxxxxxxxxxxxxxxxxx';
        $this->cookieEncrypted('NameOfCookie', 'Value of Cookie', 'aes', $key);

        $this->get('/cookie_component_test/view/' . urlencode($key));
        $this->assertStringStartsWith('Q2FrZQ==.', $this->viewVariable('ValueFromRequest'), 'Encrypted');
        $this->assertEquals('Value of Cookie', $this->viewVariable('ValueFromCookieComponent'), 'Decrypted');
    }

    /**
     * Can be used Security::salt() as the encryption key.
     */
    public function testCanBeUsedSecuritySaltAsEncryptionKey()
    {
        $key = 'another salt xxxxxxxxxxxxxxxxxxx';
        Security::salt($key);
        $this->cookieEncrypted('NameOfCookie', 'Value of Cookie', 'aes');

        $this->get('/cookie_component_test/view/' . urlencode($key));
        $this->assertStringStartsWith('Q2FrZQ==.', $this->viewVariable('ValueFromRequest'), 'Encrypted');
        $this->assertEquals('Value of Cookie', $this->viewVariable('ValueFromCookieComponent'), 'Decrypted');
    }

    /**
     * Can AssertCookie even if the value is encrypted by
     * the CookieComponent.
     */
    public function testCanAssertCookieEncrypted()
    {
        $this->get('/cookie_component_test/set_cookie');
        $this->assertCookieEncrypted('abc', 'NameOfCookie');
    }

    /**
     * Can AssertCookie even if encrypted with the aes.
     */
    public function testCanAssertCookieEncryptedWithAes()
    {
        $this->get('/cookie_component_test/set_cookie');
        $this->assertCookieEncrypted('abc', 'NameOfCookie', 'aes');
    }

    /**
     * Can AssertCookie even if encrypted with the another
     * encrypted key.
     */
    public function testCanAssertCookieEncryptedWithAnotherEncryptionKey()
    {
        $key = 'another salt xxxxxxxxxxxxxxxxxxx';
        Security::salt($key);
        $this->get('/cookie_component_test/set_cookie');
        $this->assertCookieEncrypted('abc', 'NameOfCookie', 'aes', $key);
    }

    /**
     * Can AssertCookie even if encrypted with the aes when using PSR7 server.
     */
    public function testCanAssertCookieEncryptedWithAesWhenUsingPsr7()
    {
        $this->useHttpServer(true);
        $this->get('/cookie_component_test/set_cookie');
        $this->assertCookieEncrypted('abc', 'NameOfCookie', 'aes');
    }
}
