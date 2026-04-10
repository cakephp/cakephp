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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\AttributeResolver\Enum;

use Cake\AttributeResolver\Enum\MethodVisibility;
use Cake\TestSuite\TestCase;

/**
 * MethodVisibility Enum Test
 */
class MethodVisibilityTest extends TestCase
{
    /**
     * Test that enum has all expected cases
     */
    public function testEnumCases(): void
    {
        $cases = MethodVisibility::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(MethodVisibility::PUBLIC, $cases);
        $this->assertContains(MethodVisibility::PROTECTED, $cases);
        $this->assertContains(MethodVisibility::PRIVATE, $cases);
    }

    /**
     * Test enum values are strings
     */
    public function testEnumValues(): void
    {
        $this->assertSame('public', MethodVisibility::PUBLIC->value);
        $this->assertSame('protected', MethodVisibility::PROTECTED->value);
        $this->assertSame('private', MethodVisibility::PRIVATE->value);
    }

    /**
     * Test enum from() method
     */
    public function testFromValue(): void
    {
        $this->assertSame(MethodVisibility::PUBLIC, MethodVisibility::from('public'));
        $this->assertSame(MethodVisibility::PROTECTED, MethodVisibility::from('protected'));
        $this->assertSame(MethodVisibility::PRIVATE, MethodVisibility::from('private'));
    }

    /**
     * Test enum tryFrom() with invalid value
     */
    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(MethodVisibility::tryFrom('invalid'));
    }
}
