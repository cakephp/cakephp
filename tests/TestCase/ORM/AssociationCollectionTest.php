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

use Cake\Core\Exception\CakeException;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\AssociationCollection;
use Cake\ORM\Entity;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

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

        $tableLocator = new TableLocator();
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
        $locator = new TableLocator();
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
        $table = new Table(['alias' => 'Users']);
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
    public static function associationCollectionType(): array
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
     */
    #[DataProvider('associationCollectionType')]
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
        $belongsTo = new class ('One') extends BelongsTo {
            public function cascadeDelete(EntityInterface $entity, array $options = []): bool
            {
                return true;
            }
        };
        $hasMany = new class ('Two') extends HasMany {
            public function cascadeDelete(EntityInterface $entity, array $options = []): bool
            {
                return true;
            }
        };

        $entity = new Entity();
        $options = ['option' => 'value'];
        $this->associations->add('One', $belongsTo);
        $this->associations->add('Two', $hasMany);

        $result = $this->associations->cascadeDelete($entity, $options);
        $this->assertTrue($result);
    }

    /**
     * Test saving parent associations
     */
    public function testSaveParents(): void
    {
        $entity = new Entity();
        $entity->set('parent', ['key' => 'value']);
        $entity->set('child', ['key' => 'value']);

        $table = new Table(['alias' => 'Users']);
        $table->setSchema([]);
        $belongsTo = new class ('Parent', [
            'sourceTable' => $table,
        ]) extends BelongsTo {
            public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface
            {
                return $entity;
            }
        };
        $hasMany = new class ('Child', [
            'sourceTable' => $table,
        ]) extends HasMany {
            public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface
            {
                throw new Exception('saveAssociated should not be called');
            }
        };

        $this->associations->add('Parent', $belongsTo);
        $this->associations->add('Child', $hasMany);

        $options = ['option' => 'value'];

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
        $table = new Table(['alias' => 'Users']);
        $table->setSchema([]);
        $parent = new class ('Parent', [
            'sourceTable' => $table,
        ]) extends BelongsTo {
            public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface
            {
                return $entity;
            }
        };
        $categories = new class ('Categories', [
            'sourceTable' => $table,
        ]) extends BelongsTo {
            public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface
            {
                throw new Exception('saveAssociated should not be called');
            }
        };

        $this->associations->add('Parents', $parent);
        $this->associations->add('Categories', $categories);

        $entity = new Entity();
        $entity->set('parent', ['key' => 'value']);
        $entity->set('category', ['key' => 'value']);

        $result = $this->associations->saveParents(
            $table,
            $entity,
            ['Parents' => ['associated' => ['Others']]],
            ['atomic' => true]
        );
        $this->assertTrue($result, 'Save should work.');
    }

    /**
     * Test saving filtered child associations.
     */
    public function testSaveChildrenFiltered(): void
    {
        $table = new Table(['alias' => 'Users']);
        $table->setSchema([]);
        $comments = new class ('Comments', [
            'sourceTable' => $table,
        ]) extends HasMany {
            public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface
            {
                if ($options['atomic'] !== true) {
                    throw new Exception('options[atomic] is not correct');
                }
                if ($options['associated'] !== ['Other']) {
                    throw new Exception('options[associated] is not correct');
                }

                return $entity;
            }
        };
        $profiles = new class ('Profiles', [
            'sourceTable' => $table,
        ]) extends HasOne {
            public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface
            {
                throw new Exception('saveAssociated should not be called');
            }
        };

        $this->associations->add('Comments', $comments);
        $this->associations->add('Profiles', $profiles);

        $entity = new Entity();
        $entity->set('comments', ['key' => 'value']);
        $entity->set('profile', ['key' => 'value']);

        $result = $this->associations->saveChildren(
            $table,
            $entity,
            ['Comments' => ['associated' => ['Other']]],
            ['atomic' => true]
        );
        $this->assertTrue($result, 'Should succeed.');
    }

    /**
     * Test exceptional case.
     */
    public function testErrorOnUnknownAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot save `Profiles`, it is not associated to `Users`');
        $table = new Table(['alias' => 'Users']);

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

    public function testExceptionOnDuplicateAlias(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Association alias `Users` is already set.');

        $belongsTo = new BelongsTo('');
        $this->associations->add('Users', $belongsTo);
        $this->associations->add('Users', $belongsTo);
    }
}
