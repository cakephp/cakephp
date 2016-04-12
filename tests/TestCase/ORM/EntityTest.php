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

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use TestApp\Model\Entity\Extending;
use TestApp\Model\Entity\NonExtending;

/**
 * Entity test case.
 */
class EntityTest extends TestCase
{

    /**
     * Tests setting a single property in an entity without custom setters
     *
     * @return void
     */
    public function testSetOneParamNoSetters()
    {
        $entity = new Entity;
        $this->assertNull($entity->getOriginal('foo'));
        $entity->set('foo', 'bar');
        $this->assertEquals('bar', $entity->foo);
        $this->assertEquals('bar', $entity->getOriginal('foo'));

        $entity->set('foo', 'baz');
        $this->assertEquals('baz', $entity->foo);
        $this->assertEquals('bar', $entity->getOriginal('foo'));

        $entity->set('id', 1);
        $this->assertSame(1, $entity->id);
        $this->assertEquals(1, $entity->getOriginal('id'));
        $this->assertEquals('bar', $entity->getOriginal('foo'));
    }

    /**
     * Tests setting multiple properties without custom setters
     *
     * @return void
     */
    public function testSetMultiplePropertiesNoSetters()
    {
        $entity = new Entity;
        $entity->accessible('*', true);

        $entity->set(['foo' => 'bar', 'id' => 1]);
        $this->assertEquals('bar', $entity->foo);
        $this->assertSame(1, $entity->id);

        $entity->set(['foo' => 'baz', 'id' => 2, 'thing' => 3]);
        $this->assertEquals('baz', $entity->foo);
        $this->assertSame(2, $entity->id);
        $this->assertSame(3, $entity->thing);
        $this->assertEquals('bar', $entity->getOriginal('foo'));
        $this->assertEquals(1, $entity->getOriginal('id'));
    }

