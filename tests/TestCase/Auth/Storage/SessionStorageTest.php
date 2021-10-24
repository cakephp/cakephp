<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth\Storage;

use Cake\Auth\Storage\SessionStorage;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;

/**
 * Test case for SessionStorage
 */
class SessionStorageTest extends TestCase
{
    /**
     * @var \Cake\Http\Session|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $session;

    /**
     * @var \Cake\Auth\Storage\SessionStorage
     */
    protected $storage;

    /**
     * @var array<string, mixed>
     */
    protected $user;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)->getMock();
        $request = new ServerRequest(['session' => $this->session]);
        $response = new Response();
        $this->storage = new SessionStorage($request, $response, ['key' => 'Auth.AuthUser']);
        $this->user = ['id' => 1];
    }

    /**
     * Test write
     */
    public function testWrite(): void
    {
        $this->session->expects($this->once())
            ->method('write')
            ->with('Auth.AuthUser', $this->user)
            ->will($this->returnValue(true));

        $this->storage->write($this->user);
    }

    /**
     * Test read
     */
    public function testRead(): void
    {
        $this->session->expects($this->once())
            ->method('read')
            ->with('Auth.AuthUser')
            ->will($this->returnValue($this->user));

        $result = $this->storage->read();
        $this->assertSame($this->user, $result);
    }

    /**
     * Test read from local var
     */
    public function testGetFromLocalVar(): void
    {
        $this->storage->write($this->user);

        $this->session->expects($this->never())
            ->method('read');

        $result = $this->storage->read();
        $this->assertSame($this->user, $result);
    }

    /**
     * Test delete
     */
    public function testDelete(): void
    {
        $this->session->expects($this->once())
            ->method('delete')
            ->with('Auth.AuthUser');

        $this->storage->delete();
    }

    /**
     * Test redirectUrl()
     */
    public function redirectUrl(): void
    {
        $url = '/url';

        $this->session->expects($this->once())
            ->method('write')
            ->with('Auth.redirectUrl', $url);

        $this->storage->redirectUrl($url);

        $this->session->expects($this->once())
            ->method('read')
            ->with('Auth.redirectUrl')
            ->will($this->returnValue($url));

        $result = $this->storage->redirectUrl();
        $this->assertSame($url, $result);

        $this->session->expects($this->once())
            ->method('delete')
            ->with('Auth.redirectUrl');

        $this->storage->redirectUrl(false);
    }
}
