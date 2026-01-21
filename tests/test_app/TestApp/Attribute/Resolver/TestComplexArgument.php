<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver;

use Attribute;

#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
class TestComplexArgument
{
    public function __construct(
        public mixed $value = null,
        public mixed $object = null,
        public mixed $enum = null,
        public mixed $constant = null,
    ) {
    }
}
