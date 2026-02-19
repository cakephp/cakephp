<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestStatus;

class TestConstants
{
    #[TestStatus(label: 'Active')]
    public const ACTIVE = 1;

    #[TestStatus(label: 'Inactive')]
    public const INACTIVE = 0;
}
