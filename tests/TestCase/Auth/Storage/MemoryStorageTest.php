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
 * @since         3.2.12
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth\Storage;

use Cake\Auth\Storage\MemoryStorage;
use Cake\TestSuite\TestCase;

/**
 * Test case for MemoryStorage
 *
 */
class MemoryStorageTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->storage = new MemoryStorage;
        $this->user = ['username' => 'giantGummyLizard'];
    }

    /**
     * Test write.
     *
     * @return void
     */
    public function testWrite()
    {
        $this->storage->write($this->user);
        $this->assertSame($this->user, $this->storage->read());
    }

    /**
     * Test read.
     *
     * @return void
     */
    public function testRead()
    {
        $this->assertNull($this->storage->read());
    }

    /**
     * Test delete.
     *
     * @return void
     */
    public function testDelete()
    {
        $this->storage->write($this->user);
        $this->storage->delete();

        $this->assertNull($this->storage->read());
    }

    /**
     * Test redirectUrl.
     *
     * @return void
     */
    public function testRedirectUrl()
    {
        $this->assertNull($this->storage->redirectUrl());

        $this->storage->redirectUrl('/posts/the-gummy-lizards');
        $this->assertSame('/posts/the-gummy-lizards', $this->storage->redirectUrl());

        $this->assertNull($this->storage->redirectUrl(false));
        $this->assertNull($this->storage->redirectUrl());
    }
}
