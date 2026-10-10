<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

use SensitiveParameter;

class PlainMarkerService
{
    public function __construct(
        #[SensitiveParameter]
        public readonly Bar $bar,
    ) {
    }
}
