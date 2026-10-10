<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestClassReference;
use TestApp\Attribute\Resolver\TestRoute;
use TestApp\Attribute\Resolver\TestStatus;

/**
 * Declaring class preceded by an array of class references.
 */
#[TestClassReference([TestRoute::class, TestStatus::class])]
class TestClassReferences
{
}
