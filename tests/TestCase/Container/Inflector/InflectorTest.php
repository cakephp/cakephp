<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Inflector;

use Cake\Container\Container;
use Cake\Container\Inflector\Inflector;
use Cake\Test\TestCase\Container\Asset\Bar;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class InflectorTest extends TestCase
{
    public function testInflectorSetsExpectedMethodCalls(): void
    {
        $container = new Container();
        $inflector = (new Inflector('Type'))->setContainer($container);

        $inflector->invokeMethod('method1', ['arg1']);

        $inflector->invokeMethods([
            'method2' => ['arg1'],
            'method3' => ['arg1'],
        ]);

        $methods = (new ReflectionClass($inflector))->getProperty('methods');

        self::assertSame($methods->getValue($inflector), [
            'method1' => ['arg1'],
            'method2' => ['arg1'],
            'method3' => ['arg1'],
        ]);
    }

    public function testInflectorSetsExpectedProperties(): void
    {
        $container = new Container();
        $inflector = (new Inflector('Type'))->setContainer($container);

        $inflector->setProperty('property1', 'value');

        $inflector->setProperties([
            'property2' => 'value',
            'property3' => 'value',
        ]);

        $properties = (new ReflectionClass($inflector))->getProperty('properties');

        self::assertSame($properties->getValue($inflector), [
            'property1' => 'value',
            'property2' => 'value',
            'property3' => 'value',
        ]);
    }

    public function testInflectorInflectsWithProperties(): void
    {
        $container = new Container();

        $bar = new class {
        };

        $container->add(Bar::class, $bar);

        $inflector = (new Inflector('Type'))
            ->setContainer($container)
            ->setProperty('bar', Bar::class);

        $foo = new class {
            public $bar;
        };

        $inflector->inflect($foo);

        self::assertSame($bar, $foo->bar);
    }

    public function testInflectorInflectsWithMethodCall(): void
    {
        $container = new Container();

        $bar = new class {
        };

        $container->add(Bar::class, $bar);

        $inflector = (new Inflector('Type'))
            ->setContainer($container)
            ->invokeMethod('setBar', [Bar::class]);

        $foo = new class {
            public $bar;
            public function setBar($bar): void
            {
                $this->bar = $bar;
            }
        };

        $inflector->inflect($foo);
        self::assertSame($bar, $foo->bar);
    }

    public function testInflectorInflectsWithCallback(): void
    {
        $foo = new class {
            public $bar;
            public function setBar($bar): void
            {
                $this->bar = $bar;
            }
        };

        $bar = new class {
        };

        $inflector = new Inflector('Type', function ($object) use ($bar): void {
            $object->setBar($bar);
        });

        $inflector->inflect($foo);
        self::assertSame($bar, $foo->bar);
    }
}
