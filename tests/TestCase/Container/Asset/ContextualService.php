<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class ContextualService
{
    public function __construct(
        #[ContextualValueAttribute('resolved-value')]
        public readonly string $injected,
        public readonly Bar $dependency,
    ) {
    }
}
