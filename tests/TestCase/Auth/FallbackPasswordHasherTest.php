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

use Cake\Auth\DefaultPasswordHasher;
use Cake\Auth\FallbackPasswordHasher;
use Cake\Auth\WeakPasswordHasher;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * Test case for FallbackPasswordHasher
 */
class FallbackPasswordHasherTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Security::setSalt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mia1390as13dla8kjasdlwerpoiASf');
    }

    /**
     * Tests that only the first hasher is user for hashing a password
     */
    public function testHash(): void
    {
        $hasher = new FallbackPasswordHasher(['hashers' => ['Weak', 'Default']]);
        $weak = new WeakPasswordHasher();
        $this->assertSame($weak->hash('foo'), $hasher->hash('foo'));

        $simple = new DefaultPasswordHasher();
        $hasher = new FallbackPasswordHasher(['hashers' => ['Weak', 'Default']]);
        $this->assertSame($weak->hash('foo'), $hasher->hash('foo'));
    }

    /**
     * Tests that the check method will check with configured hashers until a match
     * is found
     */
    public function testCheck(): void
    {
        $hasher = new FallbackPasswordHasher(['hashers' => ['Weak', 'Default']]);
        $weak = new WeakPasswordHasher();
        $simple = new DefaultPasswordHasher();

        $hash = $simple->hash('foo');
        $otherHash = $weak->hash('foo');
        $this->assertTrue($hasher->check('foo', $hash));
        $this->assertTrue($hasher->check('foo', $otherHash));
    }

    /**
     * Tests that the check method will work with configured hashers including different
     * configs per hasher.
     */
    public function testCheckWithConfigs(): void
    {
        $hasher = new FallbackPasswordHasher(['hashers' => ['Default', 'Weak' => ['hashType' => 'md5']]]);
        $legacy = new WeakPasswordHasher(['hashType' => 'md5']);
        $simple = new DefaultPasswordHasher();

        $hash = $simple->hash('foo');
        $legacyHash = $legacy->hash('foo');
        $this->assertNotSame($hash, $legacyHash);
        $this->assertTrue($hasher->check('foo', $hash));
        $this->assertTrue($hasher->check('foo', $legacyHash));
    }

    /**
     * Tests that the password only needs to be re-built according to the first hasher
     */
    public function testNeedsRehash(): void
    {
        $hasher = new FallbackPasswordHasher(['hashers' => ['Default', 'Weak']]);
        $weak = new WeakPasswordHasher();
        $otherHash = $weak->hash('foo');
        $this->assertTrue($hasher->needsRehash($otherHash));

        $simple = new DefaultPasswordHasher();
        $hash = $simple->hash('foo');
        $this->assertFalse($hasher->needsRehash($hash));
    }
}
