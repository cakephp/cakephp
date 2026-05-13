<?php
declare(strict_types=1);

namespace TestApp\Event;

class GreeterService
{
    public function greet(string $name): string
    {
        return 'Hello, ' . $name;
    }
}
