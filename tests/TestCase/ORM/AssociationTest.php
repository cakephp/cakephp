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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\Exception\DatabaseException;
use Cake\ORM\Association;
use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Mockery;
use TestApp\Model\Table\AuthorsTable;
use TestApp\Model\Table\TestTable;
use TestPlugin\Model\Table\CommentsTable;

/**
 * Tests Association class
 */
class AssociationTest extends TestCase
{
    /**
     * @var \TestApp\Model\Table\TestTable
     */
    protected $source;

    /**
     * @var \Cake\ORM\Association&\Mockery\MockInterface
     */
    protected $association;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->source = new TestTable();
        $config = [
            'className' => TestTable::class,
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER',
        ];
        $this->association = Mockery::mock(
            Association::class . '[_options,attachTo,_joinCondition,cascadeDelete,isOwningSide,saveAssociated,eagerLoader,type]',
            ['Foo', $config],
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldIgnoreMissing();
    }

    /**
     * Tests that _options acts as a callback where subclasses can add their own
     * initialization code based on the passed configuration array
     */
    public function testOptionsIsCalled(): void
    {
        $options = ['foo' => 'bar'];
        $this->association->shouldReceive('_options')->once()->with($options);
        $this->association->__construct('Name', $options);
    }

    /**
     * Test that _className property is set to alias when "className" config
     * if not explicitly set.
     */
    public function testSetttingClassNameFromAlias(): void
    {
        /** @var \Cake\ORM\Association&\Mockery\MockInterface $association */
        $association = Mockery::mock(
            Association::class . '[type,eagerLoader,cascadeDelete,isOwningSide,saveAssociated]',
            ['Foo'],
        )
            ->makePartial()
            ->shouldIgnoreMissing();
        $association->shouldReceive('type')
            ->byDefault()
            ->andReturn(Association::MANY_TO_ONE);

        $this->assertSame('Foo', $association->getClassName());
    }

    /**
     * Tests that setClassName() succeeds before the target table is resolved.
     */
    public function testSetClassNameBeforeTarget(): void
    {
        $this->assertSame(TestTable::class, $this->association->getClassName());
        $this->assertSame($this->association, $this->association->setClassName(AuthorsTable::class));
        $this->assertSame(AuthorsTable::class, $this->association->getClassName());
    }

