<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\ValueObject;

class TestConfig
{
    public function __construct(
        public string $name,
        public array $options = [],
    ) {
    }
}
