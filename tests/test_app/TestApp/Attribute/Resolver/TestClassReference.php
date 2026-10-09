<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver;

use Attribute;

/**
 * Attribute with class references in positional and named arguments.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class TestClassReference
{
    /**
     * Store routing-style arguments for parser regression tests.
     *
     * @param array<string> $actions Action names or class references
     * @param string|null $routeClass Route class reference
     * @param string|null $name Route name
     * @param string|null $class Class reference passed using a reserved keyword
     */
    public function __construct(
        public array $actions,
        public ?string $routeClass = null,
        public ?string $name = null,
        public ?string $class = null,
    ) {
    }
}
