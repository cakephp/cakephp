<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility\Cast;

use Cake\TestSuite\TestCase;
use Cake\Utility\Cast\StringCast;
use PHPUnit\Framework\Attributes\DataProvider;

class StringCastTest extends TestCase
{
    #[DataProvider('castProvider')]
    public function testTryFrom(mixed $value, ?string $expected): void
    {
        $result = StringCast::tryFrom($value);
        $this->assertSame($expected, $result);
    }

    public static function castProvider(): array
    {
        return [
            // empty string input
            '(string) empty' => ['', null],
            // non-empty string input with trailing whitespace
            '(string) not empty' => ['non-empty ', 'non-empty '],
            'null' => [null, null],
            'bool false' => [false, '0'],
            'bool true' => [true, '1'],
            'int' => [2, '2'],
        ];
    }
}
