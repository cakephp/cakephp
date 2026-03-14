<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver;

enum TestPriorityEnum: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    case CRITICAL = 4;
}
