<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver;

use Attribute;

#[Attribute]
class TestInject
{
    public function __construct(public string $service)
    {
    }
}
