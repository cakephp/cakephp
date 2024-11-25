<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility\Cast;

use Cake\TestSuite\TestCase;
use Cake\Utility\Cast\IntCast;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class IntCastTest extends TestCase
{
    #[DataProvider('castProvider')]
    public function testTryFromX(mixed $value, ?int $expected): void
    {
        $result = IntCast::tryFrom($value);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('castStrictProvider')]
    public function testTryFromStrict(mixed $value, ?int $expected): void
    {
        $result = IntCast::tryFrom($value, true);
        $this->assertSame($expected, $result);
    }

    public function testFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        IntCast::from([]);
    }

    public static function castProvider(): array
    {
        return [
            // empty string input
            '(string) empty' => ['', null],
            // non-empty string input with trailing whitespace
            '(string) not empty' => ['non-empty ', null],
            'null' => [null, null],
            'bool false' => [false, 0],
            'bool true' => [true, 1],
            'int' => [2, 2],
            'float' => [2.2, null],
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
            'bool false' => [false, 0],
            'bool true' => [true, 1],
            'int' => [2, 2],
            'float' => [2.2, null],
        ];
    }
}
