<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class ContextualAwareService
{
    public function __construct(
        #[ContextualContainerAwareAttribute]
        public readonly Bar $bar,
    ) {
    }
}
