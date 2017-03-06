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
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth\Storage;

use Cake\Auth\Storage\SessionStorage;
use Cake\Http\Response;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;

/**
 * Test case for SessionStorage
 */
class SessionStorageTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)->getMock();
        $this->request = new Request(['session' => $this->session]);
        $this->response = new Response();
        $this->storage = new SessionStorage($this->request, $this->response, ['key' => 'Auth.AuthUser']);
        $this->user = ['id' => 1];
    }

    /**
     * Test write
     *
     * @return void
     */
    public function testWrite()
    {
        $this->session->expects($this->once())
            ->method('write')
            ->with('Auth.AuthUser', $this->user)
            ->will($this->returnValue(true));

        $this->storage->write($this->user);
    }

    /**
     * Test read
     *
     * @return void
     */
    public function testRead()
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
     *
     * @return void
     */
    public function testGetFromLocalVar()
    {
        $this->storage->write($this->user);

        $this->session->expects($this->never())
            ->method('read');

        $result = $this->storage->read();
        $this->assertSame($this->user, $result);
    }

    /**
     * Test delete
     *
     * @return void
     */
    public function testDelete()
    {
        $this->session->expects($this->once())
            ->method('delete')
            ->with('Auth.AuthUser');

        $this->storage->delete();
    }

    /**
     * Test redirectUrl()
     *
     * @return void
     */
    public function redirectUrl()
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
        $this->assertEquals($url, $result);

        $this->session->expects($this->once())
            ->method('delete')
            ->with('Auth.redirectUrl');

        $this->storage->redirectUrl(false);
    }
}
