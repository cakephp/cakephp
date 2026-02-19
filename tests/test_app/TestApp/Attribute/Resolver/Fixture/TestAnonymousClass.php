<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use Stringable;
use TestApp\Attribute\Resolver\TestColumn;
use TestApp\Attribute\Resolver\TestExclude;
use TestApp\Attribute\Resolver\TestInternal;
use TestApp\Attribute\Resolver\TestRoute;

#[TestRoute(path: '/factory')]
class TestAnonymousClass
{
    #[TestRoute(path: '/create')]
    public function create(): object
    {
        // This anonymous class should NOT be detected by the parser
        return new #[TestExclude]
        class {
            #[TestColumn(type: 'string')]
            public string $prop;
        };
    }

    #[TestRoute(path: '/create-another')]
    public function createAnother(): object
    {
        $instance = new #[TestInternal]
        class implements Stringable {
            public function __toString(): string
            {
                return 'test';
            }
        };

        return $instance;
    }
}
