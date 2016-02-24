<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Plugin;
use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * A Test double used to assert that default tables are created
 *
 */
class TestTable extends Table
{

    public function initialize(array $config = [])
    {
        $this->schema(['id' => ['type' => 'integer']]);
    }

    public function findPublished($query)
    {
        return $query->applyOptions(['this' => 'worked']);
    }
}

/**
 * Tests Association class
 *
 */
class AssociationTest extends TestCase
{

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
        $this->association = $this->getMock(
            '\Cake\ORM\Association',
            [
                '_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
                'saveAssociated', 'eagerLoader', 'type'
            ],
            ['Foo', $config]
        );
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
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
     * @return void
     */
    public function testName()
    {
        $this->assertEquals('Foo', $this->association->name());
        $this->association->name('Bar');
        $this->assertEquals('Bar', $this->association->name());
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
        $this->association = $this->getMock(
            '\Cake\ORM\Association',
            [
                '_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
                'saveAssociated', 'eagerLoader', 'type'
            ],
            ['Foo', $config]
        );

        $this->assertEquals('Test', $this->association->className());
    }

    /**
     * Tests that cascadeCallbacks() returns the correct configured value
     *
     * @return void
     */
    public function testCascadeCallbacks()
    {
        $this->assertSame(false, $this->association->cascadeCallbacks());
        $this->association->cascadeCallbacks(true);
        $this->assertSame(true, $this->association->cascadeCallbacks());
    }

    /**
     * Tests the bindingKey method as a setter/getter
     *
     * @return void
     */
    public function testBindingKey()
    {
        $this->association->bindingKey('foo_id');
        $this->assertEquals('foo_id', $this->association->bindingKey());
    }

    /**
     * Tests the bindingKey() method when called with its defaults
     *
     * @return void
     */
    public function testBindingKeyDefault()
    {
        $this->source->primaryKey(['id', 'site_id']);
        $this->association
            ->expects($this->once())
            ->method('isOwningSide')
            ->will($this->returnValue(true));
        $result = $this->association->bindingKey();
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
        $target->primaryKey(['foo', 'site_id']);
        $this->association->target($target);

        $this->association
            ->expects($this->once())
            ->method('isOwningSide')
            ->will($this->returnValue(false));
        $result = $this->association->bindingKey();
        $this->assertEquals(['foo', 'site_id'], $result);
    }

    /**
     * Tests that name() returns the correct configured value
     *
     * @return void
     */
    public function testForeignKey()
    {
        $this->assertEquals('a_key', $this->association->foreignKey());
        $this->association->foreignKey('another_key');
        $this->assertEquals('another_key', $this->association->foreignKey());
    }

    /**
     * Tests that conditions() returns the correct configured value
     *
     * @return void
     */
    public function testConditions()
    {
        $this->assertEquals(['field' => 'value'], $this->association->conditions());
        $conds = ['another_key' => 'another value'];
        $this->association->conditions($conds);
        $this->assertEquals($conds, $this->association->conditions());
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
     * @return void
     */
    public function testTarget()
    {
        $table = $this->association->target();
        $this->assertInstanceOf(__NAMESPACE__ . '\TestTable', $table);

        $other = new Table;
        $this->association->target($other);
        $this->assertSame($other, $this->association->target());
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

        $this->association = $this->getMock(
            '\Cake\ORM\Association',
            ['type', 'eagerLoader', 'cascadeDelete', 'isOwningSide', 'saveAssociated'],
            ['ThisAssociationName', $config]
        );

        $table = $this->association->target();
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $table);

        $this->assertTrue(
            TableRegistry::exists('TestPlugin.ThisAssociationName'),
            'The association class will use this registry key'
        );
        $this->assertFalse(TableRegistry::exists('TestPlugin.Comments'), 'The association class will NOT use this key');
        $this->assertFalse(TableRegistry::exists('Comments'), 'Should also not be set');
        $this->assertFalse(TableRegistry::exists('ThisAssociationName'), 'Should also not be set');

        $plugin = TableRegistry::get('TestPlugin.ThisAssociationName');
        $this->assertSame($table, $plugin, 'Should be an instance of TestPlugin.Comments');
        $this->assertSame('TestPlugin.ThisAssociationName', $table->registryAlias());
        $this->assertSame('comments', $table->table());
        $this->assertSame('ThisAssociationName', $table->alias());
    }

    /**
     * Tests that source() returns the correct Table object
     *
     * @return void
     */
    public function testSource()
    {
        $table = $this->association->source();
        $this->assertSame($this->source, $table);

        $other = new Table;
        $this->association->source($other);
        $this->assertSame($other, $this->association->source());
    }

    /**
     * Tests joinType method
     *
     * @return void
     */
    public function testJoinType()
    {
        $this->assertEquals('INNER', $this->association->joinType());
        $this->association->joinType('LEFT');
        $this->assertEquals('LEFT', $this->association->joinType());
    }

    /**
     * Tests property method
     *
     * @return void
     */
    public function testProperty()
    {
        $this->assertEquals('foo', $this->association->property());
        $this->association->property('thing');
        $this->assertEquals('thing', $this->association->property());
    }

    /**
     * Tests strategy method
     *
     * @return void
     */
    public function testStrategy()
    {
        $this->assertEquals('join', $this->association->strategy());
        $this->association->strategy('select');
        $this->assertEquals('select', $this->association->strategy());
        $this->association->strategy('subquery');
        $this->assertEquals('subquery', $this->association->strategy());
    }

    /**
     * Tests that providing an invalid strategy throws an exception
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testInvalidStrategy()
    {
        $this->association->strategy('anotherThing');
        $this->assertEquals('subquery', $this->association->strategy());
    }

    /**
     * Tests test finder() method as getter and setter
     *
     * @return void
     */
    public function testFinderMethod()
    {
        $this->assertEquals('all', $this->association->finder());
        $this->assertEquals('published', $this->association->finder('published'));
        $this->assertEquals('published', $this->association->finder());
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
        $assoc = $this->getMock(
            '\Cake\ORM\Association',
            ['type', 'eagerLoader', 'cascadeDelete', 'isOwningSide', 'saveAssociated'],
            ['Foo', $config]
        );
        $this->assertEquals('published', $assoc->finder());
    }

    /**
     * Tests that the defined custom finder is used when calling find
     * in the association
     *
     * @return void
     */
    public function testCustomFinderIsUsed()
    {
        $this->association->finder('published');
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
        $locator = $this->getMock('Cake\ORM\Locator\LocatorInterface');
        $config = [
            'className' => '\Cake\Test\TestCase\ORM\TestTable',
            'tableLocator' => $locator
        ];
        $assoc = $this->getMock(
            '\Cake\ORM\Association',
            ['type', 'eagerLoader', 'cascadeDelete', 'isOwningSide', 'saveAssociated'],
            ['Foo', $config]
        );
        $this->assertEquals($locator, $assoc->tableLocator());
    }
}
