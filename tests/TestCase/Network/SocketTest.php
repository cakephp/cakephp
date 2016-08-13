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
namespace Cake\Test\TestCase\Network;

use Cake\Network\Exception\SocketException;
use Cake\Network\Socket;
use Cake\TestSuite\TestCase;

/**
 * SocketTest class
 */
class SocketTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Socket = new Socket(['timeout' => 1]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Socket);
    }

    /**
     * testConstruct method
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->Socket = new Socket();
        $config = $this->Socket->config();
        $this->assertSame($config, [
            'persistent' => false,
            'host' => 'localhost',
            'protocol' => 'tcp',
            'port' => 80,
            'timeout' => 30
        ]);

        $this->Socket->reset();
        $this->Socket->__construct(['host' => 'foo-bar']);
        $config['host'] = 'foo-bar';
        $this->assertSame($this->Socket->config(), $config);

        $this->Socket = new Socket(['host' => 'www.cakephp.org', 'port' => 23, 'protocol' => 'udp']);
        $config = $this->Socket->config();

        $config['host'] = 'www.cakephp.org';
        $config['port'] = 23;
        $config['protocol'] = 'udp';

        $this->assertSame($this->Socket->config(), $config);
    }

    /**
     * testSocketConnection method
     *
     * @return void
     */
    public function testSocketConnection()
    {
        $this->assertFalse($this->Socket->connected);
        $this->Socket->disconnect();
        $this->assertFalse($this->Socket->connected);
        try {
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);

            $this->Socket->disconnect();
            $config = ['persistent' => true];
            $this->Socket = new Socket($config);
            $this->Socket->connect();
            $this->assertTrue($this->Socket->connected);
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * data provider function for testInvalidConnection
     *
     * @return array
     */
    public static function invalidConnections()
    {
        return [
            [['host' => 'invalid.host', 'port' => 9999, 'timeout' => 1]],
            [['host' => '127.0.0.1', 'port' => '70000', 'timeout' => 1]]
        ];
    }

    /**
     * testInvalidConnection method
     *
     * @dataProvider invalidConnections
     * @expectedException \Cake\Network\Exception\SocketException
     * @return void
     */
    public function testInvalidConnection($data)
    {
        $this->Socket->config($data);
        $this->Socket->connect();
    }

    /**
     * testSocketHost method
     *
     * @return void
     */
    public function testSocketHost()
    {
        try {
            $this->Socket = new Socket();
            $this->Socket->connect();
            $this->assertEquals('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertEquals(null, $this->Socket->lastError());
            $this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));

            $this->Socket = new Socket(['host' => '127.0.0.1']);
            $this->Socket->connect();
            $this->assertEquals('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertEquals(null, $this->Socket->lastError());
            $this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testSocketWriting method
     *
     * @return void
     */
    public function testSocketWriting()
    {
        try {
            $request = "GET / HTTP/1.1\r\nConnection: close\r\n\r\n";
            $this->assertTrue((bool)$this->Socket->write($request));
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testSocketReading method
     *
     * @return void
     */
    public function testSocketReading()
    {
        $this->Socket = new Socket(['timeout' => 5]);
        try {
            $this->Socket->connect();
            $this->assertEquals(null, $this->Socket->read(26));

            $config = ['host' => 'google.com', 'port' => 80, 'timeout' => 1];
            $this->Socket = new Socket($config);
            $this->assertTrue($this->Socket->connect());
            $this->assertEquals(null, $this->Socket->read(26));
            $this->assertEquals('2: ' . 'Connection timed out', $this->Socket->lastError());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testTimeOutConnection method
     *
     * @return void
     */
    public function testTimeOutConnection()
    {
        $config = ['host' => '127.0.0.1', 'timeout' => 0.5];
        $this->Socket = new Socket($config);
        try {
            $this->assertTrue($this->Socket->connect());

            $config = ['host' => '127.0.0.1', 'timeout' => 0.00001];
            $this->Socket = new Socket($config);
            $this->assertFalse($this->Socket->read(1024 * 1024));
            $this->assertEquals('2: ' . 'Connection timed out', $this->Socket->lastError());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testLastError method
     *
     * @return void
     */
    public function testLastError()
    {
        $this->Socket = new Socket();
        $this->Socket->setLastError(4, 'some error here');
        $this->assertEquals('4: some error here', $this->Socket->lastError());
    }

    /**
     * testReset method
     *
     * @return void
     */
    public function testReset()
    {
        $config = [
            'persistent' => true,
            'host' => '127.0.0.1',
            'protocol' => 'udp',
            'port' => 80,
            'timeout' => 20
        ];
        $anotherSocket = new Socket($config);
        $anotherSocket->reset();

        $expected = [
            'persistent' => false,
            'host' => 'localhost',
            'protocol' => 'tcp',
            'port' => 80,
            'timeout' => 30
        ];
        $this->assertEquals(
            $expected,
            $anotherSocket->config(),
            'Reset should cause config to return the defaults defined in _defaultConfig'
        );
    }

    /**
     * testEncrypt
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @return void
     */
    public function testEnableCryptoSocketExceptionNoSsl()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 0.1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new Socket($configNoSslOrTls);
        $this->Socket->connect();
        $this->Socket->enableCrypto('sslv3', 'client');
    }

    /**
     * testEnableCryptoSocketExceptionNoTls
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @return void
     */
    public function testEnableCryptoSocketExceptionNoTls()
    {
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 0.1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new Socket($configNoSslOrTls);
        $this->Socket->connect();
        $this->Socket->enableCrypto('tls', 'client');
    }

    /**
     * Test that protocol in the host doesn't cause cert errors.
     *
     * @return void
     */
    public function testConnectProtocolInHost()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configSslTls = ['host' => 'ssl://smtp.gmail.com', 'port' => 465, 'timeout' => 5];
        $socket = new Socket($configSslTls);
        try {
            $socket->connect();
            $this->assertEquals('smtp.gmail.com', $socket->config('host'));
            $this->assertEquals('ssl', $socket->config('protocol'));
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * _connectSocketToSslTls
     *
     * @return void
     */
    protected function _connectSocketToSslTls()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configSslTls = ['host' => 'smtp.gmail.com', 'port' => 465, 'timeout' => 5];
        $this->Socket = new Socket($configSslTls);
        try {
            $this->Socket->connect();
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testEnableCryptoBadMode
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testEnableCryptoBadMode()
    {
        // testing wrong encryption mode
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('doesntExistMode', 'server');
        $this->Socket->disconnect();
    }

    /**
     * testEnableCrypto
     *
     * @return void
     */
    public function testEnableCrypto()
    {
        $this->skipIf(!function_exists('stream_socket_enable_crypto'), 'Broken on HHVM');
        $this->_connectSocketToSslTls();
        $this->assertTrue($this->Socket->enableCrypto('tls', 'client'));
        $this->Socket->disconnect();
    }

    /**
     * testEnableCryptoExceptionEnableTwice
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @return void
     */
    public function testEnableCryptoExceptionEnableTwice()
    {
        $this->skipIf(!function_exists('stream_socket_enable_crypto'), 'Broken on HHVM');
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client');
        $this->Socket->enableCrypto('tls', 'client');
    }

    /**
     * testEnableCryptoExceptionDisableTwice
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @return void
     */
    public function testEnableCryptoExceptionDisableTwice()
    {
        $this->skipIf(!function_exists('stream_socket_enable_crypto'), 'Broken on HHVM');
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client', false);
    }

    /**
     * testEnableCryptoEnableStatus
     *
     * @return void
     */
    public function testEnableCryptoEnableStatus()
    {
        $this->skipIf(!function_exists('stream_socket_enable_crypto'), 'Broken on HHVM');
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->assertFalse($this->Socket->encrypted);
        $this->Socket->enableCrypto('tls', 'client', true);
        $this->assertTrue($this->Socket->encrypted);
    }

    /**
     * test getting the context for a socket.
     *
     * @return void
     */
    public function testGetContext()
    {
        $this->skipIf(
            !extension_loaded('openssl') || defined('HHVM_VERSION'),
            'OpenSSL is not enabled cannot test SSL.'
        );
        $config = [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'timeout' => 5,
            'context' => [
                'ssl' => ['capture_peer' => true]
            ]
        ];
        try {
            $this->Socket = new Socket($config);
            $this->Socket->connect();
        } catch (SocketException $e) {
            $this->markTestSkipped('No network, skipping test.');
        }
        $result = $this->Socket->context();
        $this->assertTrue($result['ssl']['capture_peer']);
    }

    /**
     * test configuring the context from the flat keys.
     *
     * @return void
     */
    public function testConfigContext()
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $this->skipIf(!empty(getenv('http_proxy')) || !empty(getenv('https_proxy')), 'Proxy detected and cannot test SSL.');
        $config = [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'timeout' => 5,
            'ssl_verify_peer' => true,
            'ssl_allow_self_signed' => false,
            'ssl_verify_depth' => 5,
            'ssl_verify_host' => true,
        ];
        $socket = new Socket($config);

        $socket->connect();
        $result = $socket->context();

        $this->assertTrue($result['ssl']['verify_peer']);
        $this->assertFalse($result['ssl']['allow_self_signed']);
        $this->assertEquals(5, $result['ssl']['verify_depth']);
        $this->assertEquals('smtp.gmail.com', $result['ssl']['CN_match']);
        $this->assertArrayNotHasKey('ssl_verify_peer', $socket->config());
        $this->assertArrayNotHasKey('ssl_allow_self_signed', $socket->config());
        $this->assertArrayNotHasKey('ssl_verify_host', $socket->config());
        $this->assertArrayNotHasKey('ssl_verify_depth', $socket->config());
    }
}
