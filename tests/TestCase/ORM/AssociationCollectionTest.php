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

use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\AssociationCollection;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorInterface;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * AssociationCollection test case.
 */
class AssociationCollectionTest extends TestCase
{
    /**
     * @var AssociationCollection
     */
    protected $associations;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->associations = new AssociationCollection();
    }

    /**
     * Test the constructor.
     */
    public function testConstructor(): void
    {
        $this->assertSame($this->getTableLocator(), $this->associations->getTableLocator());

        $tableLocator = $this->createMock(LocatorInterface::class);
        $associations = new AssociationCollection($tableLocator);
        $this->assertSame($tableLocator, $associations->getTableLocator());
    }

    /**
     * Test the simple add/has and get methods.
     */
    public function testAddHasRemoveAndGet(): void
    {
        $this->assertFalse($this->associations->has('users'));
        $this->assertFalse($this->associations->has('Users'));

        $this->assertNull($this->associations->get('users'));
        $this->assertNull($this->associations->get('Users'));

        $belongsTo = new BelongsTo('');
        $this->assertSame($belongsTo, $this->associations->add('Users', $belongsTo));
        $this->assertFalse($this->associations->has('users'));
        $this->assertTrue($this->associations->has('Users'));

        $this->assertSame($belongsTo, $this->associations->get('Users'));

        $this->associations->remove('Users');

        $this->assertFalse($this->associations->has('Users'));
        $this->assertNull($this->associations->get('Users'));
    }

    /**
     * Test the load method.
     */
    public function testLoad(): void
    {
        $this->associations->load(BelongsTo::class, 'Users');
        $this->assertTrue($this->associations->has('Users'));
        $this->assertInstanceOf(BelongsTo::class, $this->associations->get('Users'));
        $this->assertSame($this->associations->getTableLocator(), $this->associations->get('Users')->getTableLocator());
    }

    /**
     * Test the load method with custom locator.
     */
    public function testLoadCustomLocator(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $this->associations->load(BelongsTo::class, 'Users', [
            'tableLocator' => $locator,
        ]);
        $this->assertTrue($this->associations->has('Users'));
        $this->assertInstanceOf(BelongsTo::class, $this->associations->get('Users'));
        $this->assertSame($locator, $this->associations->get('Users')->getTableLocator());
    }

    /**
     * Test removeAll method
     */
    public function testRemoveAll(): void
    {
        $this->assertEmpty($this->associations->keys());

        $belongsTo = new BelongsTo('');
        $this->assertSame($belongsTo, $this->associations->add('Users', $belongsTo));
        $belongsToMany = new BelongsToMany('');
        $this->assertSame($belongsToMany, $this->associations->add('Cart', $belongsToMany));

        $this->associations->removeAll();
        $this->assertEmpty($this->associations->keys());
    }

    /**
     * Test getting associations by property.
     */
    public function testGetByProperty(): void
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['table'])
            ->getMock();
        $table->setSchema([]);
        $belongsTo = new BelongsTo('Users', [
            'sourceTable' => $table,
        ]);
        $this->assertSame('user', $belongsTo->getProperty());
        $this->associations->add('Users', $belongsTo);
        $this->assertNull($this->associations->get('user'));

        $this->assertSame($belongsTo, $this->associations->getByProperty('user'));
    }

    /**
     * Test associations with plugin names.
     */
    public function testAddHasRemoveGetWithPlugin(): void
    {
        $this->assertFalse($this->associations->has('Photos.Photos'));
        $this->assertFalse($this->associations->has('Photos'));

        $belongsTo = new BelongsTo('');
        $this->assertSame($belongsTo, $this->associations->add('Photos.Photos', $belongsTo));
        $this->assertTrue($this->associations->has('Photos'));
        $this->assertFalse($this->associations->has('Photos.Photos'));
    }

    /**
     * Test keys()
     */
    public function testKeys(): void
    {
        $belongsTo = new BelongsTo('');
        $this->associations->add('Users', $belongsTo);
        $this->associations->add('Categories', $belongsTo);
        $this->assertEquals(['Users', 'Categories'], $this->associations->keys());

        $this->associations->remove('Categories');
        $this->assertEquals(['Users'], $this->associations->keys());
    }

    /**
     *  Data provider for AssociationCollection::getByType
     */
    public function associationCollectionType(): array
    {
        return [
            ['BelongsTo', 'BelongsToMany'],
            ['belongsTo', 'belongsToMany'],
            ['belongsto', 'belongstomany'],
        ];
    }

    /**
     * Test getting association names by getByType.
     *
     * @param string $belongsToStr
     * @param string $belongsToManyStr
     * @dataProvider associationCollectionType
     */
    public function testGetByType($belongsToStr, $belongsToManyStr): void
    {
        $belongsTo = new BelongsTo('');
        $this->associations->add('Users', $belongsTo);

        $belongsToMany = new BelongsToMany('');
        $this->associations->add('Tags', $belongsToMany);

        $this->assertSame([$belongsTo], $this->associations->getByType($belongsToStr));
        $this->assertSame([$belongsToMany], $this->associations->getByType($belongsToManyStr));
        $this->assertSame([$belongsTo, $belongsToMany], $this->associations->getByType([$belongsToStr, $belongsToManyStr]));
    }

    /**
     * Type should return empty array.
     */
    public function hasTypeReturnsEmptyArray(): void
    {
        foreach (['HasMany', 'hasMany', 'FooBar', 'DoesNotExist'] as $value) {
            $this->assertSame([], $this->associations->getByType($value));
        }
    }

    /**
     * test cascading deletes.
     */
    public function testCascadeDelete(): void
    {
        $mockOne = $this->getMockBuilder('Cake\ORM\Association\BelongsTo')
            ->setConstructorArgs([''])
            ->getMock();
        $mockTwo = $this->getMockBuilder('Cake\ORM\Association\HasMany')
            ->setConstructorArgs([''])
            ->getMock();

        $entity = new Entity();
        $options = ['option' => 'value'];
        $this->associations->add('One', $mockOne);
        $this->associations->add('Two', $mockTwo);

        $mockOne->expects($this->once())
            ->method('cascadeDelete')
            ->with($entity, $options)
            ->willReturn(true);

        $mockTwo->expects($this->once())
            ->method('cascadeDelete')
            ->with($entity, $options)
            ->willReturn(true);

        $result = $this->associations->cascadeDelete($entity, $options);
        $this->assertTrue($result);
    }

    /**
     * Test saving parent associations
     */
    public function testSaveParents(): void
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['table'])
            ->getMock();
        $table->setSchema([]);
        $mockOne = $this->getMockBuilder('Cake\ORM\Association\BelongsTo')
            ->onlyMethods(['saveAssociated'])
            ->setConstructorArgs(['Parent', [
                'sourceTable' => $table,
            ]])
            ->getMock();
        $mockTwo = $this->getMockBuilder('Cake\ORM\Association\HasMany')
            ->onlyMethods(['saveAssociated'])
            ->setConstructorArgs(['Child', [
                'sourceTable' => $table,
            ]])
            ->getMock();

        $this->associations->add('Parent', $mockOne);
        $this->associations->add('Child', $mockTwo);

        $entity = new Entity();
        $entity->set('parent', ['key' => 'value']);
        $entity->set('child', ['key' => 'value']);

        $options = ['option' => 'value'];

        $mockOne->expects($this->once())
            ->method('saveAssociated')
            ->with($entity, $options)
            ->will($this->returnValue(true));

        $mockTwo->expects($this->never())
            ->method('saveAssociated');

        $result = $this->associations->saveParents(
            $table,
            $entity,
            ['Parent', 'Child'],
            $options
        );
        $this->assertTrue($result, 'Save should work.');
    }

    /**
     * Test saving filtered parent associations.
     */
    public function testSaveParentsFiltered(): void
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['table'])
            ->getMock();
        $table->setSchema([]);
        $mockOne = $this->getMockBuilder('Cake\ORM\Association\BelongsTo')
            ->onlyMethods(['saveAssociated'])
            ->setConstructorArgs(['Parents', [
                'sourceTable' => $table,
            ]])
            ->getMock();
        $mockTwo = $this->getMockBuilder('Cake\ORM\Association\BelongsTo')
            ->onlyMethods(['saveAssociated'])
            ->setConstructorArgs(['Categories', [
                'sourceTable' => $table,
            ]])
            ->getMock();

        $this->associations->add('Parents', $mockOne);
        $this->associations->add('Categories', $mockTwo);

        $entity = new Entity();
        $entity->set('parent', ['key' => 'value']);
        $entity->set('category', ['key' => 'value']);

        $options = ['atomic' => true];

        $mockOne->expects($this->once())
            ->method('saveAssociated')
            ->with($entity, ['atomic' => true, 'associated' => ['Others']])
            ->will($this->returnValue(true));

        $mockTwo->expects($this->never())
            ->method('saveAssociated');

        $result = $this->associations->saveParents(
            $table,
            $entity,
            ['Parents' => ['associated' => ['Others']]],
            $options
        );
        $this->assertTrue($result, 'Save should work.');
    }

    /**
     * Test saving filtered child associations.
     */
    public function testSaveChildrenFiltered(): void
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['table'])
            ->getMock();
        $table->setSchema([]);
        $mockOne = $this->getMockBuilder('Cake\ORM\Association\HasMany')
            ->onlyMethods(['saveAssociated'])
            ->setConstructorArgs(['Comments', [
                'sourceTable' => $table,
            ]])
            ->getMock();
        $mockTwo = $this->getMockBuilder('Cake\ORM\Association\HasOne')
            ->onlyMethods(['saveAssociated'])
            ->setConstructorArgs(['Profiles', [
                'sourceTable' => $table,
            ]])
            ->getMock();

        $this->associations->add('Comments', $mockOne);
        $this->associations->add('Profiles', $mockTwo);

        $entity = new Entity();
        $entity->set('comments', ['key' => 'value']);
        $entity->set('profile', ['key' => 'value']);

        $options = ['atomic' => true];

        $mockOne->expects($this->once())
            ->method('saveAssociated')
            ->with($entity, $options + ['associated' => ['Other']])
            ->will($this->returnValue(true));

        $mockTwo->expects($this->never())
            ->method('saveAssociated');

        $result = $this->associations->saveChildren(
            $table,
            $entity,
            ['Comments' => ['associated' => ['Other']]],
            $options
        );
        $this->assertTrue($result, 'Should succeed.');
    }

    /**
     * Test exceptional case.
     */
    public function testErrorOnUnknownAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot save Profiles, it is not associated to Users');
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->onlyMethods(['save'])
            ->setConstructorArgs([['alias' => 'Users']])
            ->getMock();

        $entity = new Entity();
        $entity->set('profile', ['key' => 'value']);

        $this->associations->saveChildren(
            $table,
            $entity,
            ['Profiles'],
            ['atomic' => true]
        );
    }

    /**
     * Tests the normalizeKeys method
     */
    public function testNormalizeKeys(): void
    {
        $this->assertSame([], $this->associations->normalizeKeys([]));
        $this->assertSame([], $this->associations->normalizeKeys(false));

        $assocs = ['a', 'b', 'd' => ['something']];
        $expected = ['a' => [], 'b' => [], 'd' => ['something']];
        $this->assertSame($expected, $this->associations->normalizeKeys($assocs));

        $belongsTo = new BelongsTo('');
        $this->associations->add('users', $belongsTo);
        $this->associations->add('categories', $belongsTo);
        $expected = ['users' => [], 'categories' => []];
        $this->assertSame($expected, $this->associations->normalizeKeys(true));
    }

    /**
     * Ensure that the association collection can be iterated.
     */
    public function testAssociationsCanBeIterated(): void
    {
        $belongsTo = new BelongsTo('');
        $this->associations->add('Users', $belongsTo);
        $belongsToMany = new BelongsToMany('');
        $this->associations->add('Cart', $belongsToMany);

        $expected = ['Users' => $belongsTo, 'Cart' => $belongsToMany];
        $result = iterator_to_array($this->associations, true);
        $this->assertSame($expected, $result);
    }
}
