<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class ContainerAwareValueService
{
    public function __construct(
        #[ContainerAwareValueAttribute]
        public readonly Bar $bar,
    ) {
    }
}
