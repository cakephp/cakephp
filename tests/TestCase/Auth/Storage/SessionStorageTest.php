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
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\Storage\SessionStorage;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;

/**
 * Test case for SessionStorage
 *
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

        $this->session = $this->getMock('Cake\Network\Session');
        $this->request = new Request(['session' => $this->session]);
        $this->storage = new SessionStorage($this->request, ['key' => 'Auth.AuthUser']);
        $this->user = ['id' => 1];
    }

    /**
     * Test set
     *
     * @return void
     */
    public function testSet()
    {
        $this->session->expects($this->once())
            ->method('write')
            ->with('Auth.AuthUser', $this->user)
            ->will($this->returnValue(true));

        $this->storage->set($this->user);
    }

    /**
     * Test get
     *
     * @return void
     */
    public function testGet()
    {
        $this->session->expects($this->once())
            ->method('read')
            ->with('Auth.AuthUser')
            ->will($this->returnValue($this->user));

        $result = $this->storage->get();
        $this->assertSame($this->user, $result);
    }

    /**
     * Test get from local var
     *
     * @return void
     */
    public function testGetFromLocalVar()
    {
        $this->storage->set($this->user);

        $this->session->expects($this->never())
            ->method('read');

        $result = $this->storage->get();
        $this->assertSame($this->user, $result);
    }

    /**
     * Test remove
     *
     * @return void
     */
    public function testRemove()
    {
        $this->session->expects($this->once())
            ->method('delete')
            ->with('Auth.AuthUser');

        $this->storage->remove();
    }
}
