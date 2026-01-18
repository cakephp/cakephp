<?php
declare(strict_types=1);

namespace TestPlugin\Model;

use TestApp\Attribute\TestValidation;

class AttributeTestEntity
{
    #[TestValidation('required')]
    public string $name;

    #[TestValidation('email')]
    public string $email;
}
