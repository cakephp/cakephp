<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Inflector;

use Cake\Container\Container;
use Cake\Container\ContainerAwareInterface;
use Cake\Container\Inflector\InflectorAggregate;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class InflectorAggregateTest extends TestCase
{
    public function testAggregateAddsInflector(): void
    {
        $aggregate = new InflectorAggregate();
        $inflector = $aggregate->add('Some\Type');
        self::assertSame('Some\Type', $inflector->getType());
    }

    public function testAggregateAddsAndIteratesMultipleInflectors(): void
    {
        $aggregate = new InflectorAggregate();
        $inflectors = [];

        for ($i = 0; $i < 10; $i++) {
            $inflectors[] = $aggregate->add('Some\Type' . $i);
        }

        foreach ($aggregate->getIterator() as $key => $inflector) {
            self::assertSame($inflectors[$key], $inflector);
        }
    }

    public function testAggregateIteratesAndInflectsOnObject(): void
    {
        $aggregate = new InflectorAggregate();
        $containerAware = $this->getMockBuilder(ContainerAwareInterface::class)->getMock();
        $container = $this->getMockBuilder(Container::class)->getMock();
        $containerAware->expects(self::once())->method('setContainer')->with(self::equalTo($container));
        $aggregate->add(ContainerAwareInterface::class)->invokeMethod('setContainer', [$container]);
        $aggregate->add('Ignored\Type');
        $aggregate->setContainer($container);
        $aggregate->inflect($containerAware);
    }

    public function testNoInflectionIsAttemptedOnNonObjects(): void
    {
        $container = new Container();

        $types = [
            'my-array' => [1, 2, 3],
            'my-number' => 123,
            'my-string' => 'foo bar',
            'my-generated-array' => [DateTimeZone::class, 'listIdentifiers'],
            'my-generated-number' => 'time',
            'my-generated-string' => function (): string {
                return 'blahblahblah';
            },
        ];

        foreach ($types as $alias => $concrete) {
            $container->add($alias, $concrete);

            if (is_callable($concrete)) {
                $concrete = $concrete();
            }

            self::assertSame($container->get($alias), $concrete);
        }
    }
}
