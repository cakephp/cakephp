<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility\Cast;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Cast\DateTimeCast;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class DateTimeCastTest extends TestCase
{
    #[DataProvider('castProvider')]
    public function testTryFrom(mixed $value, ?DateTime $expected): void
    {
        $result = DateTimeCast::tryFrom($value);
        $this->assertEquals($expected, $result);
    }

    public function testFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DateTimeCast::from([]);
    }

    public static function castProvider(): array
    {
        return [
            'date' => ['2015-11-12', new DateTime('2015-11-12')],
            'timestamp as int' => [1732502367, new DateTime('2024-11-25T02:39:27.000000+0000')],
            'timestamp' => ['1732502367', new DateTime('2024-11-25T02:39:27.000000+0000')],
            'null' => [null, null],
        ];
    }
}
