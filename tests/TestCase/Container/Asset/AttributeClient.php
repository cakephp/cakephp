<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class AttributeClient
{
    public function __construct(
        #[FakeAttribute('resolved-value')]
        public readonly string $value,
    ) {
    }
}
