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
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\Test\TestCase\ORM\Association\StubAssociationTrait;
use Cake\TestSuite\TestCase;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
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
     * @var \Cake\ORM\Association
     */
    protected $association;

    /**
     * Set up
     */
    public function setUp(): void
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
        $this->association = new class ('Foo', $config) extends Association {
            use StubAssociationTrait;
        };
    }

    /**
     * Tests that _options acts as a callback where subclasses can add their own
     * initialization code based on the passed configuration array
     */
    public function testOptionsIsCalled(): void
    {
        $assoc = new class ('Name', ['foo' => 'bar']) extends Association {
            use StubAssociationTrait;

            protected function _options(array $options): void
            {
                if ($options['foo'] !== 'bar') {
                    throw new Exception('Invalid option');
                }
            }
        };
        $this->assertInstanceOf(Association::class, $assoc);
    }

    /**
     * Test that _className property is set to alias when "className" config
     * if not explicitly set.
     */
    public function testSetttingClassNameFromAlias(): void
    {
        $association = new class ('Foo') extends Association {
            use StubAssociationTrait;
        };

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
        $this->association = new class ('Foo', ['className' => 'Test']) extends Association {
            use StubAssociationTrait;
        };

        $this->assertSame('Test', $this->association->getClassName());
    }

    /**
     * Tests that an exception is thrown when invalid target table is fetched
     * from a registry.
     */
    public function testInvalidTableFetchedFromRegistry(): void
    {
        $this->expectException(DatabaseException::class);
        $this->association = new class ('Test', ['className' => TestTable::class]) extends Association {
            use StubAssociationTrait;
        };
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
        $this->association = new class ('Test', ['className' => Table::class]) extends Association {
            use StubAssociationTrait;
        };

        $target = $this->association->getTarget();
        $this->assertInstanceOf(Table::class, $target);
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
        $association = new class ('Test') extends Association {
            use StubAssociationTrait;
        };
        $association->setSource($this->source);
        $result = $association->getBindingKey();
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

        $association = new class ('Test') extends Association {
            use StubAssociationTrait;

            public function isOwningSide(Table $side): bool
            {
                return false;
            }
        };
        $association->setTarget($target);
        $association->setSource($this->source);
        $result = $association->getBindingKey();
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
        $this->association = new class ('ThisAssociationName', $config) extends Association {
            use StubAssociationTrait;
        };

        $table = $this->association->getTarget();
        $this->assertInstanceOf(CommentsTable::class, $table);

        $this->assertTrue(
            $this->getTableLocator()->exists('TestPlugin.ThisAssociationName'),
            'The association class will use this registry key'
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
     * Test that warning is not shown if "propertyName" option is explicitly specified.
     */
    public function testPropertyNameExplicitySet(): void
    {
        $this->source->setSchema(['foo' => ['type' => 'string']]);

        $config = [
            'className' => TestTable::class,
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER',
            'propertyName' => 'foo',
        ];
        $association = new class ('Foo', $config) extends Association {
            use StubAssociationTrait;
        };

        $this->assertSame('foo', $association->getProperty());
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
        $assoc = new class ('Foo', $config) extends Association {
            use StubAssociationTrait;
        };
        $this->assertSame('published', $assoc->getFinder());
    }

    public function testCustomFinderWithTypedArgs(): void
    {
        $this->association->setFinder('publishedWithArgOnly');
        $this->assertEquals(
            ['custom', 'this' => 'custom'],
            $this->association->find(null, 'custom')->getOptions()
        );
        $this->assertEquals(
            ['what' => 'custom', 'this' => 'custom'],
            $this->association->find(null, what: 'custom')->getOptions()
        );
        $this->assertEquals(
            ['what' => 'custom', 'this' => 'custom'],
            $this->association->find(what: 'custom')->getOptions()
        );
    }

    #[WithoutErrorHandler]
    public function testCustomFinderWithOptions(): void
    {
        $this->association->setFinder('withOptions');

        $this->deprecated(function (): void {
            $this->assertEquals(
                ['this' => 'worked'],
                $this->association->find(null)->getOptions()
            );

            $this->assertEquals(
                ['that' => 'custom', 'this' => 'worked'],
                $this->association->find(null, ['that' => 'custom'])->getOptions()
            );
        });
    }

    /**
     * Tests that `locator` is a valid option for the association constructor
     */
    public function testLocatorInConstructor(): void
    {
        $locator = new TableLocator();
        $config = [
            'className' => TestTable::class,
            'tableLocator' => $locator,
        ];
        $assoc = new class ('Foo', $config) extends Association {
            use StubAssociationTrait;
        };
        $this->assertEquals($locator, $assoc->getTableLocator());
    }
}
