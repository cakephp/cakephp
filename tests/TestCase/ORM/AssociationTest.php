<?php
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

use Cake\Core\Plugin;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * A Test double used to assert that default tables are created
 */
class TestTable extends Table
{

    public function initialize(array $config = [])
    {
        $this->setSchema(['id' => ['type' => 'integer']]);
    }

    public function findPublished($query)
    {
        return $query->applyOptions(['this' => 'worked']);
    }
}

/**
 * Tests Association class
 */
class AssociationTest extends TestCase
{

    /**
     * @var \Cake\ORM\Association|\PHPUnit_Framework_MockObject_MockObject
     */
    public $association;

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->source = new TestTable;
        $config = [
            'className' => '\Cake\Test\TestCase\ORM\TestTable',
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER'
        ];
        $this->association = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                '_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
                'saveAssociated', 'eagerLoader', 'type', 'requiresKeys'
            ])
            ->setConstructorArgs(['Foo', $config])
            ->getMock();
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->getTableLocator()->clear();
    }

    /**
     * Tests that _options acts as a callback where subclasses can add their own
     * initialization code based on the passed configuration array
     *
     * @return void
     */
    public function testOptionsIsCalled()
    {
        $options = ['foo' => 'bar'];
        $this->association->expects($this->once())->method('_options')->with($options);
        $this->association->__construct('Name', $options);
    }

    /**
     * Tests that name() returns the correct configure association name
     *
     * @group deprecated
     * @return void
     */
    public function testName()
    {
        $this->deprecated(function () {
            $this->assertEquals('Foo', $this->association->name());
            $this->association->name('Bar');
            $this->assertEquals('Bar', $this->association->name());
        });
    }

    /**
     * Tests that setName()
     *
     * @return void
     */
    public function testSetName()
    {
        $this->assertEquals('Foo', $this->association->getName());
        $this->assertSame($this->association, $this->association->setName('Bar'));
        $this->assertEquals('Bar', $this->association->getName());
    }

    /**
     * Tests that setName() succeeds before the target table is resolved.
     *
     * @return void
     */
    public function testSetNameBeforeTarget()
    {
        $this->association->setName('Bar');
        $this->assertEquals('Bar', $this->association->getName());
    }

    /**
     * Tests that setName() fails after the target table is resolved.
     *
     * @return void
     */
    public function testSetNameAfterTarger()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Association name does not match target table alias.');
        $this->association->getTarget();
        $this->association->setName('Bar');
    }

    /**
     * Tests that setName() succeeds if name equals target table alias.
     *
     * @return void
     */
    public function testSetNameToTargetAlias()
    {
        $alias = $this->association->getTarget()->getAlias();
        $this->association->setName($alias);
        $this->assertEquals($alias, $this->association->getName());
    }

    /**
     * Tests that className() returns the correct association className
     *
     * @return void
     */
    public function testClassName()
    {
        $this->assertEquals('\Cake\Test\TestCase\ORM\TestTable', $this->association->className());
    }

    /**
     * Tests that className() returns the correct (unnormalized) className
     *
     * @return void
     */
    public function testClassNameUnnormalized()
    {
        $config = [
            'className' => 'Test',
        ];
        $this->association = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                '_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
                'saveAssociated', 'eagerLoader', 'type', 'requiresKeys'
            ])
            ->setConstructorArgs(['Foo', $config])
            ->getMock();

        $this->assertEquals('Test', $this->association->className());
    }

    /**
     * Tests that an exception is thrown when invalid target table is fetched
     * from a registry.
     *
     * @return void
     */
    public function testInvalidTableFetchedFromRegistry()
    {
        $this->expectException(\RuntimeException::class);
        $this->getTableLocator()->get('Test');

        $config = [
            'className' => '\Cake\Test\TestCase\ORM\TestTable',
        ];
        $this->association = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                '_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
                'saveAssociated', 'eagerLoader', 'type', 'requiresKeys'
            ])
            ->setConstructorArgs(['Test', $config])
            ->getMock();

        $this->association->getTarget();
    }

    /**
     * Tests that a descendant table could be fetched from a registry.
     *
     * @return void
     */
    public function testTargetTableDescendant()
    {
        $this->getTableLocator()->get('Test', [
            'className' => '\Cake\Test\TestCase\ORM\TestTable'
        ]);
        $className = '\Cake\ORM\Table';

        $config = [
            'className' => $className,
        ];
        $this->association = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                '_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
                'saveAssociated', 'eagerLoader', 'type', 'requiresKeys'
            ])
            ->setConstructorArgs(['Test', $config])
            ->getMock();

        $target = $this->association->getTarget();
        $this->assertInstanceOf($className, $target);
    }

    /**
     * Tests that cascadeCallbacks() returns the correct configured value
     *
     * @group deprecated
     * @return void
     */
    public function testCascadeCallbacks()
    {
        $this->deprecated(function () {
            $this->assertFalse($this->association->cascadeCallbacks());
            $this->association->cascadeCallbacks(true);
            $this->assertTrue($this->association->cascadeCallbacks());
        });
    }

    /**
     * Tests that cascadeCallbacks() returns the correct configured value
     *
     * @return void
     */
    public function testSetCascadeCallbacks()
    {
        $this->assertFalse($this->association->getCascadeCallbacks());
        $this->assertSame($this->association, $this->association->setCascadeCallbacks(true));
        $this->assertTrue($this->association->getCascadeCallbacks());
    }

    /**
     * Tests the bindingKey method as a setter/getter
     *
     * @group deprecated
     * @return void
     */
    public function testBindingKey()
    {
        $this->deprecated(function () {
            $this->association->bindingKey('foo_id');
            $this->assertEquals('foo_id', $this->association->bindingKey());
        });
    }

    /**
     * Tests the bindingKey method as a setter/getter
     *
     * @return void
     */
    public function testSetBindingKey()
    {
        $this->assertSame($this->association, $this->association->setBindingKey('foo_id'));
        $this->assertEquals('foo_id', $this->association->getBindingKey());
    }

    /**
     * Tests the bindingKey() method when called with its defaults
     *
     * @return void
     */
    public function testBindingKeyDefault()
    {
        $this->source->setPrimaryKey(['id', 'site_id']);
        $this->association
            ->expects($this->once())
            ->method('isOwningSide')
            ->will($this->returnValue(true));
        $result = $this->association->getBindingKey();
        $this->assertEquals(['id', 'site_id'], $result);
    }

    /**
     * Tests the bindingKey() method when the association source is not the
     * owning side
     *
     * @return void
     */
    public function testBindingDefaultNoOwningSide()
    {
        $target = new Table;
        $target->setPrimaryKey(['foo', 'site_id']);
        $this->association->setTarget($target);

        $this->association
            ->expects($this->once())
            ->method('isOwningSide')
            ->will($this->returnValue(false));
        $result = $this->association->getBindingKey();
        $this->assertEquals(['foo', 'site_id'], $result);
    }

    /**
     * Tests that name() returns the correct configured value
     *
     * @group deprecated
     * @return void
     */
    public function testForeignKey()
    {
        $this->deprecated(function () {
            $this->assertEquals('a_key', $this->association->foreignKey());
            $this->association->foreignKey('another_key');
            $this->assertEquals('another_key', $this->association->foreignKey());
        });
    }

    /**
     * Tests setForeignKey()
     *
     * @return void
     */
    public function testSetForeignKey()
    {
        $this->assertEquals('a_key', $this->association->getForeignKey());
        $this->assertSame($this->association, $this->association->setForeignKey('another_key'));
        $this->assertEquals('another_key', $this->association->getForeignKey());
    }

    /**
     * Tests that conditions() returns the correct configured value
     *
     * @group deprecated
     * @return void
     */
    public function testConditions()
    {
        $this->deprecated(function () {
            $this->assertEquals(['field' => 'value'], $this->association->conditions());
            $conds = ['another_key' => 'another value'];
            $this->association->conditions($conds);
            $this->assertEquals($conds, $this->association->conditions());
        });
    }

    /**
     * Tests setConditions()
     *
     * @return void
     */
    public function testSetConditions()
    {
        $this->assertEquals(['field' => 'value'], $this->association->getConditions());
        $conds = ['another_key' => 'another value'];
        $this->assertSame($this->association, $this->association->setConditions($conds));
        $this->assertEquals($conds, $this->association->getConditions());
    }

    /**
     * Tests that canBeJoined() returns the correct configured value
     *
     * @return void
     */
    public function testCanBeJoined()
    {
        $this->assertTrue($this->association->canBeJoined());
    }

    /**
     * Tests that target() returns the correct Table object
     *
     * @group deprecated
     * @return void
     */
    public function testTarget()
    {
        $this->deprecated(function () {
            $table = $this->association->target();
            $this->assertInstanceOf(__NAMESPACE__ . '\TestTable', $table);

            $other = new Table;
            $this->association->target($other);
            $this->assertSame($other, $this->association->target());
        });
    }

    /**
     * Tests that setTarget()
     *
     * @return void
     */
    public function testSetTarget()
    {
        $table = $this->association->getTarget();
        $this->assertInstanceOf(__NAMESPACE__ . '\TestTable', $table);

        $other = new Table;
        $this->assertSame($this->association, $this->association->setTarget($other));
        $this->assertSame($other, $this->association->getTarget());
    }

    /**
     * Tests that target() returns the correct Table object for plugins
     *
     * @return void
     */
    public function testTargetPlugin()
    {
        Plugin::load('TestPlugin');
        $config = [
            'className' => 'TestPlugin.Comments',
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER'
        ];

        $this->association = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                'type', 'eagerLoader', 'cascadeDelete', 'isOwningSide', 'saveAssociated',
                'requiresKeys'
            ])
            ->setConstructorArgs(['ThisAssociationName', $config])
            ->getMock();

        $table = $this->association->getTarget();
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $table);

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
    }

    /**
     * Tests that source() returns the correct Table object
     *
     * @group deprecated
     * @return void
     */
    public function testSource()
    {
        $this->deprecated(function () {
            $table = $this->association->source();
            $this->assertSame($this->source, $table);

            $other = new Table;
            $this->association->source($other);
            $this->assertSame($other, $this->association->source());
        });
    }

    /**
     * Tests that source() returns the correct Table object
     *
     * @return void
     */
    public function testSetSource()
    {
        $table = $this->association->getSource();
        $this->assertSame($this->source, $table);

        $other = new Table;
        $this->assertSame($this->association, $this->association->setSource($other));
        $this->assertSame($other, $this->association->getSource());
    }

    /**
     * Tests joinType method
     *
     * @group deprecated
     * @return void
     */
    public function testJoinType()
    {
        $this->deprecated(function () {
            $this->assertEquals('INNER', $this->association->joinType());
            $this->association->joinType('LEFT');
            $this->assertEquals('LEFT', $this->association->joinType());
        });
    }

    /**
     * Tests setJoinType method
     *
     * @return void
     */
    public function testSetJoinType()
    {
        $this->assertEquals('INNER', $this->association->getJoinType());
        $this->assertSame($this->association, $this->association->setJoinType('LEFT'));
        $this->assertEquals('LEFT', $this->association->getJoinType());
    }

    /**
     * Tests dependent method
     *
     * @group deprecated
     * @return void
     */
    public function testDependent()
    {
        $this->deprecated(function () {
            $this->assertTrue($this->association->dependent());
            $this->association->dependent(false);
            $this->assertFalse($this->association->dependent());
        });
    }

    /**
     * Tests property method
     *
     * @group deprecated
     * @return void
     */
    public function testProperty()
    {
        $this->deprecated(function () {
            $this->assertEquals('foo', $this->association->property());
            $this->association->property('thing');
            $this->assertEquals('thing', $this->association->property());
        });
    }

    /**
     * Tests property method
     *
     * @return void
     */
    public function testSetProperty()
    {
        $this->assertEquals('foo', $this->association->getProperty());
        $this->assertSame($this->association, $this->association->setProperty('thing'));
        $this->assertEquals('thing', $this->association->getProperty());
    }

    /**
     * Test that warning is shown if property name clashes with table field.
     *
     * @return void
     */
    public function testPropertyNameClash()
    {
        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessageRegExp('/^Association property name "foo" clashes with field of same name of table "test"/');
        $this->source->setSchema(['foo' => ['type' => 'string']]);
        $this->assertEquals('foo', $this->association->getProperty());
    }

    /**
     * Test that warning is not shown if "propertyName" option is explicitly specified.
     *
     * @return void
     */
    public function testPropertyNameExplicitySet()
    {
        $this->source->setSchema(['foo' => ['type' => 'string']]);

        $config = [
            'className' => '\Cake\Test\TestCase\ORM\TestTable',
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER',
            'propertyName' => 'foo'
        ];
        $association = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                '_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
                'saveAssociated', 'eagerLoader', 'type', 'requiresKeys'
            ])
            ->setConstructorArgs(['Foo', $config])
            ->getMock();

        $this->assertEquals('foo', $association->getProperty());
    }

    /**
     * Tests strategy method
     *
     * @group deprecated
     * @return void
     */
    public function testStrategy()
    {
        $this->deprecated(function () {
            $this->assertEquals('join', $this->association->strategy());

            $this->association->strategy('select');
            $this->assertEquals('select', $this->association->strategy());

            $this->association->strategy('subquery');
            $this->assertEquals('subquery', $this->association->strategy());
        });
    }

    /**
     * Tests strategy method
     *
     * @return void
     */
    public function testSetStrategy()
    {
        $this->assertEquals('join', $this->association->getStrategy());

        $this->association->setStrategy('select');
        $this->assertEquals('select', $this->association->getStrategy());

        $this->association->setStrategy('subquery');
        $this->assertEquals('subquery', $this->association->getStrategy());
    }

    /**
     * Tests that providing an invalid strategy throws an exception
     *
     * @return void
     */
    public function testInvalidStrategy()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->association->setStrategy('anotherThing');
    }

    /**
     * Tests test finder() method as getter and setter
     *
     * @group deprecated
     * @return void
     */
    public function testFinderMethod()
    {
        $this->deprecated(function () {
            $this->assertEquals('all', $this->association->finder());
            $this->assertEquals('published', $this->association->finder('published'));
            $this->assertEquals('published', $this->association->finder());
        });
    }

    /**
     * Tests test setFinder() method
     *
     * @return void
     */
    public function testSetFinderMethod()
    {
        $this->assertEquals('all', $this->association->getFinder());
        $this->assertSame($this->association, $this->association->setFinder('published'));
        $this->assertEquals('published', $this->association->getFinder());
    }

    /**
     * Tests that `finder` is a valid option for the association constructor
     *
     * @return void
     */
    public function testFinderInConstructor()
    {
        $config = [
            'className' => '\Cake\Test\TestCase\ORM\TestTable',
            'foreignKey' => 'a_key',
            'conditions' => ['field' => 'value'],
            'dependent' => true,
            'sourceTable' => $this->source,
            'joinType' => 'INNER',
            'finder' => 'published'
        ];
        $assoc = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                'type', 'eagerLoader', 'cascadeDelete', 'isOwningSide', 'saveAssociated',
                'requiresKeys'
            ])
            ->setConstructorArgs(['Foo', $config])
            ->getMock();
        $this->assertEquals('published', $assoc->getFinder());
    }

    /**
     * Tests that the defined custom finder is used when calling find
     * in the association
     *
     * @return void
     */
    public function testCustomFinderIsUsed()
    {
        $this->association->setFinder('published');
        $this->assertEquals(
            ['this' => 'worked'],
            $this->association->find()->getOptions()
        );
    }

    /**
     * Tests that `locator` is a valid option for the association constructor
     *
     * @return void
     */
    public function testLocatorInConstructor()
    {
        $locator = $this->getMockBuilder('Cake\ORM\Locator\LocatorInterface')->getMock();
        $config = [
            'className' => '\Cake\Test\TestCase\ORM\TestTable',
            'tableLocator' => $locator
        ];
        $assoc = $this->getMockBuilder('\Cake\ORM\Association')
            ->setMethods([
                'type', 'eagerLoader', 'cascadeDelete', 'isOwningSide', 'saveAssociated',
                'requiresKeys'
            ])
            ->setConstructorArgs(['Foo', $config])
            ->getMock();
        $this->assertEquals($locator, $assoc->getTableLocator());
    }
}
