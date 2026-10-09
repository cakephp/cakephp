<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestClassReference;
use TestApp\Attribute\Resolver\TestRoute;

/**
 * Declaring class preceded by a class reference and another named argument.
 */
#[TestClassReference(actions: ['index'], routeClass: TestRoute::class, name: 'list')]
class TestClassReferenceWithFollowup
{
}
