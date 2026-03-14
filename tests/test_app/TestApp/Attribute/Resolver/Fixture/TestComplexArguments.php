<?php
declare(strict_types=1);

namespace TestApp\Attribute\Resolver\Fixture;

use TestApp\Attribute\Resolver\TestComplexArgument;
use TestApp\Attribute\Resolver\TestPriorityEnum;
use TestApp\Attribute\Resolver\ValueObject\TestConfig;

class TestComplexArguments
{
    public const DEFAULT_TIMEOUT = 30;
    public const MAX_RETRIES = 3;

    #[TestComplexArgument(
        value: 'simple_string',
        object: new TestConfig('database', ['host' => 'localhost', 'port' => 3306]),
        enum: TestPriorityEnum::HIGH,
        constant: self::DEFAULT_TIMEOUT,
    )]
    public function methodWithComplexAttributes(): void
    {
    }

    #[TestComplexArgument(
        object: new TestConfig('cache', ['driver' => 'redis']),
        enum: TestPriorityEnum::CRITICAL,
    )]
    public string $propertyWithObject;

    #[TestComplexArgument(
        value: 'nested',
        object: new TestConfig(
            name: 'nested_config',
            options: [
                'nested' => new TestConfig('deep', ['level' => 2]),
                'timeout' => self::MAX_RETRIES,
            ],
        ),
    )]
    public function nestedObjects(): void
    {
    }

    #[TestComplexArgument(constant: self::MAX_RETRIES)]
    #[TestComplexArgument(enum: TestPriorityEnum::LOW)]
    #[TestComplexArgument(object: new TestConfig('multi', []))]
    public function multipleAttributes(): void
    {
    }
}
