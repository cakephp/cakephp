<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestClassReference;
use TestApp\Attribute\Resolver\TestRoute;

/**
 * Declaring class preceded by a reserved keyword used as a named argument.
 */
// phpcs:ignore SlevomatCodingStandard.Functions.NamedArgumentSpacing.WhitespaceBeforeColon -- Exercise token lookahead.
#[TestClassReference(actions: ['index'], name: 'list', class/* Named argument */: TestRoute:: /* Class reference */ class)]
class TestNamedClassArgument
{
}
