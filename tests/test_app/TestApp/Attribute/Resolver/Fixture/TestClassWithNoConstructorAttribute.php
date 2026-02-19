<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestAttributeWithoutConstructor;

#[TestAttributeWithoutConstructor]
class TestClassWithNoConstructorAttribute
{
    #[TestAttributeWithoutConstructor]
    public function methodWithAttribute(): void
    {
    }
}
