<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CookieComponent;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * CookieComponentTest class
 */
class CookieComponentTest extends TestCase
{

    /**
     * start
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $controller = $this->getMock(
            'Cake\Controller\Controller',
            ['redirect'],
            [new Request(), new Response()]
        );
        $controller->loadComponent('Cookie');
        $this->Controller = $controller;
        $this->Cookie = $controller->Cookie;
        $this->request = $controller->request;

        $this->Cookie->config([
            'expires' => '+10 seconds',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'key' => 'somerandomhaskeysomerandomhaskey',
            'encryption' => false,
        ]);
    }

    /**
     * Test setting config per key.
     *
     * @return void
     */
    public function testConfigKey()
    {
        $this->Cookie->configKey('User', 'expires', '+3 days');
        $result = $this->Cookie->configKey('User');
        $expected = [
            'expires' => '+3 days',
            'path' => '/',
            'domain' => '',
            'key' => 'somerandomhaskeysomerandomhaskey',
            'secure' => false,
            'httpOnly' => false,
            'encryption' => false,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test setting config per key.
     *
     * @return void
     */
    public function testConfigKeyArray()
    {
        $this->Cookie->configKey('User', [
            'expires' => '+3 days',
            'path' => '/shop'
        ]);
        $result = $this->Cookie->configKey('User');
        $expected = [
            'expires' => '+3 days',
            'path' => '/shop',
            'domain' => '',
            'key' => 'somerandomhaskeysomerandomhaskey',
            'secure' => false,
            'httpOnly' => false,
            'encryption' => false,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * sets up some default cookie data.
     *
     * @return void
     */
    protected function _setCookieData()
    {
        $this->Cookie->write(['Encrytped_array' => ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']]);
        $this->Cookie->write(['Encrypted_multi_cookies.name' => 'CakePHP']);
        $this->Cookie->write(['Encrypted_multi_cookies.version' => '1.2.0.x']);
        $this->Cookie->write(['Encrypted_multi_cookies.tag' => 'CakePHP Rocks!']);

        $this->Cookie->write(['Plain_array' => ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']], null, false);
        $this->Cookie->write(['Plain_multi_cookies.name' => 'CakePHP'], null, false);
        $this->Cookie->write(['Plain_multi_cookies.version' => '1.2.0.x'], null, false);
        $this->Cookie->write(['Plain_multi_cookies.tag' => 'CakePHP Rocks!'], null, false);
    }

    /**
     * test that initialize sets settings from components array
     *
     * @return void
     */
    public function testSettings()
    {
        $settings = [
            'time' => '5 days',
            'path' => '/'
        ];
        $Cookie = new CookieComponent(new ComponentRegistry(), $settings);
        $this->assertEquals($Cookie->config('time'), $settings['time']);
        $this->assertEquals($Cookie->config('path'), $settings['path']);
    }

    /**
     * Test read when an invalid cipher is configured.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid encryption cipher. Must be one of aes, rijndael.
     * @return void
     */
    public function testReadInvalidCipher()
    {
        $this->request->cookies = [
            'Test' => $this->_encrypt('value'),
        ];
        $this->Cookie->config('encryption', 'derp');
        $this->Cookie->read('Test');
    }

    /**
     * testReadEncryptedCookieData
     *
     * @return void
     */
    public function testReadEncryptedCookieData()
    {
        $this->_setCookieData();
        $data = $this->Cookie->read('Encrytped_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrypted_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
    }

    /**
     * testReadPlainCookieData
     *
     * @return void
     */
    public function testReadPlainCookieData()
    {
        $this->_setCookieData();
        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
    }

    /**
     * test read() after switching the cookie name.
     *
     * @return void
     */
    public function testReadMultipleNames()
    {
        $this->request->cookies = [
            'CakeCookie' => [
                'key' => 'value'
            ],
            'OtherCookie' => [
                'key' => 'other value'
            ]
        ];
        $this->assertEquals('value', $this->Cookie->read('CakeCookie.key'));
        $this->assertEquals(['key' => 'value'], $this->Cookie->read('CakeCookie'));
        $this->assertEquals('other value', $this->Cookie->read('OtherCookie.key'));
    }

    /**
     * test a simple write()
     *
     * @return void
     */
    public function testWriteSimple()
    {
        $this->Cookie->write('Testing', 'value');
        $result = $this->Cookie->read('Testing');

        $this->assertEquals('value', $result);
    }

    /**
     * Test write when an invalid cipher is configured.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid encryption cipher. Must be one of aes, rijndael.
     * @return void
     */
    public function testWriteInvalidCipher()
    {
        $this->Cookie->config('encryption', 'derp');
        $this->Cookie->write('Test', 'nope');
    }

    /**
     * Test writes don't omit request data from being read.
     *
     * @return void
     */
    public function testWriteThanRead()
    {
        $this->request->cookies = [
            'User' => ['name' => 'mark']
        ];
        $this->Cookie->write('Testing', 'value');
        $this->assertEquals('mark', $this->Cookie->read('User.name'));
    }

    /**
     * test write() encrypted data with falsey value
     *
     * @return void
     */
    public function testWriteWithFalseyValue()
    {
        $this->Cookie->config([
            'encryption' => 'aes',
            'key' => 'qSI232qs*&sXOw!adre@34SAv!@*(XSL#$%)asGb$@11~_+!@#HKis~#^',
        ]);

        $this->Cookie->write('Testing');
        $result = $this->Cookie->read('Testing');
        $this->assertNull($result);

        $this->Cookie->write('Testing', '');
        $result = $this->Cookie->read('Testing');
        $this->assertEquals('', $result);

        $this->Cookie->write('Testing', false);
        $result = $this->Cookie->read('Testing');
        $this->assertFalse($result);

        $this->Cookie->write('Testing', 1);
        $result = $this->Cookie->read('Testing');
        $this->assertEquals(1, $result);

        $this->Cookie->write('Testing', '0');
        $result = $this->Cookie->read('Testing');
        $this->assertSame('0', $result);

        $this->Cookie->write('Testing', 0);
        $result = $this->Cookie->read('Testing');
        $this->assertSame(0, $result);
    }

    /**
     * test write with distant future cookies
     *
     * @return void
     */
    public function testWriteFarFuture()
    {
        $this->Cookie->configKey('Testing', 'expires', '+90 years');
        $this->Cookie->write('Testing', 'value');
        $future = new Time('now');
        $future = $future->modify('+90 years');

        $expected = [
            'name' => 'Testing',
            'value' => 'value',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false];
        $result = $this->Controller->response->cookie('Testing');

        $this->assertEquals($future->format('U'), $result['expire'], '', 3);
        unset($result['expire']);

        $this->assertEquals($expected, $result);
    }

    /**
     * test write with httpOnly cookies
     *
     * @return void
     */
    public function testWriteHttpOnly()
    {
        $this->Cookie->config([
            'httpOnly' => true,
            'secure' => false
        ]);
        $this->Cookie->write('Testing', 'value', false);
        $expected = [
            'name' => 'Testing',
            'value' => 'value',
            'expire' => (new Time('+10 seconds'))->format('U'),
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true];
        $result = $this->Controller->response->cookie('Testing');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test writing multiple nested keys when some are encrypted.
     *
     * @return void
     */
    public function testWriteMulitMixedEncryption()
    {
        $this->Cookie->configKey('Open', 'encryption', false);
        $this->Cookie->configKey('Closed', 'encryption', 'aes');
        $this->Cookie->write([
            'Closed.key' => 'secret',
            'Open.key' => 'not secret',
        ]);
        $expected = [
            'name' => 'Open',
            'value' => '{"key":"not secret"}',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false
        ];
        $result = $this->Controller->response->cookie('Open');
        unset($result['expire']);
        $this->assertEquals($expected, $result);

        $result = $this->Controller->response->cookie('Closed');
        $this->assertContains('Q2FrZQ==.', $result['value']);
    }

    /**
     * test delete with httpOnly
     *
     * @return void
     */
    public function testDeleteHttpOnly()
    {
        $this->Cookie->config([
            'httpOnly' => true,
            'secure' => false
        ]);
        $this->Cookie->delete('Testing');
        $expected = [
            'name' => 'Testing',
            'value' => '',
            'expire' => (new Time('now'))->format('U') - 42000,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true];
        $result = $this->Controller->response->cookie('Testing');
        $this->assertEquals($expected, $result);
    }

    /**
     * test writing values that are not scalars
     *
     * @return void
     */
    public function testWriteArrayValues()
    {
        $this->Cookie->write('Testing', [1, 2, 3]);
        $expected = [
            'name' => 'Testing',
            'value' => '[1,2,3]',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false
        ];
        $result = $this->Controller->response->cookie('Testing');

        $time = new Time('now');
        $this->assertWithinRange($time->format('U') + 10, $result['expire'], 1);
        unset($result['expire']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that writing mixed arrays results in the correct data.
     *
     * @return void
     */
    public function testWriteMixedArray()
    {
        $this->Cookie->write('User', ['name' => 'mark'], false);
        $this->Cookie->write('User.email', 'mark@example.com', false);
        $expected = [
            'name' => 'User',
            'value' => '{"name":"mark","email":"mark@example.com"}',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false
        ];
        $result = $this->Controller->response->cookie('User');
        unset($result['expire']);

        $this->assertEquals($expected, $result);

        $this->Cookie->write('User.email', 'mark@example.com', false);
        $this->Cookie->write('User', ['name' => 'mark'], false);
        $expected = [
            'name' => 'User',
            'value' => '{"name":"mark"}',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false
        ];
        $result = $this->Controller->response->cookie('User');
        unset($result['expire']);

        $this->assertEquals($expected, $result);
    }

    /**
     * testDeleteCookieValue
     *
     * @return void
     */
    public function testDeleteCookieValue()
    {
        $this->_setCookieData();
        $this->Cookie->delete('Encrypted_multi_cookies.name');
        $data = $this->Cookie->read('Encrypted_multi_cookies');
        $expected = ['version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $this->Cookie->delete('Encrytped_array');
        $data = $this->Cookie->read('Encrytped_array');
        $this->assertNull($data);

        $this->Cookie->delete('Plain_multi_cookies.name');
        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $this->Cookie->delete('Plain_array');
        $data = $this->Cookie->read('Plain_array');
        $this->assertNull($data);
    }

    /**
     * testReadingCookieArray
     *
     * @return void
     */
    public function testReadingCookieArray()
    {
        $this->_setCookieData();

        $data = $this->Cookie->read('Encrytped_array.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_array.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrytped_array.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrypted_multi_cookies.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrypted_multi_cookies.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrypted_multi_cookies.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.name');
        $expected = 'CakePHP';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.version');
        $expected = '1.2.0.x';
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies.tag');
        $expected = 'CakePHP Rocks!';
        $this->assertEquals($expected, $data);
    }

    /**
     * testReadingCookieDataOnStartup
     *
     * @return void
     */
    public function testReadingDataFromRequest()
    {
        $this->Cookie->configKey('Encrypted_array', 'encryption', 'aes');
        $this->Cookie->configKey('Encrypted_multi_cookies', 'encryption', 'aes');

        $data = $this->Cookie->read('Encrypted_array');
        $this->assertNull($data);

        $data = $this->Cookie->read('Encrypted_multi_cookies');
        $this->assertNull($data);

        $data = $this->Cookie->read('Plain_array');
        $this->assertNull($data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $this->assertNull($data);

        $this->request->cookies = [
            'Encrypted_array' => $this->_encrypt(['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!']),
            'Encrypted_multi_cookies' => [
                'name' => $this->_encrypt('CakePHP'),
                'version' => $this->_encrypt('1.2.0.x'),
                'tag' => $this->_encrypt('CakePHP Rocks!')
            ],
            'Plain_array' => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
            'Plain_multi_cookies' => [
                'name' => 'CakePHP',
                'version' => '1.2.0.x',
                'tag' => 'CakePHP Rocks!'
            ]
        ];

        $data = $this->Cookie->read('Encrypted_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Encrypted_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_array');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);

        $data = $this->Cookie->read('Plain_multi_cookies');
        $expected = ['name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'];
        $this->assertEquals($expected, $data);
    }

    /**
     * Test Reading legacy cookie values.
     *
     * @return void
     */
    public function testReadLegacyCookieValue()
    {
        $this->request->cookies = [
            'Legacy' => ['value' => $this->_oldImplode([1, 2, 3])]
        ];
        $result = $this->Cookie->read('Legacy.value');
        $expected = [1, 2, 3];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test reading empty values.
     *
     * @return void
     */
    public function testReadEmpty()
    {
        $this->request->cookies = [
            'JSON' => '{"name":"value"}',
            'Empty' => '',
            'String' => '{"somewhat:"broken"}',
            'Array' => '{}'
        ];
        $this->assertEquals(['name' => 'value'], $this->Cookie->read('JSON'));
        $this->assertEquals('value', $this->Cookie->read('JSON.name'));
        $this->assertEquals('', $this->Cookie->read('Empty'));
        $this->assertEquals('{"somewhat:"broken"}', $this->Cookie->read('String'));
        $this->assertEquals([], $this->Cookie->read('Array'));
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        $this->Cookie->write('CookieComponentTestCase', 'value');
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->assertFalse($this->Cookie->check('NotExistingCookieComponentTestCase'));
    }

    /**
     * testCheckingSavedEmpty method
     *
     * @return void
     */
    public function testCheckingSavedEmpty()
    {
        $this->Cookie->write('CookieComponentTestCase', 0);
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

        $this->Cookie->write('CookieComponentTestCase', '0');
        $this->assertTrue($this->Cookie->check('CookieComponentTestCase'));
    }

    /**
     * testCheckKeyWithSpaces method
     *
     * @return void
     */
    public function testCheckKeyWithSpaces()
    {
        $this->Cookie->write('CookieComponent Test', "test");
        $this->assertTrue($this->Cookie->check('CookieComponent Test'));
        $this->Cookie->delete('CookieComponent Test');

        $this->Cookie->write('CookieComponent Test.Test Case', "test");
        $this->assertTrue($this->Cookie->check('CookieComponent Test.Test Case'));
    }

    /**
     * testCheckEmpty
     *
     * @return void
     */
    public function testCheckEmpty()
    {
        $this->assertFalse($this->Cookie->check());
    }

    /**
     * test that deleting a top level keys kills the child elements too.
     *
     * @return void
     */
    public function testDeleteRemovesChildren()
    {
        $this->request->cookies = [
            'User' => ['email' => 'example@example.com', 'name' => 'mark'],
            'other' => 'value'
        ];
        $this->assertEquals('mark', $this->Cookie->read('User.name'));

        $this->Cookie->delete('User');
        $this->assertNull($this->Cookie->read('User.email'));
        $this->assertNull($this->Cookie->read('User.name'));

        $result = $this->Controller->response->cookie('User');
        $this->assertEquals('', $result['value']);
        $this->assertLessThan(time(), $result['expire']);
    }

    /**
     * Test deleting recursively with keys that don't exist.
     *
     * @return void
     */
    public function testDeleteChildrenNotExist()
    {
        $this->assertNull($this->Cookie->delete('NotFound'));
        $this->assertNull($this->Cookie->delete('Not.Found'));
    }

    /**
     * Helper method for generating old style encoded cookie values.
     *
     * @param array $array
     * @return string
     */
    protected function _oldImplode(array $array)
    {
        $string = '';
        foreach ($array as $key => $value) {
            $string .= ',' . $key . '|' . $value;
        }
        return substr($string, 1);
    }

    /**
     * Implode method to keep keys are multidimensional arrays
     *
     * @param array $array Map of key and values
     * @return string String in the form key1|value1,key2|value2
     */
    protected function _implode(array $array)
    {
        return json_encode($array);
    }

    /**
     * encrypt method
     *
     * @param array|string $value
     * @return string
     */
    protected function _encrypt($value)
    {
        if (is_array($value)) {
            $value = $this->_implode($value);
        }
        return "Q2FrZQ==." . base64_encode(Security::encrypt($value, $this->Cookie->config('key')));
    }
}
