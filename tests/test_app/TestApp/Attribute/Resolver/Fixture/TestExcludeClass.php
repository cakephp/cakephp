<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestExclude;
use TestApp\Attribute\Resolver\TestInclude;

#[TestInclude]
#[TestExclude]
class TestExcludeClass
{
}
