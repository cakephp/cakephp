<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver;

use Attribute;

#[Attribute]
class TestColumn
{
    public function __construct(public string $type)
    {
    }
}
