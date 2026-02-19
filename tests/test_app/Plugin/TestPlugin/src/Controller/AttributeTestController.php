<?php
declare(strict_types=1);

namespace TestPlugin\Controller;

use TestApp\Attribute\Resolver\TestRoute;

#[TestRoute('/plugin')]
class AttributeTestController
{
    #[TestRoute('/index')]
    public function index(): void
    {
    }

    #[TestRoute('/view')]
    public function view(): void
    {
    }
}
