<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Argument;

use Cake\Container\Argument\Literal\ArrayArgument;
use Cake\Container\Argument\Literal\BooleanArgument;
use Cake\Container\Argument\Literal\CallableArgument;
use Cake\Container\Argument\Literal\FloatArgument;
use Cake\Container\Argument\Literal\IntegerArgument;
use Cake\Container\Argument\Literal\ObjectArgument;
use Cake\Container\Argument\Literal\StringArgument;
use Cake\Container\Argument\LiteralArgument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TypedArgumentTest extends TestCase
{
    public function testLiteralArgumentSetsAndGetsArgument(): void
    {
        $arguments = [
            ArrayArgument::class => [],
            BooleanArgument::class => true,
            CallableArgument::class => function (): void {
            },
            FloatArgument::class => 1.23,
            IntegerArgument::class => 1,
            ObjectArgument::class => new class {
            },
            StringArgument::class => 'string',
        ];

        foreach ($arguments as $type => $expected) {
            $argument = new $type($expected);
            self::assertSame($expected, $argument->getValue());
        }
    }

    public function testLiteralArgumentThrowsWithWrongArgumentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LiteralArgument(LiteralArgument::TYPE_BOOL, 'blah');
    }
}
