<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestColumn;

class TestEntity
{
    #[TestColumn(type: 'string')]
    public string $publicProp;

    #[TestColumn(type: 'int')]
    protected int $protectedProp;

    #[TestColumn(type: 'bool')]
    private bool $privateProp;
}
