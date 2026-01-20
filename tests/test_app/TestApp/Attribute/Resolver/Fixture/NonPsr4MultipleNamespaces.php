<?php
declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.Namespaces.NamespaceDeclaration.DisallowedBracketedSyntax, SlevomatCodingStandard.Namespaces.UseSpacing, PSR1.Classes.ClassDeclaration.MultipleClasses, Squiz.Classes.ClassFileName.NoMatch
namespace TestApp\Attribute\Resolver\Fixture {
    use TestApp\Attribute\Resolver\TestRoute;
    #[TestRoute(path: '/first')]
    class FirstClass
    {
    }
}

namespace TestApp\OtherNamespace {
    use TestApp\Attribute\Resolver\TestRoute;

    #[TestRoute(path: '/second')]
    class SecondClass
    {
    }
}

namespace {
    use TestApp\Attribute\Resolver\TestInclude;

    #[TestInclude]
    class ThirdClass
    {
    }
}
