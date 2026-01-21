<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestRoute;

#[TestRoute(path: '/interface')]
interface TestInterface
{
    #[TestRoute(path: '/method')]
    public function interfaceMethod(): void;
}
