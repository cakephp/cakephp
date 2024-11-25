<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility\Cast;

use Cake\TestSuite\TestCase;
use Cake\Utility\Cast\FloatCast;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class FloatCastTest extends TestCase
{
    #[DataProvider('castProvider')]
    public function testTryFrom(mixed $value, ?float $expected): void
    {
        $result = FloatCast::tryFrom($value);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('castStrictProvider')]
    public function testTryFromStrict(mixed $value, ?float $expected): void
    {
        $result = FloatCast::tryFrom($value, true);
        $this->assertSame($expected, $result);
    }

    public function testFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FloatCast::from([]);
    }

    public static function castProvider(): array
    {
        return [
            // empty string input
            '(string) empty' => ['', null],
            // non-empty string input with trailing whitespace
            '(string) not empty' => ['non-empty ', null],
            'null' => [null, null],
            'bool false' => [false, null],
            'bool true' => [true, 1.0],
            'int' => [2, 2.0],
            'float' => [2.2, 2.2],
        ];
    }

    public static function castStrictProvider(): array
    {
        return [
            // empty string input
            '(string) empty' => ['', null],
            // non-empty string input with trailing whitespace
            '(string) not empty' => ['non-empty ', null],
            'null' => [null, null],
            'bool false' => [false, null],
            'bool true' => [true, 1.0],
            'int' => [2, 2.0],
            'float' => [2.2, 2.2],
        ];
    }
}