    /**
     * Test that getOriginal() retains falsey values.
     *
     * @return void
     */
    public function testGetOriginal()
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
     *
     * @return void
     */
    public function testExtractOriginal()
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
     * Tests setting a single property using a setter function
     *
     * @return void
     */
    public function testSetOneParamWithSetter()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_setName']);
        $entity->expects($this->once())->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertEquals('Jones', $name);
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertEquals('Dr. Jones', $entity->name);
    }

    /**
     * Tests setting multiple properties using a setter function
     *
     * @return void
     */
    public function testMultipleWithSetter()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_setName', '_setStuff']);
        $entity->accessible('*', true);
        $entity->expects($this->once())->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertEquals('Jones', $name);
                return 'Dr. ' . $name;
            }));
        $entity->expects($this->once())->method('_setStuff')
            ->with(['a', 'b'])
            ->will($this->returnCallback(function ($stuff) {
                $this->assertEquals(['a', 'b'], $stuff);
                return ['c', 'd'];
            }));
        $entity->set(['name' => 'Jones', 'stuff' => ['a', 'b']]);
        $this->assertEquals('Dr. Jones', $entity->name);
        $this->assertEquals(['c', 'd'], $entity->stuff);
    }

    /**
     * Tests that it is possible to bypass the setters
     *
     * @return void
     */
    public function testBypassSetters()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_setName', '_setStuff']);
        $entity->accessible('*', true);

        $entity->expects($this->never())->method('_setName');
        $entity->expects($this->never())->method('_setStuff');

        $entity->set('name', 'Jones', ['setter' => false]);
        $this->assertEquals('Jones', $entity->name);

        $entity->set('stuff', 'Thing', ['setter' => false]);
        $this->assertEquals('Thing', $entity->stuff);

        $entity->set(['name' => 'foo', 'stuff' => 'bar'], ['setter' => false]);
        $this->assertEquals('bar', $entity->stuff);
    }

    /**
     * Tests that the constructor will set initial properties
     *
     * @return void
     */
    public function testConstructor()
    {
        $entity = $this->getMockBuilder('\Cake\ORM\Entity')
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->at(0))
            ->method('set')
            ->with(['a' => 'b', 'c' => 'd'], ['setter' => true, 'guard' => false]);

        $entity->expects($this->at(1))
            ->method('set')
            ->with(['foo' => 'bar'], ['setter' => false, 'guard' => false]);

        $entity->__construct(['a' => 'b', 'c' => 'd']);
        $entity->__construct(['foo' => 'bar'], ['useSetters' => false]);
    }

    /**
     * Tests that the constructor will set initial properties and pass the guard
     * option along
     *
     * @return void
     */
    public function testConstructorWithGuard()
    {
        $entity = $this->getMockBuilder('\Cake\ORM\Entity')
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())
            ->method('set')
            ->with(['foo' => 'bar'], ['setter' => true, 'guard' => true]);
        $entity->__construct(['foo' => 'bar'], ['guard' => true]);
    }

    /**
     * Tests getting properties with no custom getters
     *
     * @return void
     */
    public function testGetNoGetters()
    {
        $entity = new Entity(['id' => 1, 'foo' => 'bar']);
        $this->assertSame(1, $entity->get('id'));
        $this->assertSame('bar', $entity->get('foo'));
    }

    /**
     * Tests get with custom getter
     *
     * @return void
     */
    public function testGetCustomGetters()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getName']);
        $entity->expects($this->any())
            ->method('_getName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertEquals('Dr. Jones', $entity->get('name'));
        $this->assertEquals('Dr. Jones', $entity->get('name'));
    }

    /**
     * Tests get with custom getter
     *
     * @return void
     */
    public function testGetCustomGettersAfterSet()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getName']);
        $entity->expects($this->any())
            ->method('_getName')
            ->will($this->returnCallback(function ($name) {
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertEquals('Dr. Jones', $entity->get('name'));
        $this->assertEquals('Dr. Jones', $entity->get('name'));

        $entity->set('name', 'Mark');
        $this->assertEquals('Dr. Mark', $entity->get('name'));
        $this->assertEquals('Dr. Mark', $entity->get('name'));
    }

    /**
     * Tests that the get cache is cleared by unsetProperty.
     *
     * @return void
     */
    public function testGetCacheClearedByUnset()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getName']);
        $entity->expects($this->any())->method('_getName')
            ->will($this->returnCallback(function ($name) {
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertEquals('Dr. Jones', $entity->get('name'));

        $entity->unsetProperty('name');
        $this->assertEquals('Dr. ', $entity->get('name'));
    }

    /**
     * Test getting camelcased virtual fields.
     *
     * @return void
     */
    public function testGetCamelCasedProperties()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getListIdName']);
        $entity->expects($this->any())->method('_getListIdName')
            ->will($this->returnCallback(function ($name) {
                return 'A name';
            }));
        $entity->virtualProperties(['ListIdName']);
        $this->assertSame('A name', $entity->list_id_name, 'underscored virtual field should be accessible');
        $this->assertSame('A name', $entity->listIdName, 'Camelbacked virtual field should be accessible');
    }

    /**
     * Test magic property setting with no custom setter
     *
     * @return void
     */
    public function testMagicSet()
    {
        $entity = new Entity;
        $entity->name = 'Jones';
        $this->assertEquals('Jones', $entity->name);
        $entity->name = 'George';
        $this->assertEquals('George', $entity->name);
    }

    /**
     * Tests magic set with custom setter function
     *
     * @return void
     */
    public function testMagicSetWithSetter()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_setName']);
        $entity->expects($this->once())->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertEquals('Jones', $name);
                return 'Dr. ' . $name;
            }));
        $entity->name = 'Jones';
        $this->assertEquals('Dr. Jones', $entity->name);
    }

    /**
     * Tests magic set with custom setter function using a Title cased property
     *
     * @return void
     */
    public function testMagicSetWithSetterTitleCase()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_setName']);
        $entity->expects($this->once())
            ->method('_setName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertEquals('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->Name = 'Jones';
        $this->assertEquals('Dr. Jones', $entity->Name);
    }

    /**
     * Tests the magic getter with a custom getter function
     *
     * @return void
     */
    public function testMagicGetWithGetter()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getName']);
        $entity->expects($this->once())->method('_getName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertSame('Jones', $name);
                return 'Dr. ' . $name;
            }));
        $entity->set('name', 'Jones');
        $this->assertEquals('Dr. Jones', $entity->name);
    }

    /**
     * Tests magic get with custom getter function using a Title cased property
     *
     * @return void
     */
    public function testMagicGetWithGetterTitleCase()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getName']);
        $entity->expects($this->once())
            ->method('_getName')
            ->with('Jones')
            ->will($this->returnCallback(function ($name) {
                $this->assertEquals('Jones', $name);

                return 'Dr. ' . $name;
            }));
        $entity->set('Name', 'Jones');
        $this->assertEquals('Dr. Jones', $entity->Name);
    }

    /**
     * Test indirectly modifying internal properties
     *
     * @return void
     */
    public function testIndirectModification()
    {
        $entity = new Entity;
        $entity->things = ['a', 'b'];
        $entity->things[] = 'c';
        $this->assertEquals(['a', 'b', 'c'], $entity->things);
    }

    /**
     * Tests has() method
     *
     * @return void
     */
    public function testHas()
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

        $entity = $this->getMock('\Cake\ORM\Entity', ['_getThings']);
        $entity->expects($this->once())->method('_getThings')
            ->will($this->returnValue(0));
        $this->assertTrue($entity->has('things'));
    }

    /**
     * Tests unsetProperty one property at a time
     *
     * @return void
     */
    public function testUnset()
    {
        $entity = new Entity(['id' => 1, 'name' => 'bar']);
        $entity->unsetProperty('id');
        $this->assertFalse($entity->has('id'));
        $this->assertTrue($entity->has('name'));
        $entity->unsetProperty('name');
        $this->assertFalse($entity->has('id'));
    }

    /**
     * Unsetting a property should not mark it as dirty.
     *
     * @return void
     */
    public function testUnsetMakesClean()
    {
        $entity = new Entity(['id' => 1, 'name' => 'bar']);
        $this->assertTrue($entity->dirty('name'));
        $entity->unsetProperty('name');
        $this->assertFalse($entity->dirty('name'), 'Removed properties are not dirty.');
    }

    /**
     * Tests unsetProperty whith multiple properties
     *
     * @return void
     */
    public function testUnsetMultiple()
    {
        $entity = new Entity(['id' => 1, 'name' => 'bar', 'thing' => 2]);
        $entity->unsetProperty(['id', 'thing']);
        $this->assertFalse($entity->has('id'));
        $this->assertTrue($entity->has('name'));
        $this->assertFalse($entity->has('thing'));
    }

    /**
     * Tests the magic __isset() method
     *
     * @return void
     */
    public function testMagicIsset()
    {
        $entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
        $this->assertTrue(isset($entity->id));
        $this->assertTrue(isset($entity->name));
        $this->assertFalse(isset($entity->foo));
        $this->assertFalse(isset($entity->thing));
    }

    /**
     * Tests the magic __unset() method
     *
     * @return void
     */
    public function testMagicUnset()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['unsetProperty']);
        $entity->expects($this->at(0))
            ->method('unsetProperty')
            ->with('foo');
        unset($entity->foo);
    }

    /**
     * Tests isset with array access
     *
     * @return void
     */
    public function testIssetArrayAccess()
    {
        $entity = new Entity(['id' => 1, 'name' => 'Juan', 'foo' => null]);
        $this->assertTrue(isset($entity['id']));
        $this->assertTrue(isset($entity['name']));
        $this->assertFalse(isset($entity['foo']));
        $this->assertFalse(isset($entity['thing']));
    }

    /**
     * Tests get property with array access
     *
     * @return void
     */
    public function testGetArrayAccess()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['get']);
        $entity->expects($this->at(0))
            ->method('get')
            ->with('foo')
            ->will($this->returnValue('worked'));

        $entity->expects($this->at(1))
            ->method('get')
            ->with('bar')
            ->will($this->returnValue('worked too'));

        $this->assertEquals('worked', $entity['foo']);
        $this->assertEquals('worked too', $entity['bar']);
    }

    /**
     * Tests set with array access
     *
     * @return void
     */
    public function testSetArrayAccess()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['set']);
        $entity->accessible('*', true);

        $entity->expects($this->at(0))
            ->method('set')
            ->with('foo', 1)
            ->will($this->returnSelf());

        $entity->expects($this->at(1))
            ->method('set')
            ->with('bar', 2)
            ->will($this->returnSelf());

        $entity['foo'] = 1;
        $entity['bar'] = 2;
    }

    /**
     * Tests unset with array access
     *
     * @return void
     */
    public function testUnsetArrayAccess()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['unsetProperty']);
        $entity->expects($this->at(0))
            ->method('unsetProperty')
            ->with('foo');
        unset($entity['foo']);
    }

    /**
     * Tests that the method cache will only report the methods for the called class,
     * this is, calling methods defined in another entity will not cause a fatal error
     * when trying to call directly an inexistent method in another class
     *
     * @return void
     */
    public function testMethodCache()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_setFoo', '_getBar']);
        $entity2 = $this->getMock('\Cake\ORM\Entity', ['_setBar']);
        $entity->expects($this->once())->method('_setFoo');
        $entity->expects($this->once())->method('_getBar');
        $entity2->expects($this->once())->method('_setBar');

        $entity->set('foo', 1);
        $entity->get('bar');
        $entity2->set('bar', 1);
    }

    /**
     * Tests that long properties in the entity are inflected correctly
     *
     * @return void
     */
    public function testSetGetLongProperyNames()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getVeryLongProperty', '_setVeryLongProperty']);
        $entity->expects($this->once())->method('_getVeryLongProperty');
        $entity->expects($this->once())->method('_setVeryLongProperty');
        $entity->get('very_long_property');
        $entity->set('very_long_property', 1);
    }

    /**
     * Tests serializing an entity as json
     *
     * @return void
     */
    public function testJsonSerialize()
    {
        $data = ['name' => 'James', 'age' => 20, 'phones' => ['123', '457']];
        $entity = new Entity($data);
        $this->assertEquals(json_encode($data), json_encode($entity));
    }

    /**
     * Tests the extract method
     *
     * @return void
     */
    public function testExtract()
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3
        ]);
        $expected = ['author_id' => 3, 'title' => 'Foo', ];
        $this->assertEquals($expected, $entity->extract(['author_id', 'title']));

        $expected = ['id' => 1];
        $this->assertEquals($expected, $entity->extract(['id']));

        $expected = [];
        $this->assertEquals($expected, $entity->extract([]));

        $expected = ['id' => 1, 'crazyness' => null];
        $this->assertEquals($expected, $entity->extract(['id', 'crazyness']));
    }

    /**
     * Tests dirty() method on a newly created object
     *
     * @return void
     */
    public function testDirty()
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3
        ]);
        $this->assertTrue($entity->dirty('id'));
        $this->assertTrue($entity->dirty('title'));
        $this->assertTrue($entity->dirty('author_id'));

        $this->assertTrue($entity->dirty());

        $entity->dirty('id', false);
        $this->assertFalse($entity->dirty('id'));
        $this->assertTrue($entity->dirty('title'));
        $entity->dirty('title', false);
        $this->assertFalse($entity->dirty('title'));
        $this->assertTrue($entity->dirty());
        $entity->dirty('author_id', false);
        $this->assertFalse($entity->dirty());
    }

    /**
     * Tests dirty() when altering properties values and adding new ones
     *
     * @return void
     */
    public function testDirtyChangingProperties()
    {
        $entity = new Entity([
            'title' => 'Foo',
        ]);

        $entity->dirty('title', false);
        $this->assertFalse($entity->dirty('title'));

        $entity->set('title', 'Foo');
        $this->assertTrue($entity->dirty('title'));

        $entity->set('title', 'Foo');
        $this->assertTrue($entity->dirty('title'));

        $entity->set('something', 'else');
        $this->assertTrue($entity->dirty('something'));
    }

    /**
     * Tests extract only dirty properties
     *
     * @return void
     */
    public function testExtractDirty()
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3
        ]);
        $entity->dirty('id', false);
        $entity->dirty('title', false);
        $expected = ['author_id' => 3];
        $result = $entity->extract(['id', 'title', 'author_id'], true);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the clean method
     *
     * @return void
     */
    public function testClean()
    {
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3
        ]);
        $this->assertTrue($entity->dirty('id'));
        $this->assertTrue($entity->dirty('title'));
        $this->assertTrue($entity->dirty('author_id'));

        $entity->clean();
        $this->assertFalse($entity->dirty('id'));
        $this->assertFalse($entity->dirty('title'));
        $this->assertFalse($entity->dirty('author_id'));
    }

    /**
     * Tests the isNew method
     *
     * @return void
     */
    public function testIsNew()
    {
        $data = [
            'id' => 1,
            'title' => 'Foo',
            'author_id' => 3
        ];
        $entity = new Entity($data);
        $this->assertTrue($entity->isNew());

        $entity->isNew(true);
        $this->assertTrue($entity->isNew());

        $entity->isNew('derpy');
        $this->assertTrue($entity->isNew());

        $entity->isNew(false);
        $this->assertFalse($entity->isNew());
    }

    /**
     * Tests the constructor when passing the markClean option
     *
     * @return void
     */
    public function testConstructorWithClean()
    {
        $entity = $this->getMockBuilder('\Cake\ORM\Entity')
            ->setMethods(['clean'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->never())->method('clean');
        $entity->__construct(['a' => 'b', 'c' => 'd']);

        $entity = $this->getMockBuilder('\Cake\ORM\Entity')
            ->setMethods(['clean'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())->method('clean');
        $entity->__construct(['a' => 'b', 'c' => 'd'], ['markClean' => true]);
    }

    /**
     * Tests the constructor when passing the markClean option
     *
     * @return void
     */
    public function testConstructorWithMarkNew()
    {
        $entity = $this->getMockBuilder('\Cake\ORM\Entity')
            ->setMethods(['isNew', 'clean'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->never())->method('clean');
        $entity->__construct(['a' => 'b', 'c' => 'd']);

        $entity = $this->getMockBuilder('\Cake\ORM\Entity')
            ->setMethods(['isNew'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())->method('isNew');
        $entity->__construct(['a' => 'b', 'c' => 'd'], ['markNew' => true]);
    }

    /**
     * Test toArray method.
     *
     * @return void
     */
    public function testToArray()
    {
        $data = ['name' => 'James', 'age' => 20, 'phones' => ['123', '457']];
        $entity = new Entity($data);

        $this->assertEquals($data, $entity->toArray());
    }

    /**
     * Test toArray recursive.
     *
     * @return void
     */
    public function testToArrayRecursive()
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
            ]
        ];
        $this->assertEquals($expected, $user->toArray());
    }

    /**
     * Tests that an entity with entities and other misc types can be properly toArray'd
     *
     * @return void
     */
    public function testToArrayMixed()
    {
        $test = new Entity([
            'id' => 1,
            'foo' => [
                new Entity(['hi' => 'test']),
                'notentity' => 1
            ]
        ]);
        $expected = [
            'id' => 1,
            'foo' => [
                ['hi' => 'test'],
                'notentity' => 1
            ]
        ];
        $this->assertEquals($expected, $test->toArray());
    }

    /**
     * Test that get accessors are called when converting to arrays.
     *
     * @return void
     */
    public function testToArrayWithAccessor()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getName']);
        $entity->accessible('*', true);
        $entity->set(['name' => 'Mark', 'email' => 'mark@example.com']);
        $entity->expects($this->any())
            ->method('_getName')
            ->will($this->returnValue('Jose'));

        $expected = ['name' => 'Jose', 'email' => 'mark@example.com'];
        $this->assertEquals($expected, $entity->toArray());
    }

    /**
     * Test that toArray respects hidden properties.
     *
     * @return void
     */
    public function testToArrayHiddenProperties()
    {
        $data = ['secret' => 'sauce', 'name' => 'mark', 'id' => 1];
        $entity = new Entity($data);
        $entity->hiddenProperties(['secret']);
        $this->assertEquals(['name' => 'mark', 'id' => 1], $entity->toArray());
    }

    /**
     * Test toArray includes 'virtual' properties.
     *
     * @return void
     */
    public function testToArrayVirtualProperties()
    {
        $entity = $this->getMock('\Cake\ORM\Entity', ['_getName']);
        $entity->accessible('*', true);

        $entity->expects($this->any())
            ->method('_getName')
            ->will($this->returnValue('Jose'));
        $entity->set(['email' => 'mark@example.com']);

        $entity->virtualProperties(['name']);
        $expected = ['name' => 'Jose', 'email' => 'mark@example.com'];
        $this->assertEquals($expected, $entity->toArray());

        $this->assertEquals(['name'], $entity->virtualProperties());

        $entity->hiddenProperties(['name']);
        $expected = ['email' => 'mark@example.com'];
        $this->assertEquals($expected, $entity->toArray());
        $this->assertEquals(['name'], $entity->hiddenProperties());
    }

    /**
     * Tests the errors method
     *
     * @return void
     */
    public function testErrors()
    {
        $entity = new Entity;
        $this->assertEmpty($entity->errors());
        $this->assertSame($entity, $entity->errors('foo', 'bar'));
        $this->assertEquals(['bar'], $entity->errors('foo'));

        $this->assertEquals([], $entity->errors('boo'));
        $entity['boo'] = [
            'someting' => 'stupid',
            'and' => false
        ];
        $this->assertEquals([], $entity->errors('boo'));

        $entity->errors('foo', 'other error');
        $this->assertEquals(['bar', 'other error'], $entity->errors('foo'));

        $entity->errors('bar', ['something', 'bad']);
        $this->assertEquals(['something', 'bad'], $entity->errors('bar'));

        $expected = ['foo' => ['bar', 'other error'], 'bar' => ['something', 'bad']];
        $this->assertEquals($expected, $entity->errors());

        $errors = ['foo' => ['something'], 'bar' => 'else', 'baz' => ['error']];
        $this->assertSame($entity, $entity->errors($errors, null, true));
        $errors['bar'] = ['else'];
        $this->assertEquals($errors, $entity->errors());
    }

    /**
     * Tests that it is possible to get errors for nested entities
     *
     * @return void
     */
    public function testErrorsDeep()
    {
        $user = new Entity();
        $owner = new NonExtending();
        $author = new Extending([
            'foo' => 'bar',
            'thing' => 'baz',
            'user' => $user,
            'owner' => $owner
        ]);
        $author->errors('thing', ['this is a mistake']);
        $user->errors(['a' => ['error1'], 'b' => ['error2']]);
        $owner->errors(['c' => ['error3'], 'd' => ['error4']]);

        $expected = ['a' => ['error1'], 'b' => ['error2']];
        $this->assertEquals($expected, $author->errors('user'));

        $expected = ['c' => ['error3'], 'd' => ['error4']];
        $this->assertEquals($expected, $author->errors('owner'));

        $author->set('multiple', [$user, $owner]);
        $expected = [
            ['a' => ['error1'], 'b' => ['error2']],
            ['c' => ['error3'], 'd' => ['error4']]
        ];
        $this->assertEquals($expected, $author->errors('multiple'));

        $expected = [
            'thing' => $author->errors('thing'),
            'user' => $author->errors('user'),
            'owner' => $author->errors('owner'),
            'multiple' => $author->errors('multiple')
        ];
        $this->assertEquals($expected, $author->errors());
    }

    /**
     * Test that errors can be read with a path.
     *
     * @return void
     */
    public function testErrorPathReading()
    {
        $assoc = new Entity();
        $assoc2 = new NonExtending();
        $entity = new Extending([
            'field' => 'value',
            'one' => $assoc,
            'many' => [$assoc2]
        ]);
        $entity->errors('wrong', 'Bad stuff');
        $assoc->errors('nope', 'Terrible things');
        $assoc2->errors('nope', 'Terrible things');

        $this->assertEquals(['Bad stuff'], $entity->errors('wrong'));
        $this->assertEquals(['Terrible things'], $entity->errors('many.0.nope'));
        $this->assertEquals(['Terrible things'], $entity->errors('one.nope'));
        $this->assertEquals(['nope' => ['Terrible things']], $entity->errors('one'));
        $this->assertEquals([0 => ['nope' => ['Terrible things']]], $entity->errors('many'));
        $this->assertEquals(['nope' => ['Terrible things']], $entity->errors('many.0'));

        $this->assertEquals([], $entity->errors('many.0.mistake'));
        $this->assertEquals([], $entity->errors('one.mistake'));
        $this->assertEquals([], $entity->errors('one.1.mistake'));
        $this->assertEquals([], $entity->errors('many.1.nope'));
    }

    /**
     * Tests that changing the value of a property will remove errors
     * stored for it
     *
     * @return void
     */
    public function testDirtyRemovesError()
    {
        $entity = new Entity(['a' => 'b']);
        $entity->errors('a', 'is not good');
        $entity->set('a', 'c');
        $this->assertEmpty($entity->errors('a'));

        $entity->errors('a', 'is not good');
        $entity->dirty('a', true);
        $this->assertEmpty($entity->errors('a'));
    }

    /**
     * Tests that marking an entity as clean will remove errors too
     *
     * @return void
     */
    public function testCleanRemovesErrors()
    {
        $entity = new Entity(['a' => 'b']);
        $entity->errors('a', 'is not good');
        $entity->clean();
        $this->assertEmpty($entity->errors());
    }

    /**
     * Tests accessible() method as a getter and setter
     *
     * @return void
     */
    public function testAccessible()
    {
        $entity = new Entity;
        $entity->accessible('*', false);
        $this->assertFalse($entity->accessible('foo'));
        $this->assertFalse($entity->accessible('bar'));

        $this->assertSame($entity, $entity->accessible('foo', true));
        $this->assertTrue($entity->accessible('foo'));
        $this->assertFalse($entity->accessible('bar'));

        $this->assertSame($entity, $entity->accessible('bar', true));
        $this->assertTrue($entity->accessible('foo'));
        $this->assertTrue($entity->accessible('bar'));

        $this->assertSame($entity, $entity->accessible('foo', false));
        $this->assertFalse($entity->accessible('foo'));
        $this->assertTrue($entity->accessible('bar'));

        $this->assertSame($entity, $entity->accessible('bar', false));
        $this->assertFalse($entity->accessible('foo'));
        $this->assertFalse($entity->accessible('bar'));
    }

    /**
     * Tests that an array can be used to set
     *
     * @return void
     */
    public function testAccessibleAsArray()
    {
        $entity = new Entity;
        $entity->accessible(['foo', 'bar', 'baz'], true);
        $this->assertTrue($entity->accessible('foo'));
        $this->assertTrue($entity->accessible('bar'));
        $this->assertTrue($entity->accessible('baz'));

        $entity->accessible('foo', false);
        $this->assertFalse($entity->accessible('foo'));
        $this->assertTrue($entity->accessible('bar'));
        $this->assertTrue($entity->accessible('baz'));

        $entity->accessible(['foo', 'bar', 'baz'], false);
        $this->assertFalse($entity->accessible('foo'));
        $this->assertFalse($entity->accessible('bar'));
        $this->assertFalse($entity->accessible('baz'));
    }

    /**
     * Tests that a wildcard can be used for setting accesible properties
     *
     * @return void
     */
    public function testAccessibleWildcard()
    {
        $entity = new Entity;
        $entity->accessible(['foo', 'bar', 'baz'], true);
        $this->assertTrue($entity->accessible('foo'));
        $this->assertTrue($entity->accessible('bar'));
        $this->assertTrue($entity->accessible('baz'));

        $entity->accessible('*', false);
        $this->assertFalse($entity->accessible('foo'));
        $this->assertFalse($entity->accessible('bar'));
        $this->assertFalse($entity->accessible('baz'));
        $this->assertFalse($entity->accessible('newOne'));

        $entity->accessible('*', true);
        $this->assertTrue($entity->accessible('foo'));
        $this->assertTrue($entity->accessible('bar'));
        $this->assertTrue($entity->accessible('baz'));
        $this->assertTrue($entity->accessible('newOne2'));
    }

    /**
     * Tests that only accessible properties can be set
     *
     * @return void
     */
    public function testSetWithAccessible()
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $options = ['guard' => true];
        $entity->accessible('*', false);
        $entity->accessible('foo', true);
        $entity->set('bar', 3, $options);
        $entity->set('foo', 4, $options);
        $this->assertEquals(2, $entity->get('bar'));
        $this->assertEquals(4, $entity->get('foo'));

        $entity->accessible('bar', true);
        $entity->set('bar', 3, $options);
        $this->assertEquals(3, $entity->get('bar'));
    }

    /**
     * Tests that only accessible properties can be set
     *
     * @return void
     */
    public function testSetWithAccessibleWithArray()
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $options = ['guard' => true];
        $entity->accessible('*', false);
        $entity->accessible('foo', true);
        $entity->set(['bar' => 3, 'foo' => 4], $options);
        $this->assertEquals(2, $entity->get('bar'));
        $this->assertEquals(4, $entity->get('foo'));

        $entity->accessible('bar', true);
        $entity->set(['bar' => 3, 'foo' => 5], $options);
        $this->assertEquals(3, $entity->get('bar'));
        $this->assertEquals(5, $entity->get('foo'));
    }

    /**
     * Test that accessible() and single property setting works.
     *
     * @return void
     */
    public function testSetWithAccessibleSingleProperty()
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $entity->accessible('*', false);
        $entity->accessible('title', true);

        $entity->set(['title' => 'test', 'body' => 'Nope']);
        $this->assertEquals('test', $entity->title);
        $this->assertNull($entity->body);

        $entity->body = 'Yep';
        $this->assertEquals('Yep', $entity->body, 'Single set should bypass guards.');

        $entity->set('body', 'Yes');
        $this->assertEquals('Yes', $entity->body, 'Single set should bypass guards.');
    }

    /**
     * Tests the entity's __toString method
     *
     * @return void
     */
    public function testToString()
    {
        $entity = new Entity(['foo' => 1, 'bar' => 2]);
        $this->assertEquals(json_encode($entity, JSON_PRETTY_PRINT), (string)$entity);
    }

    /**
     * Tests __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $entity = new Entity(['foo' => 'bar'], ['markClean' => true]);
        $entity->somethingElse = 'value';
        $entity->accessible('name', true);
        $entity->virtualProperties(['baz']);
        $entity->dirty('foo', true);
        $entity->errors('foo', ['An error']);
        $entity->invalid('foo', 'a value');
        $entity->source('foos');
        $result = $entity->__debugInfo();
        $expected = [
            'foo' => 'bar',
            'somethingElse' => 'value',
            '[new]' => true,
            '[accessible]' => ['*' => true, 'name' => true],
            '[dirty]' => ['somethingElse' => true, 'foo' => true],
            '[original]' => [],
            '[virtual]' => ['baz'],
            '[errors]' => ['foo' => ['An error']],
            '[invalid]' => ['foo' => 'a value'],
            '[repository]' => 'foos'
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Tests the source method
     *
     * @return void
     */
    public function testSource()
    {
        $entity = new Entity;
        $this->assertNull($entity->source());
        $entity->source('foos');
        $this->assertEquals('foos', $entity->source());
    }

    /**
     * Provides empty values
     *
     * @return void
     */
    public function emptyNamesProvider()
    {
        return [[''], [null], [false]];
    }
    /**
     * Tests that trying to get an empty propery name throws exception
     *
     * @dataProvider emptyNamesProvider
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testEmptyProperties($property)
    {
        $entity = new Entity();
        $entity->get($property);
    }

    /**
     * Tests that setitng an empty property name does nothing
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider emptyNamesProvider
     * @return void
     */
    public function testSetEmptyPropertyName($property)
    {
        $entity = new Entity();
        $entity->set($property, 'bar');
    }

    /**
     * Provides empty values
     *
     * @return void
     */
    public function testIsDirtyFromClone()
    {
        $entity = new Entity(
            ['a' => 1, 'b' => 2],
            ['markNew' => false, 'markClean' => true]
        );

        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->dirty());

        $cloned = clone $entity;
        $cloned->isNew(true);

        $this->assertTrue($cloned->dirty());
        $this->assertTrue($cloned->dirty('a'));
        $this->assertTrue($cloned->dirty('b'));
    }
}
