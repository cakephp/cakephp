<?php
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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\WeakPasswordHasher;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * Test case for WeakPasswordHasher
 */
class WeakPasswordHasherTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Security::salt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
    }

    /**
     * Tests that any password not produced by WeakPasswordHasher needs
     * to be rehashed
     *
     * @return void
     */
    public function testNeedsRehash()
    {
        $hasher = new WeakPasswordHasher();
        $this->assertTrue($hasher->needsRehash(md5('foo')));
        $this->assertTrue($hasher->needsRehash('bar'));
        $this->assertFalse($hasher->needsRehash('$2y$10$juOA0XVFpvZa0KTxRxEYVuX5kIS7U1fKDRcxyYhhUQECN1oHYnBMy'));
    }

    /**
     * Tests hash() and check()
     *
     * @return void
     */
    public function testHashAndCheck()
    {
        $hasher = new WeakPasswordHasher();
        $hasher->config('hashType', 'md5');
        $password = $hasher->hash('foo');
        $this->assertTrue($hasher->check('foo', $password));
        $this->assertFalse($hasher->check('bar', $password));

        $hasher->config('hashType', 'sha1');
        $this->assertFalse($hasher->check('foo', $password));
    }
}
