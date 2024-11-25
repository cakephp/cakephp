<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility\Cast;

use Cake\TestSuite\TestCase;
use Cake\Utility\Cast\BoolCast;
use Cake\Utility\Cast\IntCast;
use Cake\Utility\Cast\StringCast;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class BoolCastTest extends TestCase
{
    #[DataProvider('castProvider')]
    public function testTryFrom(mixed $value, ?bool $expected): void
    {
        $result = BoolCast::tryFrom($value);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('castStrictProvider')]
    public function testTryFromStrict(mixed $value, ?bool $expected): void
    {
        $result = BoolCast::tryFrom($value, true);
        $this->assertSame($expected, $result);
    }

    public function testFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoolCast::from([]);
    }

    #[DataProvider('idempotentProvider')]
    public function testIdempotent(bool $value): void
    {
        $expected = $value;

        $value = BoolCast::from($value);
        $value = IntCast::from($value);
        $value = StringCast::from($value);
        $value = IntCast::from($value);
        $value = BoolCast::from($value);

        $this->assertSame($expected, $value);
    }

    public static function castProvider(): array
    {
        return [
            // empty string input
            '(string) empty' => ['', false],
            // non-empty string input with trailing whitespace
            '(string) not empty' => ['non-empty ', true],
            'null' => [null, false],
            'bool false' => [false, false],
            'bool true' => [true, true],
            'int' => [2, true],
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
            'bool false' => [false, false],
            'bool true' => [true, true],
            'string 1' => ['1', true],
            'string 2' => ['2', null],
        ];
    }

    public static function idempotentProvider(): array
    {
        return [
           [true],
           [false],
        ];
    }
}
