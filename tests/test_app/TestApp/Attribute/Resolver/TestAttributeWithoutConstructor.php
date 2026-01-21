<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class TestAttributeWithoutConstructor
{
    // No constructor defined - tests fallback path in getAttributeConstructor
}
