<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestRoute;

#[TestRoute('/test')]
class TestController
{
    #[TestRoute('/public')]
    public function publicMethod(): void
    {
    }

    #[TestRoute('/protected')]
    protected function protectedMethod(): void
    {
    }

    #[TestRoute('/private')]
    private function privateMethod(): void
    {
    }

    #[TestRoute('/static')]
    public static function staticMethod(): void
    {
    }
}
