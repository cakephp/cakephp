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

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;
use TestApp\Model\Entity\Extending;
use TestApp\Model\Entity\NonExtending;

/**
 * Entity test case.
 */
class EntityTest extends TestCase
{
    /**
     * Tests setting a single property in an entity without custom setters
     */
    public function testSetOneParamNoSetters(): void
    {
        $entity = new Entity();
        $this->assertNull($entity->getOriginal('foo'));
        $entity->set('foo', 'bar');
        $this->assertSame('bar', $entity->foo);
        $this->assertSame('bar', $entity->getOriginal('foo'));

        $entity->set('foo', 'baz');
        $this->assertSame('baz', $entity->foo);
        $this->assertSame('bar', $entity->getOriginal('foo'));

        $entity->set('id', 1);
        $this->assertSame(1, $entity->id);
        $this->assertSame(1, $entity->getOriginal('id'));
        $this->assertSame('bar', $entity->getOriginal('foo'));
    }

    /**
     * Tests setting multiple properties without custom setters
     */
    public function testSetMultiplePropertiesNoSetters(): void
    {
        $entity = new Entity();
        $entity->setAccess('*', true);

        $entity->set(['foo' => 'bar', 'id' => 1]);
        $this->assertSame('bar', $entity->foo);
        $this->assertSame(1, $entity->id);

        $entity->set(['foo' => 'baz', 'id' => 2, 'thing' => 3]);
        $this->assertSame('baz', $entity->foo);
        $this->assertSame(2, $entity->id);
        $this->assertSame(3, $entity->thing);
        $this->assertSame('bar', $entity->getOriginal('foo'));
        $this->assertSame(1, $entity->getOriginal('id'));

        $entity->set(['foo', 'bar']);
        $this->assertSame('foo', $entity->get('0'));
        $this->assertSame('bar', $entity->get('1'));

        $entity->set(['sample']);
        $this->assertSame('sample', $entity->get('0'));
    }

    /**
     * Test that getOriginal() retains falsey values.
     */
    public function testGetOriginal(): void
    {
        $entity = new Entity(
            ['false' => false, 'null' => null, 'zero' => 0, 'empty' => ''],
            ['markNew' => true]
        );
        $this->assertNull($entity->getOriginal('null'));
        $this->assertFalse($entity->getOriginal('false'));
        $this->assertSame(0, $entity->getOriginal('zero'));
        $this->assertSame('', $entity->getOriginal('empty'));

        $entity->set(['false' => 'y', 'null' => 'y', 'zero' => 'y', 'empty' => '']);
        $this->assertNull($entity->getOriginal('null'));
        $this->assertFalse($entity->getOriginal('false'));
        $this->assertSame(0, $entity->getOriginal('zero'));
        $this->assertSame('', $entity->getOriginal('empty'));
    }

