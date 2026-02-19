<?php
declare(strict_types=1);

namespace TestPlugin\Model;

use TestApp\Attribute\Resolver\TestColumn;

class AttributeTestEntity
{
    #[TestColumn('name')]
    public string $name;

    #[TestColumn('email')]
    public string $email;
}
