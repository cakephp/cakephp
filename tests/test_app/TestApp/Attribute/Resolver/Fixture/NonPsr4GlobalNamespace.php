<?php
declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ClassFileName.NoMatch
use TestApp\Attribute\TestColumn;
use TestApp\Attribute\TestInclude;

#[TestInclude]
class GlobalNamespaceClass
{
    #[TestColumn(type: 'string')]
    public string $property;
}
