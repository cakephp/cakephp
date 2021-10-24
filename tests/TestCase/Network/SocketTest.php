<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network;

use Cake\Network\Exception\SocketException;
use Cake\Network\Socket;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * SocketTest class
 */
class SocketTest extends TestCase
{
    /**
     * @var \Cake\Network\Socket
     */
    protected $Socket;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Socket = new Socket(['timeout' => 1]);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->Socket);
    }

    /**
     * testConstruct method
     */
    public function testConstruct(): void
    {
        $this->Socket = new Socket();
        $config = $this->Socket->getConfig();
        $this->assertSame($config, [
            'persistent' => false,
            'host' => 'localhost',
            'protocol' => 'tcp',
            'port' => 80,
            'timeout' => 30,
        ]);

        $this->Socket->reset();
        $this->Socket->__construct(['host' => 'foo-bar']);
        $config['host'] = 'foo-bar';
        $this->assertSame($this->Socket->getConfig(), $config);

        $this->Socket = new Socket(['host' => 'www.cakephp.org', 'port' => 23, 'protocol' => 'udp']);
        $config = $this->Socket->getConfig();

        $config['host'] = 'www.cakephp.org';
        $config['port'] = 23;
        $config['protocol'] = 'udp';

        $this->assertSame($this->Socket->getConfig(), $config);
    }

    /**
     * test host() method
     */
    public function testHost(): void
    {
        $this->Socket = new Socket(['host' => '8.8.8.8']);
        $this->assertSame('dns.google', $this->Socket->host());
    }

    /**
     * test addresses() method
     */
    public function testAddresses(): void
    {
        $this->Socket = new Socket();
        $this->assertContainsEquals('127.0.0.1', $this->Socket->addresses());

        $this->Socket = new Socket(['host' => '8.8.8.8']);
        $this->assertSame(['8.8.8.8'], $this->Socket->addresses());
    }

    /**
     * testSocketConnection method
     */
    public function testSocketConnection(): void
    {
        $this->assertFalse($this->Socket->isConnected());
        $this->Socket->disconnect();
        $this->assertFalse($this->Socket->isConnected());
        try {
            $this->Socket->connect();
            $this->assertTrue($this->Socket->isConnected());
            $this->Socket->connect();
            $this->assertTrue($this->Socket->isConnected());

            $this->Socket->disconnect();
            $config = ['persistent' => true];
            $this->Socket = new Socket($config);
            $this->Socket->connect();
            $this->assertTrue($this->Socket->isConnected());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * data provider function for testInvalidConnection
     *
     * @return array
     */
    public static function invalidConnections(): array
    {
        return [
            [['host' => 'invalid.host', 'port' => 9999, 'timeout' => 1]],
            [['host' => '127.0.0.1', 'port' => '70000', 'timeout' => 1]],
        ];
    }

    /**
     * testInvalidConnection method
     *
     * @dataProvider invalidConnections
     */
    public function testInvalidConnection(array $data): void
    {
        $this->expectException(SocketException::class);
        $this->Socket->setConfig($data);
        $this->Socket->connect();
    }

    /**
     * testSocketHost method
     */
    public function testSocketHost(): void
    {
        try {
            $this->Socket = new Socket();
            $this->Socket->connect();
            $this->assertSame('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertNull($this->Socket->lastError());
            $this->assertContains('127.0.0.1', $this->Socket->addresses());

            $this->Socket = new Socket(['host' => '127.0.0.1']);
            $this->Socket->connect();
            $this->assertSame('127.0.0.1', $this->Socket->address());
            $this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
            $this->assertNull($this->Socket->lastError());
            $this->assertContains('127.0.0.1', $this->Socket->addresses());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testSocketWriting method
     */
    public function testSocketWriting(): void
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
     */
    public function testSocketReading(): void
    {
        $this->Socket = new Socket(['timeout' => 5]);
        try {
            $this->Socket->connect();
            $this->assertNull($this->Socket->read(26));

            $config = ['host' => 'google.com', 'port' => 80, 'timeout' => 1];
            $this->Socket = new Socket($config);
            $this->assertTrue($this->Socket->connect());
            $this->assertNull($this->Socket->read(26));
            $this->assertSame('2: ' . 'Connection timed out', $this->Socket->lastError());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testTimeOutConnection method
     */
    public function testTimeOutConnection(): void
    {
        $config = ['host' => '127.0.0.1', 'timeout' => 1];
        $this->Socket = new Socket($config);
        try {
            $this->assertTrue($this->Socket->connect());

            $config = ['host' => '127.0.0.1', 'timeout' => 1];
            $this->Socket = new Socket($config);
            $this->assertNull($this->Socket->read(1024 * 1024));
            $this->assertSame('2: ' . 'Connection timed out', $this->Socket->lastError());
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * testLastError method
     */
    public function testLastError(): void
    {
        $this->Socket = new Socket();
        $this->Socket->setLastError(4, 'some error here');
        $this->assertSame('4: some error here', $this->Socket->lastError());
    }

    /**
     * testReset method
     */
    public function testReset(): void
    {
        $config = [
            'persistent' => true,
            'host' => '127.0.0.1',
            'protocol' => 'udp',
            'port' => 80,
            'timeout' => 20,
        ];
        $anotherSocket = new Socket($config);
        $anotherSocket->reset();

        $expected = [
            'persistent' => false,
            'host' => 'localhost',
            'protocol' => 'tcp',
            'port' => 80,
            'timeout' => 30,
        ];
        $this->assertEquals(
            $expected,
            $anotherSocket->getConfig(),
            'Reset should cause config to return the defaults defined in _defaultConfig'
        );
    }

    /**
     * testEncrypt
     */
    public function testEnableCryptoSocketExceptionNoSsl(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new Socket($configNoSslOrTls);

        try {
            $this->Socket->connect();
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }

        $e = null;
        try {
            $this->Socket->enableCrypto('tlsv10', 'client');
        } catch (SocketException $e) {
        }

        $this->assertNotNull($e);
        $this->assertInstanceOf('Exception', $e->getPrevious());
    }

    /**
     * testEnableCryptoSocketExceptionNoTls
     */
    public function testEnableCryptoSocketExceptionNoTls(): void
    {
        $configNoSslOrTls = ['host' => 'localhost', 'port' => 80, 'timeout' => 1];

        // testing exception on no ssl socket server for ssl and tls methods
        $this->Socket = new Socket($configNoSslOrTls);

        try {
            $this->Socket->connect();
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }

        $e = null;
        try {
            $this->Socket->enableCrypto('tls', 'client');
        } catch (SocketException $e) {
        }

        $this->assertNotNull($e);
        $this->assertInstanceOf('Exception', $e->getPrevious());
    }

    /**
     * Test that protocol in the host doesn't cause cert errors.
     */
    public function testConnectProtocolInHost(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $configSslTls = ['host' => 'ssl://smtp.gmail.com', 'port' => 465, 'timeout' => 5];
        $socket = new Socket($configSslTls);
        try {
            $socket->connect();
            $this->assertSame('smtp.gmail.com', $socket->getConfig('host'));
            $this->assertSame('ssl', $socket->getConfig('protocol'));
        } catch (SocketException $e) {
            $this->markTestSkipped('Cannot test network, skipping.');
        }
    }

    /**
     * _connectSocketToSslTls
     */
    protected function _connectSocketToSslTls(): void
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
     */
    public function testEnableCryptoBadMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // testing wrong encryption mode
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('doesntExistMode', 'server');
        $this->Socket->disconnect();
    }

    /**
     * testEnableCrypto
     */
    public function testEnableCrypto(): void
    {
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client');
        $result = $this->Socket->disconnect();
        $this->assertTrue($result);
    }

    /**
     * testEnableCryptoExceptionEnableTwice
     */
    public function testEnableCryptoExceptionEnableTwice(): void
    {
        $this->expectException(SocketException::class);
        // testing on tls server
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client');
        $this->Socket->enableCrypto('tls', 'client');
    }

    /**
     * testEnableCryptoExceptionDisableTwice
     */
    public function testEnableCryptoExceptionDisableTwice(): void
    {
        $this->expectException(SocketException::class);
        $this->_connectSocketToSslTls();
        $this->Socket->enableCrypto('tls', 'client', false);
    }

    /**
     * testEnableCryptoEnableStatus
     */
    public function testEnableCryptoEnableTls12(): void
    {
        $this->_connectSocketToSslTls();
        $this->assertFalse($this->Socket->isEncrypted());
        $this->Socket->enableCrypto('tlsv12', 'client', true);
        $this->assertTrue($this->Socket->isEncrypted());
    }

    /**
     * testEnableCryptoEnableStatus
     */
    public function testEnableCryptoEnableStatus(): void
    {
        $this->_connectSocketToSslTls();
        $this->assertFalse($this->Socket->isEncrypted());
        $this->Socket->enableCrypto('tls', 'client', true);
        $this->assertTrue($this->Socket->isEncrypted());
    }

    /**
     * test getting the context for a socket.
     */
    public function testGetContext(): void
    {
        $this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
        $config = [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'timeout' => 5,
            'context' => [
                'ssl' => ['capture_peer' => true],
            ],
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
     */
    public function testConfigContext(): void
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
        $this->assertSame(5, $result['ssl']['verify_depth']);
        $this->assertSame('smtp.gmail.com', $result['ssl']['CN_match']);
        $this->assertArrayNotHasKey('ssl_verify_peer', $socket->getConfig());
        $this->assertArrayNotHasKey('ssl_allow_self_signed', $socket->getConfig());
        $this->assertArrayNotHasKey('ssl_verify_host', $socket->getConfig());
        $this->assertArrayNotHasKey('ssl_verify_depth', $socket->getConfig());
    }

    /**
     * test connect to a unix file socket
     */
    public function testConnectToUnixFileSocket(): void
    {
        $socketName = 'unix:///tmp/test.socket';
        $socket = $this->getMockBuilder(Socket::class)
            ->onlyMethods(['_getStreamSocketClient'])
            ->getMock();
        $socket->expects($this->once())
            ->method('_getStreamSocketClient')
            ->with('unix:///tmp/test.socket', null, null, 1)
            ->willReturn(false);
        $socket->setConfig([
            'host' => $socketName,
            'port' => null,
            'timeout' => 1,
            'persistent' => true,
        ]);
        $socket->connect();
    }

    /**
     * @return void
     * @deprecated
     */
    public function testDeprecatedProps()
    {
        $this->deprecated(function () {
            $this->assertFalse($this->Socket->connected);
            $this->assertFalse($this->Socket->encrypted);
        });
    }
}
