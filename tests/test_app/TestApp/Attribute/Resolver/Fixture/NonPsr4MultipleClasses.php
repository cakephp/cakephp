<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

// phpcs:disable SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses, SlevomatCodingStandard.Namespaces.UseSpacing, PSR1.Classes.ClassDeclaration.MultipleClasses, Squiz.Classes.ClassFileName.NoMatch
use TestApp\Attribute\Resolver\TestRoute;
use TestApp\Attribute\Resolver\TestColumn;
use TestApp\Attribute\Resolver\TestStatus;
use TestApp\Attribute\Resolver\TestInclude;

#[TestRoute(path: '/interface')]
interface TestInterface
{
    #[TestRoute(path: '/interface-method')]
    public function interfaceMethod(): void;
}

#[TestInclude]
trait TestTrait
{
    #[TestColumn(type: 'string')]
    public string $traitProperty;

    #[TestRoute(path: '/trait-method')]
    public function traitMethod(): void
    {
    }
}

#[TestStatus(value: 'active')]
enum TestEnum
{
    #[TestStatus(value: 'pending')]
    case PENDING;

    #[TestStatus(value: 'active')]
    case ACTIVE;

    #[TestStatus(value: 'inactive')]
    case INACTIVE;
}
