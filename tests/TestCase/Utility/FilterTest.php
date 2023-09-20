<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.0.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Utility;

use Cake\ORM\Entity;
use Cake\Utility\Filter;
use PHPUnit\Framework\TestCase;
use stdClass;

class FilterTest extends TestCase
{
    /**
     * @dataProvider toStringProvider
     */
    public function testToString(mixed $rawValue, ?string $expected): void
    {
        $this->assertSame($expected, Filter::toString($rawValue));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toStringProvider(): array
    {
        return [
            // input like string
            '(string) empty' => ['', ''],
            '(string) space' => [' ', ' '],
            '(string) dash' => ['-', '-'],
            '(string) zero' => ['0', '0'],
            '(string) number' => ['55', '55'],
            '(string) partially2 number' => ['5x', '5x'],
            // input like int
            '(int) number' => [55, '55'],
            '(int) negative number' => [-5, '-5'],
            '(int) PHP_INT_MAX + 2' => [9223372036854775809, '9223372036854775808'], //is float: see IEEE 754
            '(int) PHP_INT_MAX + 1' => [9223372036854775808, '9223372036854775808'], //is float: see IEEE 754
            '(int) PHP_INT_MAX + 0' => [9223372036854775807, '9223372036854775807'],
            '(int) PHP_INT_MAX - 1' => [9223372036854775806, '9223372036854775806'],
            '(int) PHP_INT_MIN + 1' => [-9223372036854775807, '-9223372036854775807'],
            '(int) PHP_INT_MIN + 0' => [-9223372036854775808, '-9223372036854775808'],
            '(int) PHP_INT_MIN - 1' => [-9223372036854775809, '-9223372036854775808'], //is float: see IEEE 754
            '(int) PHP_INT_MIN - 2' => [-9223372036854775810, '-9223372036854775808'], //is float: see IEEE 754
            // input like float
            '(float) zero' => [0.0, '0'],
            '(float) positive' => [5.5, '5.5'],
            '(float) round' => [5.0, '5'],
            '(float) negative' => [-5.5, '-5.5'],
            '(float) round negative' => [-5.0, '-5'],
            '(float) small' => [0.000000000003, '0.000000000003'],
            '(float) small2' => [64321.0000003, '64321.0000003'],
            '(float) fractions' => [-9223372036778.2233, '-9223372036778.223'], //is float: see IEEE 754
            '(float) NaN' => [acos(8), null],
            '(float) INF' => [INF, null],
            '(float) -INF' => [-INF, null],
            // boolean input types
            '(bool) true' => [true, '1'],
            '(bool) false' => [false, '0'],
            // other input types
            '(other) null' => [null, null],
            '(other) empty-array' => [[], null],
            '(other) int-array' => [[5], null],
            '(other) string-array' => [['5'], null],
            '(other) simple object' => [new stdClass(), null],
            '(other) Stringable object' => [new Entity(), '[]'],
        ];
    }

    /**
     * @dataProvider toIntProvider
     */
    public function testToInt(mixed $rawValue, null|int $expected): void
    {
        $this->assertSame($expected, Filter::toInt($rawValue));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toIntProvider(): array
    {
        return [
            // string input types
            '(string) empty' => ['', null],
            '(string) space' => [' ', null],
            '(string) null' => ['null', null],
            '(string) dash' => ['-', null],
            '(string) ctz' => ['čťž', null],
            '(string) hex' => ['0x539', null],
            '(string) binary' => ['0b10100111001', null],
            '(string) scientific e' => ['1.2e+2', null],
            '(string) scientific E' => ['1.2E+2', null],
            '(string) octal old' => ['0123', null],
            '(string) octal new' => ['0o123', null],
            '(string) decimal php74' => ['1_234_567', null],
            '(string) zero' => ['0', 0],
            '(string) number' => ['55', 55],
            '(string) number_space_before' => [' 55', 55],
            '(string) number_space_after' => ['55 ', 55],
            '(string) negative number' => ['-5', -5],
            '(string) float round' => ['5.0', null],
            '(string) float round negative' => ['-5.0', null],
            '(string) float real' => ['5.1', null],
            '(string) float round slovak' => ['5,0', null],
            '(string) money' => ['5 €', null],
            '(string) PHP_INT_MAX + 1' => ['9223372036854775808', null],
            '(string) PHP_INT_MAX + 0' => ['9223372036854775807', 9223372036854775807],
            '(string) PHP_INT_MAX - 1' => ['9223372036854775806', 9223372036854775806],
            '(string) PHP_INT_MIN + 1' => ['-9223372036854775807', -9223372036854775807],
            '(string) PHP_INT_MIN + 0' => ['-9223372036854775808', null],
            '(string) PHP_INT_MIN - 1' => ['-9223372036854775809', null],
            '(string) string' => ['f', null],
            '(string) partially1 number' => ['5 5', null],
            '(string) partially2 number' => ['5x', null],
            '(string) partially3 number' => ['x4', null],
            '(string) double dot' => ['5.1.0', null],
            // int input types
            '(int) number' => [55, 55],
            '(int) negative number' => [-5, -5],
            '(int) PHP_INT_MAX + 1' => [9223372036854775808, null],
            '(int) PHP_INT_MAX + 0' => [9223372036854775807, 9223372036854775807],
            '(int) PHP_INT_MAX - 1' => [9223372036854775806, 9223372036854775806],
            '(int) PHP_INT_MIN + 1' => [-9223372036854775807, -9223372036854775807],
            // PHP_INT_MIN is float -> PHP inconsistency https://bugs.php.net/bug.php?id=53934
            '(int) PHP_INT_MIN + 0' => [-9223372036854775808, null],
            '(int) PHP_INT_MIN - 1' => [-9223372036854775809, null],
            // float input types
            '(float) zero' => [0.0, 0],
            '(float) positive' => [5.5, 5],
            '(float) round' => [5.0, 5],
            '(float) negative' => [-5.5, -5],
            '(float) round negative' => [-5.0, -5],
            '(float) PHP_INT_MAX + 1' => [9223372036854775808.0, null],
            '(float) PHP_INT_MAX + 0' => [9223372036854775807.0, null],
            '(float) PHP_INT_MAX - 1' => [9223372036854775806.0, null],
            '(float) PHP_INT_MIN + 1' => [-9223372036854775807.0, null],
            '(float) PHP_INT_MIN + 0' => [-9223372036854775808.0, null],
            '(float) PHP_INT_MIN - 1' => [-9223372036854775809.0, null],
            '(float) 2^53 + 2' => [9007199254740994.0, null],
            '(float) 2^53 + 1' => [9007199254740993.0, null],
            '(float) 2^53 + 0' => [9007199254740992.0, null],
            '(float) 2^53 - 1' => [9007199254740991.0, 9007199254740991],
            '(float) 2^53 - 2' => [9007199254740990.0, 9007199254740990],
            '(float) -(2^53) + 2' => [-9007199254740990.0, -9007199254740990],
            '(float) -(2^53) + 1' => [-9007199254740991.0, -9007199254740991],
            '(float) -(2^53) + 0' => [-9007199254740992.0, null],
            '(float) -(2^53) - 1' => [-9007199254740992.0, null],
            '(float) -(2^53) - 2' => [-9007199254740994.0, null],
            '(float) NaN' => [acos(8), null],
            '(float) INF' => [INF, null],
            '(float) -INF' => [-INF, null],
            // boolean input types
            '(bool) true' => [true, 1],
            '(bool) false' => [false, 0],
            // other input types
            '(other) null' => [null, null],
            '(other) empty-array' => [[], null],
            '(other) int-array' => [[5], null],
            '(other) string-array' => [['5'], null],
            '(other) simple object' => [new stdClass(), null],
        ];
    }

    /**
     * @dataProvider toBoolProvider
     */
    public function testToBool(mixed $rawValue, ?bool $expected): void
    {
        $this->assertSame($expected, Filter::toBool($rawValue));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toBoolProvider(): array
    {
        return [
            // string input types
            '(string) empty string' => ['', null],
            '(string) space' => [' ', null],
            '(string) some word' => ['abc', null],
            '(string) double 0' => ['00', null],
            '(string) single 0' => ['0', false],
            '(string) false' => ['false', null],
            '(string) double 1' => ['11', null],
            '(string) single 1' => ['1', true],
            '(string) true-string' => ['true', null],
            // int input types
            '(int) 0' => [0, false],
            '(int) 1' => [1, true],
            '(int) -1' => [-1, null],
            '(int) 55' => [55, null],
            '(int) negative number' => [-5, null],
            // float input types
            '(float) positive' => [5.5, null],
            '(float) round' => [5.0, null],
            '(float) 0.0' => [0.0, false],
            '(float) 1.0' => [1.0, true],
            '(float) NaN' => [acos(8), null],
            '(float) INF' => [INF, null],
            '(float) -INF' => [-INF, null],
            // boolean input types
            '(bool) true' => [true, true],
            '(bool) false' => [false, false],
            // other input types
            '(other) null' => [null, null],
            '(other) empty-array' => [[], null],
            '(other) int-array' => [[5], null],
            '(other) string-array' => [['5'], null],
            '(other) simple object' => [new stdClass(), null],
        ];
    }
}
