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

use Cake\AttributeResolver\Enum\AttributeTargetTypeEnum;
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
        $cases = AttributeTargetTypeEnum::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(AttributeTargetTypeEnum::CLASS_TYPE, $cases);
        $this->assertContains(AttributeTargetTypeEnum::METHOD, $cases);
        $this->assertContains(AttributeTargetTypeEnum::PROPERTY, $cases);
        $this->assertContains(AttributeTargetTypeEnum::PARAMETER, $cases);
        $this->assertContains(AttributeTargetTypeEnum::CONSTANT, $cases);
    }

    /**
     * Test enum values are strings
     */
    public function testEnumValues(): void
    {
        $this->assertSame('class', AttributeTargetTypeEnum::CLASS_TYPE->value);
        $this->assertSame('method', AttributeTargetTypeEnum::METHOD->value);
        $this->assertSame('property', AttributeTargetTypeEnum::PROPERTY->value);
        $this->assertSame('parameter', AttributeTargetTypeEnum::PARAMETER->value);
        $this->assertSame('constant', AttributeTargetTypeEnum::CONSTANT->value);
    }

    /**
     * Test enum from() method
     */
    public function testFromValue(): void
    {
        $this->assertSame(AttributeTargetTypeEnum::CLASS_TYPE, AttributeTargetTypeEnum::from('class'));
        $this->assertSame(AttributeTargetTypeEnum::METHOD, AttributeTargetTypeEnum::from('method'));
        $this->assertSame(AttributeTargetTypeEnum::PROPERTY, AttributeTargetTypeEnum::from('property'));
        $this->assertSame(AttributeTargetTypeEnum::PARAMETER, AttributeTargetTypeEnum::from('parameter'));
        $this->assertSame(AttributeTargetTypeEnum::CONSTANT, AttributeTargetTypeEnum::from('constant'));
    }

    /**
     * Test enum tryFrom() with invalid value
     */
    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(AttributeTargetTypeEnum::tryFrom('invalid'));
    }
}
