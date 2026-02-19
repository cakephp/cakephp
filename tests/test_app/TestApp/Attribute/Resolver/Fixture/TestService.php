<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestInject;

class TestService
{
    public function method(
        #[TestInject(service: 'logger')]
        $param1,
        #[TestInject(service: 'cache')]
        $param2,
    ): void {
    }
}
