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
    public function setUp(): void
    {
        parent::setUp();

        Security::setSalt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mia1390as13dla8kjasdlwerpoiASf');
    }

    /**
     * Tests that any password not produced by WeakPasswordHasher needs
     * to be rehashed
     *
     * @return void
     */
    public function testNeedsRehash(): void
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
    public function testHashAndCheck(): void
    {
        $hasher = new WeakPasswordHasher();
        $hasher->setConfig('hashType', 'md5');
        $password = $hasher->hash('foo');
        $this->assertTrue($hasher->check('foo', $password));
        $this->assertFalse($hasher->check('bar', $password));

        $hasher->setConfig('hashType', 'sha1');
        $this->assertFalse($hasher->check('foo', $password));
    }
}
