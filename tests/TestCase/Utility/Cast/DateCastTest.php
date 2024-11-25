<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility\Cast;

use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Cake\Utility\Cast\DateCast;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class DateCastTest extends TestCase
{
    #[DataProvider('castProvider')]
    public function testTryFrom(mixed $value, ?Date $expected): void
    {
        $result = DateCast::tryFrom($value);
        $this->assertEquals($expected, $result);
    }

    #[DataProvider('castStrictProvider')]
    public function testTryFromStrict(mixed $value, ?Date $expected): void
    {
        $result = DateCast::tryFrom($value, true);
        $this->assertEquals($expected, $result);
    }

    public function testFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DateCast::from([]);
    }

    public static function castProvider(): array
    {
        return [
            'date' => ['2015-11-12', new Date('2015-11-12')],
            'timestamp as int' => [1732502367, new Date('2024-11-25')],
            'timestamp' => ['1732502367', new Date('2024-11-25')],
            'null' => [null, null],
        ];
    }

    public static function castStrictProvider(): array
    {
        return [
            'date' => ['2015-11-12', new Date('2015-11-12')],
            'timestamp' => [1732502367, new Date('2024-11-25')],
            'null' => [null, null],
        ];
    }
}
