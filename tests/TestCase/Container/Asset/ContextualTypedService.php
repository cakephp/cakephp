<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class ContextualTypedService
{
    public function __construct(
        #[ContextualFromContainerAttribute]
        public readonly Bar $bar,
    ) {
    }
}
