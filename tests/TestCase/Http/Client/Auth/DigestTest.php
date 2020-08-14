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
     * @var \Cake\Http\Client\Auth\Digest
     */
    protected $auth;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getClientMock();
        $this->auth = new Digest($this->client);
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
     *
     * @return void
     */
    public function testRealmAndNonceFromExtraRequest()
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
     * testQop method
     *
     * @return void
     */
    public function testQop()
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

        $this->assertStringContainsString('qop="auth"', $result);
        $this->assertStringContainsString('nc=00000001', $result);
        $this->assertMatchesRegularExpression('/cnonce="[a-z0-9]+"/', $result);
    }

    /**
     * testOpaque method
     *
     * @return void
     */
    public function testOpaque()
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
}
