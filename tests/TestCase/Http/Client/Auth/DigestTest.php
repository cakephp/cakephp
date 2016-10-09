<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * @var \PHPUnit_Framework_MockObject_MockObject|\Cake\Http\Client
     */
    public $client;

    /**
     * @var \Cake\Http\Client\Auth\Digest
     */
    public $auth;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->client = $this->getClientMock();
        $this->auth = new Digest($this->client);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Cake\Http\Client
     */
    protected function getClientMock()
    {
        return $this->getMockBuilder(Client::class)
            ->setMethods(['send'])
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
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51"'
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $request = $this->auth->authentication($request, $auth);

        $result = $request->getHeaderLine('Authorization');
        $this->assertContains('Digest', $result);
        $this->assertContains('realm="The batcave"', $result);
        $this->assertContains('nonce="4cded326c6c51"', $result);
        $this->assertContains('response="a21a874c0b29165929f5d24d1aad2c47"', $result);
        $this->assertContains('uri="/some/path"', $result);
        $this->assertNotContains('qop=', $result);
        $this->assertNotContains('nc=', $result);
    }

    /**
     * testQop method
     *
     * @return void
     */
    public function testQop()
    {
        $headers = [
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51",qop="auth"'
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $request = $this->auth->authentication($request, $auth);
        $result = $request->getHeaderLine('Authorization');

        $this->assertContains('qop="auth"', $result);
        $this->assertContains('nc=00000001', $result);
        $this->assertRegexp('/cnonce="[a-z0-9]+"/', $result);
    }

    /**
     * testOpaque method
     *
     * @return void
     */
    public function testOpaque()
    {
        $headers = [
            'WWW-Authenticate: Digest realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'
        ];

        $response = new Response($headers, '');
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $auth = ['username' => 'admin', 'password' => '1234'];
        $request = new Request('http://example.com/some/path', Request::METHOD_GET);
        $request = $this->auth->authentication($request, $auth);
        $result = $request->getHeaderLine('Authorization');

        $this->assertContains('opaque="d8ea7aa61a1693024c4cc3a516f49b3c"', $result);
    }
}
