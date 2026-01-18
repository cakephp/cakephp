<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver;

use Attribute;

#[Attribute]
class TestRoute
{
    public function __construct(public string $path)
    {
    }
}
