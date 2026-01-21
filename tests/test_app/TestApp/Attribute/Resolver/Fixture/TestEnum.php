<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestRoute;

#[TestRoute(path: '/enum')]
enum TestEnum: string
{
    #[TestRoute(path: '/value1')]
    case VALUE1 = 'value1';

    #[TestRoute(path: '/value2')]
    case VALUE2 = 'value2';
}
