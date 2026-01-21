<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestRoute;

#[TestRoute(path: '/trait')]
trait TestTrait
{
    #[TestRoute(path: '/trait-method')]
    public function traitMethod(): void
    {
    }
}
