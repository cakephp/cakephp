<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Definition;

use Cake\Container\Container;
use Cake\Container\Definition\Definition;
use Cake\Container\Definition\DefinitionAggregate;
use Cake\Container\Definition\DefinitionInterface;
use Cake\Container\Exception\NotFoundException;
use Cake\Test\TestCase\Container\Asset\Foo;
use PHPUnit\Framework\TestCase;

class DefinitionAggregateTest extends TestCase
{
    public function testAggregateAddsDefinition(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $definition = $this->getMockBuilder(DefinitionInterface::class)->getMock();

        $definition
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias'))
            ->willReturnSelf();

        $aggregate = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', $definition);

        self::assertInstanceOf(DefinitionInterface::class, $definition);
    }

    public function testAggregateCreatesDefinition(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', Foo::class);
        self::assertSame('alias', $definition->getAlias());
    }

    public function testAggregateHasDefinition(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new DefinitionAggregate())->setContainer($container);
        $aggregate->add('alias', Foo::class);
        self::assertTrue($aggregate->has('alias'));
        self::assertFalse($aggregate->has('nope'));
    }

    public function testAggregateAddsAndIteratesMultipleDefinitions(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = (new DefinitionAggregate())->setContainer($container);

        $definitions = [];

        for ($i = 0; $i < 10; $i++) {
            $definitions[] = $aggregate->add('alias' . $i, Foo::class);
        }

        foreach ($aggregate->getIterator() as $key => $definition) {
            self::assertSame($definitions[$key], $definition);
        }
    }

    public function testAggregateIteratesAndResolvesDefinition(): void
    {
        $aggregate = new DefinitionAggregate();
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias1');

        $definition1
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias1'))
            ->willReturnSelf();

        $definition2
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias2');

        $definition2
            ->expects(self::once())
            ->method('setContainer')
            ->with(self::equalTo($container))
            ->willReturnSelf();

        $definition2
            ->expects(self::once())
            ->method('setShared')
            ->with(self::equalTo(true))
            ->willReturnSelf();

        $definition2
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias2'))
            ->willReturnSelf();

        $definition2
            ->expects(self::once())
            ->method('resolve')
            ->willReturnSelf();

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $resolved = $aggregate->resolve('alias2');
        self::assertSame($definition2, $resolved);
    }

    public function testAggregateCanResolveArrayOfTaggedDefinitions(): void
    {
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects(self::once())
            ->method('setContainer')
            ->with(self::equalTo($container))
            ->willReturnSelf();

        $definition1
            ->expects(self::exactly(2))
            ->method('hasTag')
            ->with(self::equalTo('tag'))
            ->willReturn(true);

        $definition1
            ->expects(self::once())
            ->method('resolve')
            ->willReturn('definition1');

        $definition2
            ->expects(self::once())
            ->method('setContainer')
            ->with(self::equalTo($container))
            ->willReturnSelf();

        $definition2
            ->expects(self::once())
            ->method('hasTag')
            ->with(self::equalTo('tag'))
            ->willReturn(true);

        $definition2
            ->expects(self::once())
            ->method('resolve')
            ->willReturn('definition2');

        $aggregate = new DefinitionAggregate([$definition1, $definition2]);

        $aggregate->setContainer($container);
        self::assertTrue($aggregate->hasTag('tag'));
        $resolved = $aggregate->resolveTagged('tag');
        self::assertSame(['definition1', 'definition2'], $resolved);
    }

    public function testAggregateThrowsExceptionWhenCannotResolve(): void
    {
        $this->expectException(NotFoundException::class);

        $aggregate = new DefinitionAggregate();
        $definition1 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $definition2 = $this->getMockBuilder(DefinitionInterface::class)->getMock();
        $container = $this->getMockBuilder(Container::class)->getMock();

        $definition1
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias1');

        $definition1
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias1'))
            ->willReturnSelf();

        $definition2
            ->expects(self::once())
            ->method('getAlias')
            ->willReturn('alias2');

        $definition2
            ->expects(self::once())
            ->method('setShared')
            ->with(self::equalTo(true))
            ->willReturnSelf();

        $definition2
            ->expects(self::once())
            ->method('setAlias')
            ->with(self::equalTo('alias2'))
            ->willReturnSelf();

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $aggregate->resolveNew('alias');
    }

    public function testDefinitionPrecedingSlash(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = '\\Cake\\Test\\TestCase\\Container\\Asset\\Foo';
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition(Foo::class);

        self::assertInstanceOf(Definition::class, $definition);
    }

    public function testGetPrecedingSlash(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = Foo::class;
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition('\\Cake\\Test\\TestCase\\Container\\Asset\\Foo');

        self::assertInstanceOf(Definition::class, $definition);
    }

    public function testDefinitionPrecedingSlashSingularQuotes(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = '\\Cake\\Test\\TestCase\\Container\\Asset\\Foo';
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition(Foo::class);

        self::assertInstanceOf(Definition::class, $definition);
    }

    public function testGetPrecedingSlashSingularQuote(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = Foo::class;
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition('\\Cake\\Test\\TestCase\\Container\\Asset\\Foo');

        self::assertInstanceOf(Definition::class, $definition);
    }
}
