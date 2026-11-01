<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class UnannotatedService
{
    public function __construct(
        public readonly Bar $dependency,
    ) {
    }
}
