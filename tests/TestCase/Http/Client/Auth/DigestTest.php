<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Client\Auth;

use Cake\Http\Client;
use Cake\Http\Client\Auth\Digest;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;

/**
 * Digest authentication test
 */
class DigestTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Cake\Http\Client
     */
    protected $client;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Cake\Http\Client\Auth\Digest
     */
    protected $auth;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getClientMock();
        $this->auth = $this->getDigestMock();
    }

    /**
     * @return Digest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getDigestMock()
    {
        $digest = $this->getMockBuilder(Digest::class)
            ->onlyMethods(['generateCnonce'])
            ->setConstructorArgs([$this->client])
            ->getMock();
        $digest->expects($this->any())
            ->method('generateCnonce')
            ->willReturn('cnonce');

        return $digest;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Cake\Http\Client
     */
    protected function getClientMock()
    {
        return $this->getMockBuilder(Client::class)
            ->onlyMethods(['send'])
            ->getMock();
    }

    /**
     * test getting data from additional request method
     */
    public function testRealmAndNonceFromExtraRequest(): void
    {
        $headers = [
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51"',
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $request = $this->auth->authentication($request, $auth);

        $result = $request->getHeaderLine('Authorization');
        $this->assertStringContainsString('Digest', $result);
        $this->assertStringContainsString('realm="The batcave"', $result);
        $this->assertStringContainsString('nonce="4cded326c6c51"', $result);
        $this->assertStringContainsString('response="a21a874c0b29165929f5d24d1aad2c47"', $result);
        $this->assertStringContainsString('uri="/some/path"', $result);
        $this->assertStringNotContainsString('qop=', $result);
        $this->assertStringNotContainsString('nc=', $result);
    }

    /**
     * testQopAuth method
     */
    public function testQopAuth(): void
    {
        $headers = [
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51",qop="auth"',
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));
        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $request = $this->auth->authentication($request, $auth);
        $result = $request->getHeaderLine('Authorization');

        $this->assertStringContainsString('qop=auth', $result);
        $this->assertStringContainsString('nc=00000001', $result);
        $this->assertMatchesRegularExpression('/cnonce="[a-z0-9]+"/', $result);
    }

    /**
     * testQopAuthInt method
     */
    public function testQopAuthInt(): void
    {
        $headers = [
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51",qop="auth-int"',
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $request = $this->auth->authentication($request, $auth);
        $result = $request->getHeaderLine('Authorization');
        $this->assertStringContainsString('qop=auth-int', $result);
        $this->assertStringContainsString('nc=00000001', $result);
        $this->assertMatchesRegularExpression('/cnonce="[a-z0-9]+"/', $result);
    }

    /**
     * testQopAuthInt method
     */
    public function testQopFailure(): void
    {
        $headers = [
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51",qop="wrong"',
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid QOP parameter. Valid types are: auth,auth-int');
        $this->auth->authentication($request, $auth);
    }

    /**
     * testOpaque method
     */
    public function testOpaque(): void
    {
        $headers = [
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $request = $this->auth->authentication($request, $auth);
        $result = $request->getHeaderLine('Authorization');

        $this->assertStringContainsString('opaque="d8ea7aa61a1693024c4cc3a516f49b3c"', $result);
    }

    /**
     * Data provider for testAlgorithms
     *
     * @return array[]
     */
    public function algorithmsProvider(): array
    {
        return [
            [
                'ALGORITHM: MD5 QOP: none',
                ['WWW-Authenticate: Digest algorithm="MD5", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="MD5", response="a21a874c0b29165929f5d24d1aad2c47", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: MD5-sess QOP: none',
                ['WWW-Authenticate: Digest algorithm="MD5-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="MD5-sess", nc=00000001, cnonce="cnonce", response="6807a3326271bd172439d17c2d03d295", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-256 QOP: none',
                ['WWW-Authenticate: Digest algorithm="SHA-256", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-256", response="65d00137c82412c7421ec9c8c08dccbbac667a1dedbae7db9cd888980e7af112", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-256-sess QOP: none',
                ['WWW-Authenticate: Digest algorithm="SHA-256-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-256-sess", nc=00000001, cnonce="cnonce", response="a954ffbe615b56aa16e9a7f62ea34f4a4833bb75b670f73863e2209862d0fedf", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-512-256 QOP: none',
                ['WWW-Authenticate: Digest algorithm="SHA-512-256", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-512-256", response="112b7ab122e7be8b9b5e7f32b8e4d9d4f651a53a783f1a1f267434b51f54e3cd", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-512-256-sess QOP: none',
                ['WWW-Authenticate: Digest algorithm="SHA-512-256-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-512-256-sess", nc=00000001, cnonce="cnonce", response="d0a3b5b3d10b585911a9f5fd4ec4bbe691124e5920d371e699203906ba65376f", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: MD5 QOP: auth',
                ['WWW-Authenticate: Digest algorithm="MD5", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="MD5", qop=auth, nc=00000001, cnonce="cnonce", response="716e45bf26c8abfa957d6799a34cc60f", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: MD5-sess QOP: auth',
                ['WWW-Authenticate: Digest algorithm="MD5-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="MD5-sess", qop=auth, nc=00000001, cnonce="cnonce", response="1dfe066896bfab45282f088a390abe35", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-256 QOP: auth',
                ['WWW-Authenticate: Digest algorithm="SHA-256", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-256", qop=auth, nc=00000001, cnonce="cnonce", response="f2bf2df206fd8b244d20540a5b294e5af7c7839615230acce240e3954bae781a", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-256-sess QOP: auth',
                ['WWW-Authenticate: Digest algorithm="SHA-256-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-256-sess", qop=auth, nc=00000001, cnonce="cnonce", response="9e912a2d25b9ed4d3f66bdc5f011d8be04d8971a18993adc845a0f4e5c486546", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-512-256 QOP: auth',
                ['WWW-Authenticate: Digest algorithm="SHA-512-256", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-512-256", qop=auth, nc=00000001, cnonce="cnonce", response="7569d573a117016388393fd682cbeb49a0a1af62366511a4e0d29b753ccf5e83", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-512-256-sess QOP: auth',
                ['WWW-Authenticate: Digest algorithm="SHA-512-256-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth"'],
                Request::METHOD_GET,
                [],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-512-256-sess", qop=auth, nc=00000001, cnonce="cnonce", response="e100bc5a33a1c24943d5876bc2cf37cc45e1cde06069ed8b33a75fe032351e01", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: MD5 QOP: auth-int',
                ['WWW-Authenticate: Digest algorithm="MD5", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth-int"'],
                Request::METHOD_POST,
                ['test' => 'test'],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="MD5", qop=auth-int, nc=00000001, cnonce="cnonce", response="476738bf56cf2f24173902adfa55d236", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: MD5-sess QOP: auth-int',
                ['WWW-Authenticate: Digest algorithm="MD5-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth-int"'],
                Request::METHOD_POST,
                ['test' => 'test'],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="MD5-sess", qop=auth-int, nc=00000001, cnonce="cnonce", response="beee6427899606fb6b3e09bb71b57c79", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-256 QOP: auth-int',
                ['WWW-Authenticate: Digest algorithm="SHA-256", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth-int"'],
                Request::METHOD_POST,
                ['test' => 'test'],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-256", qop=auth-int, nc=00000001, cnonce="cnonce", response="ca8f61f4d637343befeeb6282dc0302ebfc20ff974ff92b11c7e88836422f230", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-256-sess QOP: auth-int',
                ['WWW-Authenticate: Digest algorithm="SHA-256-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth-int"'],
                Request::METHOD_POST,
                ['test' => 'test'],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-256-sess", qop=auth-int, nc=00000001, cnonce="cnonce", response="a2340619cd74256bc058bf2ea0c9fd62a27f9bf62fc295b8b3e94eab441a73d1", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-512-256 QOP: auth-int',
                ['WWW-Authenticate: Digest algorithm="SHA-512-256", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth-int"'],
                Request::METHOD_POST,
                ['test' => 'test'],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-512-256", qop=auth-int, nc=00000001, cnonce="cnonce", response="055498e5d59601bea5c735a084fa74a0d33ebde3b5788057ce8380f892e99cec", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
            [
                'ALGORITHM: SHA-512-256-sess QOP: auth-int',
                ['WWW-Authenticate: Digest algorithm="SHA-512-256-sess", realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c",qop="auth-int"'],
                Request::METHOD_POST,
                ['test' => 'test'],
                'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/some/path", algorithm="SHA-512-256-sess", qop=auth-int, nc=00000001, cnonce="cnonce", response="9dbc89190bfe55eec14b1d444e1922d016d20dad461a1e9d25121c0db0024d3d", opaque="d8ea7aa61a1693024c4cc3a516f49b3c"',
            ],
        ];
    }

    /**
     * testAlgorithms method
     *
     * @dataProvider algorithmsProvider
     * @return void
     */
    public function testAlgorithms($message, $headers, $method, $data, $expected)
    {
        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));
        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', $method, [], $data);
        $request = $this->auth->authentication($request, $auth);
        $result = $request->getHeaderLine('Authorization');

        $this->assertSame($expected, $result, $message);
    }

    public function testAlgorithmException()
    {
        $headers = [
            'WWW-Authenticate: Digest algorithm="WRONG",realm="The batcave",nonce="4cded326c6c51"',
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Algorithm. Valid ones are: MD5,SHA-256,SHA-512-256,MD5-sess,SHA-256-sess,SHA-512-256-sess');
        $this->auth->authentication($request, $auth);
    }
}
