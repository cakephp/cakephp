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

use Cake\AttributeResolver\Enum\DeclaringClassType;
use Cake\TestSuite\TestCase;

/**
 * DeclaringClassType Enum Test
 */
class DeclaringClassTypeTest extends TestCase
{
    /**
     * Test that enum has all expected cases
     */
    public function testEnumCases(): void
    {
        $cases = DeclaringClassType::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(DeclaringClassType::CLASS_, $cases);
        $this->assertContains(DeclaringClassType::INTERFACE, $cases);
        $this->assertContains(DeclaringClassType::TRAIT, $cases);
        $this->assertContains(DeclaringClassType::ENUM, $cases);
    }

    /**
     * Test enum values are strings
     */
    public function testEnumValues(): void
    {
        $this->assertSame('class', DeclaringClassType::CLASS_->value);
        $this->assertSame('interface', DeclaringClassType::INTERFACE->value);
        $this->assertSame('trait', DeclaringClassType::TRAIT->value);
        $this->assertSame('enum', DeclaringClassType::ENUM->value);
    }

    /**
     * Test enum from() method
     */
    public function testFromValue(): void
    {
        $this->assertSame(DeclaringClassType::CLASS_, DeclaringClassType::from('class'));
        $this->assertSame(DeclaringClassType::INTERFACE, DeclaringClassType::from('interface'));
        $this->assertSame(DeclaringClassType::TRAIT, DeclaringClassType::from('trait'));
        $this->assertSame(DeclaringClassType::ENUM, DeclaringClassType::from('enum'));
    }

    /**
     * Test enum tryFrom() with invalid value
     */
    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(DeclaringClassType::tryFrom('invalid'));
    }
}