    /**
     * Tests that setClassName() fails after the target table is resolved.
     */
    public function testSetClassNameAfterTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The class name `' . AuthorsTable::class . "` doesn't match the target table class name of");
        $this->association->getTarget();
        $this->association->setClassName(AuthorsTable::class);
    }

    /**
     * Tests that setClassName() fails after the target table is resolved.
     */
    public function testSetClassNameWithShortSyntaxAfterTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The class name `Authors` doesn't match the target table class name of");
        $this->association->getTarget();
        $this->association->setClassName('Authors');
    }

    /**
     * Tests that setClassName() succeeds if name equals target table's class name.
     */
    public function testSetClassNameToTargetClassName(): void
    {
        $className = $this->association->getTarget()::class;
        $this->association->setClassName($className);
        $this->assertSame($className, $this->association->getClassName());
    }

    /**
     * Tests that setClassName() succeeds if the short name resolves to the target table's class name.
     */
    public function testSetClassNameWithShortSyntaxToTargetClassName(): void
    {
        Configure::write('App.namespace', 'TestApp');

        $this->association->setClassName(AuthorsTable::class);
        $className = $this->association->getTarget()::class;
        $this->assertSame(AuthorsTable::class, $className);
        $this->association->setClassName('Authors');
        $this->assertSame('Authors', $this->association->getClassName());
    }

    /**
     * Tests that className() returns the correct (unnormalized) className
     */
    public function testClassNameUnnormalized(): void
    {
        $config = [
            'className' => 'Test',
        ];
        $this->association = Mockery::mock(
            Association::class . '[_options,attachTo,_joinCondition,cascadeDelete,isOwningSide,saveAssociated,eagerLoader,type]',
            ['Foo', $config],
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldIgnoreMissing();
        $this->association->shouldReceive('type')
            ->byDefault()
            ->andReturn(Association::MANY_TO_ONE);

        $this->assertSame('Test', $this->association->getClassName());
    }

    /**
     * Tests that an exception is thrown when invalid target table is fetched
     * from a registry.
     */
    public function testInvalidTableFetchedFromRegistry(): void
    {
        $this->expectException(DatabaseException::class);

        $config = [
            'className' => TestTable::class,
        ];
        $this->association = Mockery::mock(
            Association::class . '[_options,attachTo,_joinCondition,cascadeDelete,isOwningSide,saveAssociated,eagerLoader,type]',
            ['Test', $config],
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldIgnoreMissing();
        $this->association->shouldReceive('type')
            ->byDefault()
            ->andReturn(Association::MANY_TO_ONE);
        $this->association->setSource($this->getTableLocator()->get('Test'));

        $this->association->getTarget();
    }

    /**
     * Tests that a descendant table could be fetched from a registry.
     */
    public function testTargetTableDescendant(): void
    {
        $this->getTableLocator()->get('Test', [
            'className' => TestTable::class,
        ]);
        $className = Table::class;

        $config = [
            'className' => $className,
        ];
        $this->association = Mockery::mock(
            Association::class . '[_options,attachTo,_joinCondition,cascadeDelete,isOwningSide,saveAssociated,eagerLoader,type]',
            ['Test', $config],
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldIgnoreMissing();
        $this->association->shouldReceive('type')
            ->byDefault()
            ->andReturn(Association::MANY_TO_ONE);

        $target = $this->association->getTarget();
        $this->assertInstanceOf($className, $target);
    }

    /**
     * Tests that cascadeCallbacks() returns the correct configured value
     */
    public function testSetCascadeCallbacks(): void
    {
        $this->assertFalse($this->association->getCascadeCallbacks());
        $this->assertSame($this->association, $this->association->setCascadeCallbacks(true));
        $this->assertTrue($this->association->getCascadeCallbacks());
    }

    /**
     * Tests the bindingKey method as a setter/getter
     */
    public function testSetBindingKey(): void
    {
        $this->assertSame($this->association, $this->association->setBindingKey('foo_id'));
        $this->assertSame('foo_id', $this->association->getBindingKey());
    }

    /**
     * Tests the bindingKey() method when called with its defaults
     */
    public function testBindingKeyDefault(): void
    {
        $this->source->setPrimaryKey(['id', 'site_id']);
        $this->association
            ->shouldReceive('isOwningSide')
            ->once()
            ->andReturn(true);
        $result = $this->association->getBindingKey();
        $this->assertEquals(['id', 'site_id'], $result);
    }

    /**
     * Tests the bindingKey() method when the association source is not the
     * owning side
     */
    public function testBindingDefaultNoOwningSide(): void
    {
        $target = new Table();
        $target->setPrimaryKey(['foo', 'site_id']);
        $this->association->setTarget($target);

        $this->association
            ->shouldReceive('isOwningSide')
            ->once()
            ->andReturn(false);
        $result = $this->association->getBindingKey();
        $this->assertEquals(['foo', 'site_id'], $result);
    }

    /**
     * Tests setForeignKey()
     */
    public function testSetForeignKey(): void
    {
        $this->assertSame('a_key', $this->association->getForeignKey());
        $this->assertSame($this->association, $this->association->setForeignKey('another_key'));
        $this->assertSame('another_key', $this->association->getForeignKey());
    }

    /**
     * Tests setConditions()
     */
    public function testSetConditions(): void
    {
        $this->assertEquals(['field' => 'value'], $this->association->getConditions());
        $conds = ['another_key' => 'another value'];
        $this->assertSame($this->association, $this->association->setConditions($conds));
        $this->assertEquals($conds, $this->association->getConditions());
    }

    /**
     * Tests that canBeJoined() returns the correct configured value
     */
    public function testCanBeJoined(): void
    {
        $this->assertTrue($this->association->canBeJoined());
    }

    /**
     * Tests that setTarget()
     */
    public function testSetTarget(): void
    {
        $table = $this->association->getTarget();
        $this->assertInstanceOf(TestTable::class, $table);

        $other = new Table();
        $this->assertSame($this->association, $this->association->setTarget($other));
        $this->assertSame($other, $this->association->getTarget());
    }

    /**
     * Tests that target() returns the correct Table object for plugins
     */
    public function testTargetPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $config = [
            'className' => 'TestPlugin.Comments',
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER',
        ];

        $this->association = Mockery::mock(
            Association::class . '[type,eagerLoader,cascadeDelete,isOwningSide,saveAssociated]',
            ['ThisAssociationName', $config],
        )
            ->makePartial()
            ->shouldIgnoreMissing();
        $this->association->shouldReceive('type')
            ->byDefault()
            ->andReturn(Association::MANY_TO_ONE);

        $table = $this->association->getTarget();
        $this->assertInstanceOf(CommentsTable::class, $table);

        $this->assertTrue(
            $this->getTableLocator()->exists('TestPlugin.ThisAssociationName'),
            'The association class will use this registry key',
        );
        $this->assertFalse($this->getTableLocator()->exists('TestPlugin.Comments'), 'The association class will NOT use this key');
        $this->assertFalse($this->getTableLocator()->exists('Comments'), 'Should also not be set');
        $this->assertFalse($this->getTableLocator()->exists('ThisAssociationName'), 'Should also not be set');

        $plugin = $this->getTableLocator()->get('TestPlugin.ThisAssociationName');
        $this->assertSame($table, $plugin, 'Should be an instance of TestPlugin.Comments');
        $this->assertSame('TestPlugin.ThisAssociationName', $table->getRegistryAlias());
        $this->assertSame('comments', $table->getTable());
        $this->assertSame('ThisAssociationName', $table->getAlias());
        $this->clearPlugins();
    }

    /**
     * Tests that source() returns the correct Table object
     */
    public function testSetSource(): void
    {
        $table = $this->association->getSource();
        $this->assertSame($this->source, $table);

        $other = new Table();
        $this->assertSame($this->association, $this->association->setSource($other));
        $this->assertSame($other, $this->association->getSource());
    }

    /**
     * Tests setJoinType method
     */
    public function testSetJoinType(): void
    {
        $this->assertSame('INNER', $this->association->getJoinType());
        $this->assertSame($this->association, $this->association->setJoinType('LEFT'));
        $this->assertSame('LEFT', $this->association->getJoinType());
    }

    /**
     * Tests property method
     */
    public function testSetProperty(): void
    {
        $this->assertSame('foo', $this->association->getProperty());
        $this->assertSame($this->association, $this->association->setProperty('thing'));
        $this->assertSame('thing', $this->association->getProperty());
    }

    /**
     * Test that warning is shown if property name clashes with table field.
     */
    public function testPropertyNameClash(): void
    {
        $this->expectWarningMessageMatches('/^Association property name `foo` clashes with field of same name of table `test`/', function (): void {
            $this->source->setSchema(['foo' => ['type' => 'string']]);
            $this->assertSame('foo', $this->association->getProperty());
        });
    }

    /**
     * Tests strategy method
     */
    public function testSetStrategy(): void
    {
        $this->assertSame('join', $this->association->getStrategy());

        $this->association->setStrategy('select');
        $this->assertSame('select', $this->association->getStrategy());

        $this->association->setStrategy('subquery');
        $this->assertSame('subquery', $this->association->getStrategy());
    }

    /**
     * Tests that providing an invalid strategy throws an exception
     */
    public function testInvalidStrategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->association->setStrategy('anotherThing');
    }

    /**
     * Tests test setFinder() method
     */
    public function testSetFinderMethod(): void
    {
        $this->assertSame('all', $this->association->getFinder());
        $this->assertSame($this->association, $this->association->setFinder('published'));
        $this->assertSame('published', $this->association->getFinder());
    }

    /**
     * Tests that `finder` is a valid option for the association constructor
     */
    public function testFinderInConstructor(): void
    {
        $config = [
            'className' => TestTable::class,
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER',
            'finder' => 'published',
        ];
        $assoc = Mockery::mock(
            Association::class . '[type,eagerLoader,cascadeDelete,isOwningSide,saveAssociated]',
            ['Foo', $config],
        )
            ->makePartial()
            ->shouldIgnoreMissing();
        $assoc->shouldReceive('type')
            ->byDefault()
            ->andReturn(Association::MANY_TO_ONE);
        $this->assertSame('published', $assoc->getFinder());
    }

    public function testCustomFinderWithTypedArgs(): void
    {
        $this->association->setFinder('publishedWithArgOnly');
        $this->assertEquals(
            ['this' => 'custom'],
            $this->association->find(null, 'custom')->getOptions(),
        );
        $this->assertEquals(
            ['what' => 'custom', 'this' => 'custom'],
            $this->association->find(null, what: 'custom')->getOptions(),
        );
        $this->assertEquals(
            ['what' => 'custom', 'this' => 'custom'],
            $this->association->find(what: 'custom')->getOptions(),
        );
    }

    public function testCustomFinderWithOptions(): void
    {
        $this->association->setFinder('withOptions');

        $this->deprecated(function (): void {
            $this->assertEquals(
                ['this' => 'worked'],
                $this->association->find(null)->getOptions(),
            );

            $this->assertEquals(
                ['that' => 'custom', 'this' => 'worked'],
                $this->association->find(null, ['that' => 'custom'])->getOptions(),
            );
        });
    }

    /**
     * Tests that `locator` is a valid option for the association constructor
     */
    public function testLocatorInConstructor(): void
    {
        $locator = Mockery::mock(LocatorInterface::class);
        $config = [
            'className' => TestTable::class,
            'tableLocator' => $locator,
        ];
        $assoc = Mockery::mock(
            Association::class . '[type,eagerLoader,cascadeDelete,isOwningSide,saveAssociated]',
            ['Foo', $config],
        )
            ->makePartial()
            ->shouldIgnoreMissing();
        $assoc->shouldReceive('type')
            ->byDefault()
            ->andReturn(Association::MANY_TO_ONE);
        $this->assertEquals($locator, $assoc->getTableLocator());
    }
}
