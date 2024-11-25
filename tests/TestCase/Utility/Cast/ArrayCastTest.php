<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility\Cast;

use Cake\TestSuite\TestCase;
use Cake\Utility\Cast\ArrayCast;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class ArrayCastTest extends TestCase
{
    #[DataProvider('castProvider')]
    public function testTryFrom(mixed $value, ?array $expected): void
    {
        $result = ArrayCast::tryFrom($value);
        $this->assertSame($expected, $result);
    }

    public function testFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ArrayCast::from(new stdClass());
    }

    public static function castProvider(): array
    {
        return [
            // empty string input
            '(string) empty' => ['', []],
            // non-empty string input with trailing whitespace
            '(string) not empty' => ['non-empty ', ['non-empty ']],
            'null' => [null, []],
            'bool false' => [false, []],
            'bool true' => [true, [true]],
            'int' => [2, [2]],
            'float' => [2.2, [2.2]],
            'array' => [['x'], ['x']],
        ];
    }
}
