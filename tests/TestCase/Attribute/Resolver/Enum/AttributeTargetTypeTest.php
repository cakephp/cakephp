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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Attribute\Resolver\Enum;

use Cake\Attribute\Resolver\Enum\AttributeTargetType;
use Cake\TestSuite\TestCase;

/**
 * AttributeTargetType Enum Test
 */
class AttributeTargetTypeTest extends TestCase
{
    /**
     * Test that enum has all expected cases
     */
    public function testEnumCases(): void
    {
        $cases = AttributeTargetType::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(AttributeTargetType::CLASS_TYPE, $cases);
        $this->assertContains(AttributeTargetType::METHOD, $cases);
        $this->assertContains(AttributeTargetType::PROPERTY, $cases);
        $this->assertContains(AttributeTargetType::PARAMETER, $cases);
        $this->assertContains(AttributeTargetType::CLASS_CONSTANT, $cases);
    }

    /**
     * Test enum values are strings
     */
    public function testEnumValues(): void
    {
        $this->assertSame('class', AttributeTargetType::CLASS_TYPE->value);
        $this->assertSame('method', AttributeTargetType::METHOD->value);
        $this->assertSame('property', AttributeTargetType::PROPERTY->value);
        $this->assertSame('parameter', AttributeTargetType::PARAMETER->value);
        $this->assertSame('class_constant', AttributeTargetType::CLASS_CONSTANT->value);
    }

    /**
     * Test enum from() method
     */
    public function testFromValue(): void
    {
        $this->assertSame(AttributeTargetType::CLASS_TYPE, AttributeTargetType::from('class'));
        $this->assertSame(AttributeTargetType::METHOD, AttributeTargetType::from('method'));
        $this->assertSame(AttributeTargetType::PROPERTY, AttributeTargetType::from('property'));
        $this->assertSame(AttributeTargetType::PARAMETER, AttributeTargetType::from('parameter'));
        $this->assertSame(AttributeTargetType::CLASS_CONSTANT, AttributeTargetType::from('class_constant'));
    }

    /**
     * Test enum tryFrom() with invalid value
     */
    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(AttributeTargetType::tryFrom('invalid'));
    }
}
