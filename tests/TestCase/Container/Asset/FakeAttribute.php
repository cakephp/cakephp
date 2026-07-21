<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

use Attribute;
use Cake\Container\Attribute\AttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class FakeAttribute implements AttributeInterface
{
    public function __construct(protected string $value)
    {
    }

    public function resolve(): mixed
    {
        return strtoupper($this->value);
    }
}