    /**
     * Test extractOriginal()
     */
    public function testExtractOriginal(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'original',
            'body' => 'no',
            'null' => null,
        ], ['markNew' => true]);
        $entity->set('body', 'updated body');
        $result = $entity->extractOriginal(['id', 'title', 'body', 'null']);
        $expected = [
            'id' => 1,
            'title' => 'original',
            'body' => 'no',
            'null' => null,
        ];
        $this->assertEquals($expected, $result);

        $result = $entity->extractOriginalChanged(['id', 'title', 'body', 'null']);
        $expected = [
            'body' => 'no',
        ];
        $this->assertEquals($expected, $result);

        $entity->set('null', 'not null');
        $result = $entity->extractOriginalChanged(['id', 'title', 'body', 'null']);
        $expected = [
            'null' => null,
            'body' => 'no',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that all original values are returned properly
     */
    public function testExtractOriginalValues(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'original',
            'body' => 'no',
            'null' => null,
        ], ['markNew' => true]);
        $entity->set('body', 'updated body');
        $result = $entity->getOriginalValues();
        $expected = [
            'id' => 1,
            'title' => 'original',
            'body' => 'no',
            'null' => null,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests setting a single property using a setter function
     */
    public function testSetOneParamWithSetter(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_setName'])
            ->getMock();
        $entity->expects($this->once())->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertSame('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertSame('Dr. Jones', $entity->name);
    }

    /**
     * Tests setting multiple properties using a setter function
     */
    public function testMultipleWithSetter(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_setName', '_setStuff'])
            ->getMock();
        $entity->setAccess('*', true);
        $entity->expects($this->once())->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertSame('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->expects($this->once())->method('_setStuff')
            ->with(['a', 'b'])
            ->will($this->returnCallback(function ($stuff) {
                $this->assertEquals(['a', 'b'], $stuff);

                return ['c', 'd'];
            }));
        $entity->set(['name' => 'Jones', 'stuff' => ['a', 'b']]);
        $this->assertSame('Dr. Jones', $entity->name);
        $this->assertEquals(['c', 'd'], $entity->stuff);
    }

    /**
     * Tests that it is possible to bypass the setters
     */
    public function testBypassSetters(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_setName', '_setStuff'])
            ->getMock();
        $entity->setAccess('*', true);

        $entity->expects($this->never())->method('_setName');
        $entity->expects($this->never())->method('_setStuff');

        $entity->set('name', 'Jones', ['setter' => false]);
        $this->assertSame('Jones', $entity->name);

        $entity->set('stuff', 'Thing', ['setter' => false]);
        $this->assertSame('Thing', $entity->stuff);

        $entity->set(['name' => 'foo', 'stuff' => 'bar'], ['setter' => false]);
        $this->assertSame('bar', $entity->stuff);
    }

    /**
     * Tests that the constructor will set initial properties
     */
    public function testConstructor(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [
                    ['a' => 'b', 'c' => 'd'], ['setter' => true, 'guard' => false],
                ],
                [['foo' => 'bar'], ['setter' => false, 'guard' => false]]
            );

        $entity->__construct(['a' => 'b', 'c' => 'd']);
        $entity->__construct(['foo' => 'bar'], ['useSetters' => false]);
    }

    /**
     * Tests that the constructor will set initial properties and pass the guard
     * option along
     */
    public function testConstructorWithGuard(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())
            ->method('set')
            ->with(['foo' => 'bar'], ['setter' => true, 'guard' => true]);
        $entity->__construct(['foo' => 'bar'], ['guard' => true]);
    }

    /**
     * Tests getting properties with no custom getters
     */
    public function testGetNoGetters(): void
    {
        $entity = new Entity(['id' => 1, 'foo' => 'bar']);
        $this->assertSame(1, $entity->get('id'));
        $this->assertSame('bar', $entity->get('foo'));
    }

    /**
     * Tests get with custom getter
     */
    public function testGetCustomGetters(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getName'])
            ->getMock();
        $entity->expects($this->any())
            ->method('_getName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertSame('Dr. Jones', $entity->get('name'));
        $this->assertSame('Dr. Jones', $entity->get('name'));
    }

    /**
     * Tests get with custom getter
     */
    public function testGetCustomGettersAfterSet(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getName'])
            ->getMock();
        $entity->expects($this->any())
            ->method('_getName')
            ->will($this->returnCallback(function ($name) {
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertSame('Dr. Jones', $entity->get('name'));
        $this->assertSame('Dr. Jones', $entity->get('name'));

        $entity->set('name', 'Mark');
        $this->assertSame('Dr. Mark', $entity->get('name'));
        $this->assertSame('Dr. Mark', $entity->get('name'));
    }

    /**
     * Tests that the get cache is cleared by unsetProperty.
     */
    public function testGetCacheClearedByUnset(): void
    {
        /** @var \Cake\ORM\Entity|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getName'])
            ->getMock();
        $entity->expects($this->any())->method('_getName')
            ->will($this->returnCallback(function ($name) {
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertSame('Dr. Jones', $entity->get('name'));

        $entity->unset('name');
        $this->assertSame('Dr. ', $entity->get('name'));
    }

    /**
     * Test getting camelcased virtual fields.
     */
    public function testGetCamelCasedProperties(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getListIdName'])
            ->getMock();
        $entity->expects($this->any())->method('_getListIdName')
            ->will($this->returnCallback(function ($name) {
                return 'A name';
            }));
        $entity->setVirtual(['ListIdName']);
        $this->assertSame('A name', $entity->list_id_name, 'underscored virtual field should be accessible');
        $this->assertSame('A name', $entity->listIdName, 'Camelbacked virtual field should be accessible');
    }

    /**
     * Test magic property setting with no custom setter
     */
    public function testMagicSet(): void
    {
        $entity = new Entity();
        $entity->name = 'Jones';
        $this->assertSame('Jones', $entity->name);
        $entity->name = 'George';
        $this->assertSame('George', $entity->name);
    }

    /**
     * Tests magic set with custom setter function
     */
    public function testMagicSetWithSetter(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_setName'])
            ->getMock();
        $entity->expects($this->once())->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertSame('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->name = 'Jones';
        $this->assertSame('Dr. Jones', $entity->name);
    }

    /**
     * Tests magic set with custom setter function using a Title cased property
     */
    public function testMagicSetWithSetterTitleCase(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_setName'])
            ->getMock();
        $entity->expects($this->once())
            ->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertSame('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->Name = 'Jones';
        $this->assertSame('Dr. Jones', $entity->Name);
    }

    /**
     * Tests the magic getter with a custom getter function
     */
    public function testMagicGetWithGetter(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getName'])
            ->getMock();
        $entity->expects($this->once())->method('_getName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertSame('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertSame('Dr. Jones', $entity->name);
    }

    /**
     * Tests magic get with custom getter function using a Title cased property
     */
    public function testMagicGetWithGetterTitleCase(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getName'])
            ->getMock();
        $entity->expects($this->once())
            ->method('_getName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertSame('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->set('Name', 'Jones');
        $this->assertSame('Dr. Jones', $entity->Name);
    }

    /**
     * Test indirectly modifying internal properties
     */
    public function testIndirectModification(): void
    {
        $entity = new Entity();
        $entity->things = ['a', 'b'];
        $entity->things[] = 'c';
        $this->assertEquals(['a', 'b', 'c'], $entity->things);
    }

    /**
     * Tests has() method
     */
    public function testHas(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
        $this->assertTrue($entity->has('id'));
        $this->assertTrue($entity->has('name'));
        $this->assertFalse($entity->has('foo'));
        $this->assertFalse($entity->has('last_name'));

        $this->assertTrue($entity->has(['id']));
        $this->assertTrue($entity->has(['id', 'name']));
        $this->assertFalse($entity->has(['id', 'foo']));
        $this->assertFalse($entity->has(['id', 'nope']));

        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getThings'])
            ->getMock();
        $entity->expects($this->once())->method('_getThings')
            ->will($this->returnValue(0));
        $this->assertTrue($entity->has('things'));
    }

    /**
     * Tests unsetProperty one property at a time
     */
    public function testUnset(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'bar']);
        $entity->unset('id');
        $this->assertFalse($entity->has('id'));
        $this->assertTrue($entity->has('name'));
        $entity->unset('name');
        $this->assertFalse($entity->has('id'));
    }

    /**
     * Unsetting a property should not mark it as dirty.
     */
    public function testUnsetMakesClean(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'bar']);
        $this->assertTrue($entity->isDirty('name'));
        $entity->unset('name');
        $this->assertFalse($entity->isDirty('name'), 'Removed properties are not dirty.');
    }

    /**
     * Tests unsetProperty with multiple properties
     */
    public function testUnsetMultiple(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'bar', 'thing' => 2]);
        $entity->unset(['id', 'thing']);
        $this->assertFalse($entity->has('id'));
        $this->assertTrue($entity->has('name'));
        $this->assertFalse($entity->has('thing'));
    }

    /**
     * Tests the magic __isset() method
     */
    public function testMagicIsset(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
        $this->assertTrue(isset($entity->id));
        $this->assertTrue(isset($entity->name));
        $this->assertFalse(isset($entity->foo));
        $this->assertFalse(isset($entity->thing));
    }

    /**
     * Tests the magic __unset() method
     */
    public function testMagicUnset(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['unset'])
            ->getMock();
        $entity->expects($this->once())
            ->method('unset')
            ->with('foo');
        unset($entity->foo);
    }

    /**
     * Tests the deprecated unsetProperty() method
     */
    public function testUnsetDeprecated(): void
    {
        $this->deprecated(function (): void {
            $entity = new Entity();
            $entity->foo = 'foo';

            $entity->unsetProperty('foo');
            $this->assertNull($entity->foo);
        });
    }

    /**
     * Tests isset with array access
     */
    public function testIssetArrayAccess(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
        $this->assertArrayHasKey('id', $entity);
        $this->assertArrayHasKey('name', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
        $this->assertArrayNotHasKey('thing', $entity);
    }

    /**
     * Tests get property with array access
     */
    public function testGetArrayAccess(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['get'])
            ->getMock();
        $entity->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['foo'], ['bar'])
            ->will($this->onConsecutiveCalls(
                $this->returnValue('worked'),
                $this->returnValue('worked too')
            ));

        $this->assertSame('worked', $entity['foo']);
        $this->assertSame('worked too', $entity['bar']);
    }

    /**
     * Tests set with array access
     */
    public function testSetArrayAccess(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['set'])
            ->getMock();
        $entity->setAccess('*', true);

        $entity->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(['foo', 1], ['bar', 2])
            ->will($this->onConsecutiveCalls(
                $this->returnSelf(),
                $this->returnSelf()
            ));

        $entity['foo'] = 1;
        $entity['bar'] = 2;
    }

    /**
     * Tests unset with array access
     */
    public function testUnsetArrayAccess(): void
    {
        /** @var \Cake\ORM\Entity|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['unset'])
            ->getMock();
        $entity->expects($this->once())
            ->method('unset')
            ->with('foo');
        unset($entity['foo']);
    }

    /**
     * Tests that the method cache will only report the methods for the called class,
     * this is, calling methods defined in another entity will not cause a fatal error
     * when trying to call directly an inexistent method in another class
     */
    public function testMethodCache(): void
    {
        /** @var \Cake\ORM\Entity|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_setFoo', '_getBar'])
            ->getMock();
        /** @var \Cake\ORM\Entity|\PHPUnit\Framework\MockObject\MockObject $entity2 */
        $entity2 = $this->getMockBuilder(Entity::class)
            ->addMethods(['_setBar'])
            ->getMock();
        $entity->expects($this->once())->method('_setFoo');
        $entity->expects($this->once())->method('_getBar');
        $entity2->expects($this->once())->method('_setBar');

        $entity->set('foo', 1);
        $entity->get('bar');
        $entity2->set('bar', 1);
    }

    /**
     * Tests that long properties in the entity are inflected correctly
     */
    public function testSetGetLongPropertyNames(): void
    {
        /** @var \Cake\ORM\Entity|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getVeryLongProperty', '_setVeryLongProperty'])
            ->getMock();
        $entity->expects($this->once())->method('_getVeryLongProperty');
        $entity->expects($this->once())->method('_setVeryLongProperty');
        $entity->get('very_long_property');
        $entity->set('very_long_property', 1);
    }

    /**
     * Tests serializing an entity as JSON
     */
    public function testJsonSerialize(): void
    {
        $data = ['name' => 'James', 'age' => 20, 'phones' => ['123', '457']];
        $entity = new Entity($data);
        $this->assertEquals(json_encode($data), json_encode($entity));
    }

    /**
     * Tests serializing an entity as PHP
     */
    public function testPhpSerialize(): void
    {
        $data = ['name' => 'James', 'age' => 20, 'phones' => ['123', '457']];
        $entity = new Entity($data);
        $copy = unserialize(serialize($entity));
        $this->assertInstanceOf(Entity::class, $copy);
        $this->assertEquals($data, $copy->toArray());
    }

    /**
     * Tests that jsonSerialize is called recursively for contained entities
     */
    public function testJsonSerializeRecursive(): void
    {
        $phone = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['jsonSerialize'])
            ->getMock();
        $phone->expects($this->once())->method('jsonSerialize')->will($this->returnValue(['something']));
        $data = ['name' => 'James', 'age' => 20, 'phone' => $phone];
        $entity = new Entity($data);
        $expected = ['name' => 'James', 'age' => 20, 'phone' => ['something']];
        $this->assertEquals(json_encode($expected), json_encode($entity));
    }

    /**
     * Tests the extract method
     */
    public function testExtract(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3,
        ]);
        $expected = ['author_id' => 3, 'title' => 'Foo',];
        $this->assertEquals($expected, $entity->extract(['author_id', 'title']));

        $expected = ['id' => 1];
        $this->assertEquals($expected, $entity->extract(['id']));

        $expected = [];
        $this->assertEquals($expected, $entity->extract([]));

        $expected = ['id' => 1, 'craziness' => null];
        $this->assertEquals($expected, $entity->extract(['id', 'craziness']));
    }

    /**
     * Tests isDirty() method on a newly created object
     */
    public function testIsDirty(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3,
        ]);
        $this->assertTrue($entity->isDirty('id'));
        $this->assertTrue($entity->isDirty('title'));
        $this->assertTrue($entity->isDirty('author_id'));

        $this->assertTrue($entity->isDirty());

        $entity->setDirty('id', false);
        $this->assertFalse($entity->isDirty('id'));
        $this->assertTrue($entity->isDirty('title'));

        $entity->setDirty('title', false);
        $this->assertFalse($entity->isDirty('title'));
        $this->assertTrue($entity->isDirty(), 'should be dirty, one field left');

        $entity->setDirty('author_id', false);
        $this->assertFalse($entity->isDirty(), 'all fields are clean.');
    }

    /**
     * Test setDirty().
     */
    public function testSetDirty(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3,
        ], ['markClean' => true]);

        $this->assertFalse($entity->isDirty());
        $this->assertSame($entity, $entity->setDirty('title'));
        $this->assertSame($entity, $entity->setDirty('id', false));

        $entity->setErrors(['title' => ['badness']]);
        $entity->setDirty('title', true);
        $this->assertEmpty($entity->getErrors(), 'Making a field dirty clears errors.');
    }

    /**
     * Tests dirty() when altering properties values and adding new ones
     */
    public function testDirtyChangingProperties(): void
    {
        $entity = new Entity([
            'title' => 'Foo',
        ]);

        $entity->setDirty('title', false);
        $this->assertFalse($entity->isDirty('title'));

        $entity->set('title', 'Foo');
        $this->assertTrue($entity->isDirty('title'));

        $entity->set('title', 'Foo');
        $this->assertTrue($entity->isDirty('title'));

        $entity->set('something', 'else');
        $this->assertTrue($entity->isDirty('something'));
    }

    /**
     * Tests extract only dirty properties
     */
    public function testExtractDirty(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3,
        ]);
        $entity->setDirty('id', false);
        $entity->setDirty('title', false);
        $expected = ['author_id' => 3];
        $result = $entity->extract(['id', 'title', 'author_id'], true);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the getDirty method
     */
    public function testGetDirty(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3,
        ]);

        $expected = [
            'id',
            'title',
            'author_id',
        ];
        $result = $entity->getDirty();
        $this->assertSame($expected, $entity->getDirty());
    }

    /**
     * Tests the clean method
     */
    public function testClean(): void
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3,
        ]);
        $this->assertTrue($entity->isDirty('id'));
        $this->assertTrue($entity->isDirty('title'));
        $this->assertTrue($entity->isDirty('author_id'));

        $entity->clean();
        $this->assertFalse($entity->isDirty('id'));
        $this->assertFalse($entity->isDirty('title'));
        $this->assertFalse($entity->isDirty('author_id'));
    }

    /**
     * Tests the isNew method
     */
    public function testIsNew(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3,
        ];
        $entity = new Entity($data);
        $this->assertTrue($entity->isNew());

        $entity->setNew(true);
        $this->assertTrue($entity->isNew());

        $entity->setNew(false);
        $this->assertFalse($entity->isNew());
    }

    /**
     * Tests the constructor when passing the markClean option
     */
    public function testConstructorWithClean(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['clean'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->never())->method('clean');
        $entity->__construct(['a' => 'b', 'c' => 'd']);

        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['clean'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())->method('clean');
        $entity->__construct(['a' => 'b', 'c' => 'd'], ['markClean' => true]);
    }

    /**
     * Tests the constructor when passing the markClean option
     */
    public function testConstructorWithMarkNew(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['setNew', 'clean'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->never())->method('clean');
        $entity->__construct(['a' => 'b', 'c' => 'd']);

        $entity = $this->getMockBuilder(Entity::class)
            ->onlyMethods(['setNew'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())->method('setNew');
        $entity->__construct(['a' => 'b', 'c' => 'd'], ['markNew' => true]);
    }

    /**
     * Test toArray method.
     */
    public function testToArray(): void
    {
        $data = ['name' => 'James', 'age' => 20, 'phones' => ['123', '457']];
        $entity = new Entity($data);

        $this->assertEquals($data, $entity->toArray());
    }

    /**
     * Test toArray recursive.
     */
    public function testToArrayRecursive(): void
    {
        $data = ['id' => 1, 'name' => 'James', 'age' => 20, 'phones' => ['123', '457']];
        $user = new Extending($data);
        $comments = [
            new NonExtending(['user_id' => 1, 'body' => 'Comment 1']),
            new NonExtending(['user_id' => 1, 'body' => 'Comment 2']),
        ];
        $user->comments = $comments;
        $user->profile = new Entity(['email' => 'mark@example.com']);

        $expected = [
            'id' => 1,
            'name' => 'James',
            'age' => 20,
            'phones' => ['123', '457'],
            'profile' => ['email' => 'mark@example.com'],
            'comments' => [
                ['user_id' => 1, 'body' => 'Comment 1'],
                ['user_id' => 1, 'body' => 'Comment 2'],
            ],
        ];
        $this->assertEquals($expected, $user->toArray());
    }

    /**
     * Tests that an entity with entities and other misc types can be properly toArray'd
     */
    public function testToArrayMixed(): void
    {
        $test = new Entity([
            'id' => 1,
            'foo' => [
                new Entity(['hi' => 'test']),
                'notentity' => 1,
            ],
        ]);
        $expected = [
            'id' => 1,
            'foo' => [
                ['hi' => 'test'],
                'notentity' => 1,
            ],
        ];
        $this->assertEquals($expected, $test->toArray());
    }

    /**
     * Test that get accessors are called when converting to arrays.
     */
    public function testToArrayWithAccessor(): void
    {
        /** @var \Cake\ORM\Entity|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getName'])
            ->getMock();
        $entity->setAccess('*', true);
        $entity->set(['name' => 'Mark', 'email' => 'mark@example.com']);
        $entity->expects($this->any())
            ->method('_getName')
            ->will($this->returnValue('Jose'));

        $expected = ['name' => 'Jose', 'email' => 'mark@example.com'];
        $this->assertEquals($expected, $entity->toArray());
    }

    /**
     * Test that toArray respects hidden properties.
     */
    public function testToArrayHiddenProperties(): void
    {
        $data = ['secret' => 'sauce', 'name' => 'mark', 'id' => 1];
        $entity = new Entity($data);
        $entity->setHidden(['secret']);
        $this->assertEquals(['name' => 'mark', 'id' => 1], $entity->toArray());
    }

    /**
     * Tests setting hidden properties.
     */
    public function testSetHidden(): void
    {
        $data = ['secret' => 'sauce', 'name' => 'mark', 'id' => 1];
        $entity = new Entity($data);
        $entity->setHidden(['secret']);

        $result = $entity->getHidden();
        $this->assertSame(['secret'], $result);

        $entity->setHidden(['name']);

        $result = $entity->getHidden();
        $this->assertSame(['name'], $result);
    }

    /**
     * Tests setting hidden properties with merging.
     */
    public function testSetHiddenWithMerge(): void
    {
        $data = ['secret' => 'sauce', 'name' => 'mark', 'id' => 1];
        $entity = new Entity($data);
        $entity->setHidden(['secret'], true);

        $result = $entity->getHidden();
        $this->assertSame(['secret'], $result);

        $entity->setHidden(['name'], true);

        $result = $entity->getHidden();
        $this->assertSame(['secret', 'name'], $result);

        $entity->setHidden(['name'], true);
        $result = $entity->getHidden();
        $this->assertSame(['secret', 'name'], $result);
    }

    /**
     * Test toArray includes 'virtual' properties.
     */
    public function testToArrayVirtualProperties(): void
    {
        /** @var \Cake\ORM\Entity|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->getMockBuilder(Entity::class)
            ->addMethods(['_getName'])
            ->getMock();
        $entity->setAccess('*', true);

        $entity->expects($this->any())
            ->method('_getName')
            ->will($this->returnValue('Jose'));
        $entity->set(['email' => 'mark@example.com']);

        $entity->setVirtual(['name']);
        $expected = ['name' => 'Jose', 'email' => 'mark@example.com'];
        $this->assertEquals($expected, $entity->toArray());

        $this->assertEquals(['name'], $entity->getVirtual());

        $entity->setHidden(['name']);
        $expected = ['email' => 'mark@example.com'];
        $this->assertEquals($expected, $entity->toArray());
        $this->assertEquals(['name'], $entity->getHidden());
    }

    /**
     * Tests the getVisible() method
     */
    public function testGetVisible(): void
    {
        $entity = new Entity();
        $entity->foo = 'foo';
        $entity->bar = 'bar';

        $expected = $entity->getVisible();
        $this->assertSame(['foo', 'bar'], $expected);
    }

    /**
     * Tests setting virtual properties with merging.
     */
    public function testSetVirtualWithMerge(): void
    {
        $data = ['virtual' => 'sauce', 'name' => 'mark', 'id' => 1];
        $entity = new Entity($data);
        $entity->setVirtual(['virtual']);

        $result = $entity->getVirtual();
        $this->assertSame(['virtual'], $result);

        $entity->setVirtual(['name'], true);

        $result = $entity->getVirtual();
        $this->assertSame(['virtual', 'name'], $result);

        $entity->setVirtual(['name'], true);
        $result = $entity->getVirtual();
        $this->assertSame(['virtual', 'name'], $result);
    }

    /**
     * Tests error getters and setters
     */
    public function testGetErrorAndSetError(): void
    {
        $entity = new Entity();
        $this->assertEmpty($entity->getErrors());

        $entity->setError('foo', 'bar');
        $this->assertEquals(['bar'], $entity->getError('foo'));

        $expected = [
            'foo' => ['bar'],
        ];
        $result = $entity->getErrors();
        $this->assertEquals($expected, $result);

        $indexedErrors = [2 => ['foo' => 'bar']];
        $entity = new Entity();
        $entity->setError('indexes', $indexedErrors);

        $expectedIndexed = [
            'indexes' => ['2' => ['foo' => 'bar']],
        ];
        $result = $entity->getErrors();
        $this->assertEquals($expectedIndexed, $result);
    }

    /**
     * Tests reading errors from nested validator
     */
    public function testGetErrorNested(): void
    {
        $entity = new Entity();
        $entity->setError('options', ['subpages' => ['_empty' => 'required']]);

        $expected = [
            'subpages' => ['_empty' => 'required'],
        ];
        $this->assertEquals($expected, $entity->getError('options'));

        $expected = ['_empty' => 'required'];
        $this->assertEquals($expected, $entity->getError('options.subpages'));
    }

    /**
     * Tests that it is possible to get errors for nested entities
     */
    public function testErrorsDeep(): void
    {
        $user = new Entity();
        $owner = new NonExtending();
        $author = new Extending([
            'foo' => 'bar',
            'thing' => 'baz',
            'user' => $user,
            'owner' => $owner,
        ]);
        $author->setError('thing', ['this is a mistake']);
        $user->setErrors(['a' => ['error1'], 'b' => ['error2']]);
        $owner->setErrors(['c' => ['error3'], 'd' => ['error4']]);

        $expected = ['a' => ['error1'], 'b' => ['error2']];
        $this->assertEquals($expected, $author->getError('user'));

        $expected = ['c' => ['error3'], 'd' => ['error4']];
        $this->assertEquals($expected, $author->getError('owner'));

        $author->set('multiple', [$user, $owner]);
        $expected = [
            ['a' => ['error1'], 'b' => ['error2']],
            ['c' => ['error3'], 'd' => ['error4']],
        ];
        $this->assertEquals($expected, $author->getError('multiple'));

        $expected = [
            'thing' => $author->getError('thing'),
            'user' => $author->getError('user'),
            'owner' => $author->getError('owner'),
            'multiple' => $author->getError('multiple'),
        ];
        $this->assertEquals($expected, $author->getErrors());
    }

    /**
     * Tests that check if hasErrors() works
     */
    public function testHasErrors(): void
    {
        $entity = new Entity();
        $hasErrors = $entity->hasErrors();
        $this->assertFalse($hasErrors);

        $nestedEntity = new Entity();
        $entity->set([
            'nested' => $nestedEntity,
        ]);
        $hasErrors = $entity->hasErrors();
        $this->assertFalse($hasErrors);

        $nestedEntity->setError('description', 'oops');
        $hasErrors = $entity->hasErrors();
        $this->assertTrue($hasErrors);

        $hasErrors = $entity->hasErrors(false);
        $this->assertFalse($hasErrors);

        $entity->clean();
        $hasErrors = $entity->hasErrors();
        $this->assertTrue($hasErrors);
        $hasErrors = $entity->hasErrors(false);
        $this->assertFalse($hasErrors);

        $nestedEntity->clean();
        $hasErrors = $entity->hasErrors();
        $this->assertFalse($hasErrors);

        $entity->setError('foo', []);
        $this->assertFalse($entity->hasErrors());
    }

    /**
     * Test that errors can be read with a path.
     */
    public function testErrorPathReading(): void
    {
        $assoc = new Entity();
        $assoc2 = new NonExtending();
        $entity = new Extending([
            'field' => 'value',
            'one' => $assoc,
            'many' => [$assoc2],
        ]);
        $entity->setError('wrong', 'Bad stuff');
        $assoc->setError('nope', 'Terrible things');
        $assoc2->setError('nope', 'Terrible things');

        $this->assertEquals(['Bad stuff'], $entity->getError('wrong'));
        $this->assertEquals(['Terrible things'], $entity->getError('many.0.nope'));
        $this->assertEquals(['Terrible things'], $entity->getError('one.nope'));
        $this->assertEquals(['nope' => ['Terrible things']], $entity->getError('one'));
        $this->assertEquals([0 => ['nope' => ['Terrible things']]], $entity->getError('many'));
        $this->assertEquals(['nope' => ['Terrible things']], $entity->getError('many.0'));

        $this->assertEquals([], $entity->getError('many.0.mistake'));
        $this->assertEquals([], $entity->getError('one.mistake'));
        $this->assertEquals([], $entity->getError('one.1.mistake'));
        $this->assertEquals([], $entity->getError('many.1.nope'));
    }

    /**
     * Tests that changing the value of a property will remove errors
     * stored for it
     */
    public function testDirtyRemovesError(): void
    {
        $entity = new Entity(['a' => 'b']);
        $entity->setError('a', 'is not good');
        $entity->set('a', 'c');
        $this->assertEmpty($entity->getError('a'));

        $entity->setError('a', 'is not good');
        $entity->setDirty('a', true);
        $this->assertEmpty($entity->getError('a'));
    }

    /**
     * Tests that marking an entity as clean will remove errors too
     */
    public function testCleanRemovesErrors(): void
    {
        $entity = new Entity(['a' => 'b']);
        $entity->setError('a', 'is not good');
        $entity->clean();
        $this->assertEmpty($entity->getErrors());
    }

    /**
     * Tests getAccessible() method
     */
    public function testGetAccessible(): void
    {
        $entity = new Entity();
        $entity->setAccess('*', false);
        $entity->setAccess('bar', true);

        $accessible = $entity->getAccessible();
        $expected = [
            '*' => false,
            'bar' => true,
        ];
        $this->assertSame($expected, $accessible);
    }

    /**
     * Tests isAccessible() and setAccess() methods
     */
    public function testIsAccessible(): void
    {
        $entity = new Entity();
        $entity->setAccess('*', false);
        $this->assertFalse($entity->isAccessible('foo'));
        $this->assertFalse($entity->isAccessible('bar'));

        $this->assertSame($entity, $entity->setAccess('foo', true));
        $this->assertTrue($entity->isAccessible('foo'));
        $this->assertFalse($entity->isAccessible('bar'));

        $this->assertSame($entity, $entity->setAccess('bar', true));
        $this->assertTrue($entity->isAccessible('foo'));
        $this->assertTrue($entity->isAccessible('bar'));

        $this->assertSame($entity, $entity->setAccess('foo', false));
        $this->assertFalse($entity->isAccessible('foo'));
        $this->assertTrue($entity->isAccessible('bar'));

        $this->assertSame($entity, $entity->setAccess('bar', false));
        $this->assertFalse($entity->isAccessible('foo'));
        $this->assertFalse($entity->isAccessible('bar'));
    }

    /**
     * Tests that an array can be used to set
     */
    public function testAccessibleAsArray(): void
    {
        $entity = new Entity();
        $entity->setAccess(['foo', 'bar', 'baz'], true);
        $this->assertTrue($entity->isAccessible('foo'));
        $this->assertTrue($entity->isAccessible('bar'));
        $this->assertTrue($entity->isAccessible('baz'));

        $entity->setAccess('foo', false);
        $this->assertFalse($entity->isAccessible('foo'));
        $this->assertTrue($entity->isAccessible('bar'));
        $this->assertTrue($entity->isAccessible('baz'));

        $entity->setAccess(['foo', 'bar', 'baz'], false);
        $this->assertFalse($entity->isAccessible('foo'));
        $this->assertFalse($entity->isAccessible('bar'));
        $this->assertFalse($entity->isAccessible('baz'));
    }

    /**
     * Tests that a wildcard can be used for setting accessible properties
     */
    public function testAccessibleWildcard(): void
    {
        $entity = new Entity();
        $entity->setAccess(['foo', 'bar', 'baz'], true);
        $this->assertTrue($entity->isAccessible('foo'));
        $this->assertTrue($entity->isAccessible('bar'));
        $this->assertTrue($entity->isAccessible('baz'));

        $entity->setAccess('*', false);
        $this->assertFalse($entity->isAccessible('foo'));
        $this->assertFalse($entity->isAccessible('bar'));
        $this->assertFalse($entity->isAccessible('baz'));
        $this->assertFalse($entity->isAccessible('newOne'));

        $entity->setAccess('*', true);
        $this->assertTrue($entity->isAccessible('foo'));
        $this->assertTrue($entity->isAccessible('bar'));
        $this->assertTrue($entity->isAccessible('baz'));
        $this->assertTrue($entity->isAccessible('newOne2'));
    }

    /**
     * Tests that only accessible properties can be set
     */
    public function testSetWithAccessible(): void
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $options = ['guard' => true];
        $entity->setAccess('*', false);
        $entity->setAccess('foo', true);
        $entity->set('bar', 3, $options);
        $entity->set('foo', 4, $options);
        $this->assertSame(2, $entity->get('bar'));
        $this->assertSame(4, $entity->get('foo'));

        $entity->setAccess('bar', true);
        $entity->set('bar', 3, $options);
        $this->assertSame(3, $entity->get('bar'));
    }

    /**
     * Tests that only accessible properties can be set
     */
    public function testSetWithAccessibleWithArray(): void
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $options = ['guard' => true];
        $entity->setAccess('*', false);
        $entity->setAccess('foo', true);
        $entity->set(['bar' => 3, 'foo' => 4], $options);
        $this->assertSame(2, $entity->get('bar'));
        $this->assertSame(4, $entity->get('foo'));

        $entity->setAccess('bar', true);
        $entity->set(['bar' => 3, 'foo' => 5], $options);
        $this->assertSame(3, $entity->get('bar'));
        $this->assertSame(5, $entity->get('foo'));
    }

    /**
     * Test that accessible() and single property setting works.
     */
    public function testSetWithAccessibleSingleProperty(): void
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $entity->setAccess('*', false);
        $entity->setAccess('title', true);

        $entity->set(['title' => 'test', 'body' => 'Nope']);
        $this->assertSame('test', $entity->title);
        $this->assertNull($entity->body);

        $entity->body = 'Yep';
        $this->assertSame('Yep', $entity->body, 'Single set should bypass guards.');

        $entity->set('body', 'Yes');
        $this->assertSame('Yes', $entity->body, 'Single set should bypass guards.');
    }

    /**
     * Tests the entity's __toString method
     */
    public function testToString(): void
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $this->assertEquals(json_encode($entity, JSON_PRETTY_PRINT), (string)$entity);
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $entity = new Entity(['foo' => 'bar'], ['markClean' => true]);
        $entity->somethingElse = 'value';
        $entity->setAccess('id', false);
        $entity->setAccess('name', true);
        $entity->setVirtual(['baz']);
        $entity->setDirty('foo', true);
        $entity->setError('foo', ['An error']);
        $entity->setInvalidField('foo', 'a value');
        $entity->setSource('foos');
        $result = $entity->__debugInfo();
        $expected = [
            'foo' => 'bar',
            'somethingElse' => 'value',
            'baz' => null,
            '[new]' => true,
            '[accessible]' => ['*' => true, 'id' => false, 'name' => true],
            '[dirty]' => ['somethingElse' => true, 'foo' => true],
            '[original]' => [],
            '[virtual]' => ['baz'],
            '[hasErrors]' => true,
            '[errors]' => ['foo' => ['An error']],
            '[invalid]' => ['foo' => 'a value'],
            '[repository]' => 'foos',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test the source getter
     */
    public function testGetAndSetSource(): void
    {
        $entity = new Entity();
        $this->assertSame('', $entity->getSource());
        $entity->setSource('foos');
        $this->assertSame('foos', $entity->getSource());
    }

    /**
     * Provides empty values
     *
     * @return array
     */
    public function emptyNamesProvider(): array
    {
        return [[''], [null]];
    }

    /**
     * Tests that trying to get an empty property name throws exception
     */
    public function testEmptyProperties(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $entity = new Entity();
        $entity->get('');
    }

    /**
     * Tests that setting an empty property name does nothing
     *
     * @dataProvider emptyNamesProvider
     */
    public function testSetEmptyPropertyName(?string $property): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $entity = new Entity();
        $entity->set($property, 'bar');
    }

    /**
     * Provides empty values
     */
    public function testIsDirtyFromClone(): void
    {
        $entity = new Entity(
            ['a' => 1, 'b' => 2],
            ['markNew' => false, 'markClean' => true]
        );

        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->isDirty());

        $cloned = clone $entity;
        $cloned->setNew(true);

        $this->assertTrue($cloned->isDirty());
        $this->assertTrue($cloned->isDirty('a'));
        $this->assertTrue($cloned->isDirty('b'));
    }

    /**
     * Tests getInvalid and setInvalid
     */
    public function testGetSetInvalid(): void
    {
        $entity = new Entity();
        $return = $entity->setInvalid([
            'title' => 'albert',
            'body' => 'einstein',
        ]);
        $this->assertSame($entity, $return);
        $this->assertSame([
            'title' => 'albert',
            'body' => 'einstein',
        ], $entity->getInvalid());

        $set = $entity->setInvalid([
            'title' => 'nikola',
            'body' => 'tesla',
        ]);
        $this->assertSame([
            'title' => 'albert',
            'body' => 'einstein',
        ], $set->getInvalid());

        $overwrite = $entity->setInvalid([
            'title' => 'nikola',
            'body' => 'tesla',
        ], true);
        $this->assertSame($entity, $overwrite);
        $this->assertSame([
            'title' => 'nikola',
            'body' => 'tesla',
        ], $entity->getInvalid());
    }

    /**
     * Tests getInvalidField
     */
    public function testGetSetInvalidField(): void
    {
        $entity = new Entity();
        $return = $entity->setInvalidField('title', 'albert');
        $this->assertSame($entity, $return);
        $this->assertSame('albert', $entity->getInvalidField('title'));

        $overwrite = $entity->setInvalidField('title', 'nikola');
        $this->assertSame($entity, $overwrite);
        $this->assertSame('nikola', $entity->getInvalidField('title'));
    }

    /**
     * Tests getInvalidFieldNull
     */
    public function testGetInvalidFieldNull(): void
    {
        $entity = new Entity();
        $this->assertNull($entity->getInvalidField('foo'));
    }

    /**
     * Test the isEmpty() check
     */
    public function testIsEmpty(): void
    {
        $entity = new Entity([
            'array' => ['foo' => 'bar'],
            'emptyArray' => [],
            'object' => new stdClass(),
            'string' => 'string',
            'stringZero' => '0',
            'emptyString' => '',
            'intZero' => 0,
            'intNotZero' => 1,
            'floatZero' => 0.0,
            'floatNonZero' => 1.5,
            'null' => null,
        ]);

        $this->assertFalse($entity->isEmpty('array'));
        $this->assertTrue($entity->isEmpty('emptyArray'));
        $this->assertFalse($entity->isEmpty('object'));
        $this->assertFalse($entity->isEmpty('string'));
        $this->assertFalse($entity->isEmpty('stringZero'));
        $this->assertTrue($entity->isEmpty('emptyString'));
        $this->assertFalse($entity->isEmpty('intZero'));
        $this->assertFalse($entity->isEmpty('intNotZero'));
        $this->assertFalse($entity->isEmpty('floatZero'));
        $this->assertFalse($entity->isEmpty('floatNonZero'));
        $this->assertTrue($entity->isEmpty('null'));
    }

    /**
     * Test hasValue()
     */
    public function testHasValue(): void
    {
        $entity = new Entity([
            'array' => ['foo' => 'bar'],
            'emptyArray' => [],
            'object' => new stdClass(),
            'string' => 'string',
            'stringZero' => '0',
            'emptyString' => '',
            'intZero' => 0,
            'intNotZero' => 1,
            'floatZero' => 0.0,
            'floatNonZero' => 1.5,
            'null' => null,
        ]);

        $this->assertTrue($entity->hasValue('array'));
        $this->assertFalse($entity->hasValue('emptyArray'));
        $this->assertTrue($entity->hasValue('object'));
        $this->assertTrue($entity->hasValue('string'));
        $this->assertTrue($entity->hasValue('stringZero'));
        $this->assertFalse($entity->hasValue('emptyString'));
        $this->assertTrue($entity->hasValue('intZero'));
        $this->assertTrue($entity->hasValue('intNotZero'));
        $this->assertTrue($entity->hasValue('floatZero'));
        $this->assertTrue($entity->hasValue('floatNonZero'));
        $this->assertFalse($entity->hasValue('null'));
    }
}
