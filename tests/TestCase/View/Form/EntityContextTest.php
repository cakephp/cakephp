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
namespace Cake\Test\TestCase\View\Form;

use ArrayIterator;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Cake\View\Form\EntityContext;
use RuntimeException;
use stdClass;
use TestApp\Model\Entity\Article;
use TestApp\Model\Entity\ArticlesTag;
use TestApp\Model\Entity\Tag;

/**
 * Entity context test case.
 */
class EntityContextTest extends TestCase
{
    /**
     * Fixtures to use.
     *
     * @var array<string>
     */
    protected $fixtures = ['core.Articles', 'core.Comments', 'core.Tags', 'core.ArticlesTags'];

    /**
     * tests getRequiredMessage
     */
    public function testGetRequiredMessage(): void
    {
        $this->_setupTables();

        $context = new EntityContext([
            'entity' => new Article(),
            'table' => 'Articles',
            'validator' => 'create',
        ]);

        $this->assertNull($context->getRequiredMessage('body'));
        $this->assertSame('Don\'t forget a title!', $context->getRequiredMessage('title'));
    }

    /**
     * Test getting entity back from context.
     */
    public function testEntity(): void
    {
        $row = new Article();
        $context = new EntityContext([
            'entity' => $row,
        ]);
        $this->assertSame($row, $context->entity());
    }

    /**
     * Test getting primary key data.
     */
    public function testPrimaryKey(): void
    {
        $row = new Article();
        $context = new EntityContext([
            'entity' => $row,
        ]);
        $this->assertEquals(['id'], $context->getPrimaryKey());
    }

    /**
     * Test isPrimaryKey
     */
    public function testIsPrimaryKey(): void
    {
        $this->_setupTables();

        $row = new Article();
        $context = new EntityContext([
            'entity' => $row,
        ]);
        $this->assertTrue($context->isPrimaryKey('id'));
        $this->assertFalse($context->isPrimaryKey('title'));
        $this->assertTrue($context->isPrimaryKey('1.id'));
        $this->assertTrue($context->isPrimaryKey('Articles.1.id'));
        $this->assertTrue($context->isPrimaryKey('comments.0.id'));
        $this->assertTrue($context->isPrimaryKey('1.comments.0.id'));
        $this->assertFalse($context->isPrimaryKey('1.comments.0.comment'));
        $this->assertFalse($context->isPrimaryKey('Articles.1.comments.0.comment'));
        $this->assertTrue($context->isPrimaryKey('tags.0._joinData.article_id'));
        $this->assertTrue($context->isPrimaryKey('tags.0._joinData.tag_id'));
    }

    /**
     * Test isCreate on a single entity.
     */
    public function testIsCreateSingle(): void
    {
        $row = new Article();
        $context = new EntityContext([
            'entity' => $row,
        ]);
        $this->assertTrue($context->isCreate());

        $row->setNew(false);
        $this->assertFalse($context->isCreate());

        $row->setNew(true);
        $this->assertTrue($context->isCreate());
    }

    /**
     * Test isCreate on a collection.
     *
     * @dataProvider collectionProvider
     * @param mixed $collection
     */
    public function testIsCreateCollection($collection): void
    {
        $context = new EntityContext([
            'entity' => $collection,
        ]);
        $this->assertTrue($context->isCreate());
    }

    /**
     * Test an invalid table scope throws an error.
     */
    public function testInvalidTable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to find table class for current entity');
        $row = new stdClass();
        $context = new EntityContext([
            'entity' => $row,
        ]);
    }

    /**
     * Tests that passing a plain entity will give an error as it cannot be matched
     */
    public function testDefaultEntityError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to find table class for current entity');
        $context = new EntityContext([
            'entity' => new Entity(),
        ]);
    }

    /**
     * Tests that the table can be derived from the entity source if it is present
     */
    public function testTableFromEntitySource(): void
    {
        $entity = new Entity();
        $entity->setSource('Articles');
        $context = new EntityContext([
            'entity' => $entity,
        ]);
        $expected = ['id', 'author_id', 'title', 'body', 'published'];
        $this->assertEquals($expected, $context->fieldNames());
    }

    /**
     * Test operations with no entity.
     */
    public function testOperationsNoEntity(): void
    {
        $context = new EntityContext([
            'table' => 'Articles',
        ]);

        $this->assertNull($context->val('title'));
        $this->assertNull($context->isRequired('title'));
        $this->assertFalse($context->hasError('title'));
        $this->assertSame('string', $context->type('title'));
        $this->assertEquals([], $context->error('title'));

        $attrs = $context->attributes('title');
        $this->assertArrayHasKey('length', $attrs);
        $this->assertArrayHasKey('precision', $attrs);
    }

    /**
     * Test operations that lack a table argument.
     */
    public function testOperationsNoTableArg(): void
    {
        $row = new Article([
            'title' => 'Test entity',
            'body' => 'Something new',
        ]);
        $row->setError('title', ['Title is required.']);

        $context = new EntityContext([
            'entity' => $row,
        ]);

        $result = $context->val('title');
        $this->assertEquals($row->title, $result);

        $result = $context->error('title');
        $this->assertEquals($row->getError('title'), $result);
        $this->assertTrue($context->hasError('title'));
    }

    /**
     * Test collection operations that lack a table argument.
     *
     * @dataProvider collectionProvider
     * @param mixed $collection
     */
    public function testCollectionOperationsNoTableArg($collection): void
    {
        $context = new EntityContext([
            'entity' => $collection,
        ]);

        $result = $context->val('0.title');
        $this->assertSame('First post', $result);

        $result = $context->error('1.body');
        $this->assertEquals(['Not long enough'], $result);

        $this->assertNull($context->val('0'));
    }

    /**
     * Data provider for testing collections.
     *
     * @return array
     */
    public static function collectionProvider(): array
    {
        $one = new Article([
            'title' => 'First post',
            'body' => 'Stuff',
            'user' => new Entity(['username' => 'mark']),
        ]);
        $one->setError('title', 'Required field');

        $two = new Article([
            'title' => 'Second post',
            'body' => 'Some text',
            'user' => new Entity(['username' => 'jose']),
        ]);
        $two->setError('body', 'Not long enough');

        return [
            'array' => [[$one, $two]],
            'basic iterator' => [new ArrayObject([$one, $two])],
            'array iterator' => [new ArrayIterator([$one, $two])],
            'collection' => [new Collection([$one, $two])],
        ];
    }

    /**
     * Test operations on a collection of entities.
     *
     * @dataProvider collectionProvider
     * @param mixed $collection
     */
    public function testValOnCollections($collection): void
    {
        $context = new EntityContext([
            'entity' => $collection,
            'table' => 'Articles',
        ]);

        $result = $context->val('0.title');
        $this->assertSame('First post', $result);

        $result = $context->val('0.user.username');
        $this->assertSame('mark', $result);

        $result = $context->val('1.title');
        $this->assertSame('Second post', $result);

        $result = $context->val('1.user.username');
        $this->assertSame('jose', $result);

        $this->assertNull($context->val('nope'));
        $this->assertNull($context->val('99.title'));
    }

    /**
     * Test operations on a collection of entities when prefixing with the
     * table name
     *
     * @dataProvider collectionProvider
     * @param mixed $collection
     */
    public function testValOnCollectionsWithRootName($collection): void
    {
        $context = new EntityContext([
            'entity' => $collection,
            'table' => 'Articles',
        ]);

        $result = $context->val('Articles.0.title');
        $this->assertSame('First post', $result);

        $result = $context->val('Articles.0.user.username');
        $this->assertSame('mark', $result);

        $result = $context->val('Articles.1.title');
        $this->assertSame('Second post', $result);

        $result = $context->val('Articles.1.user.username');
        $this->assertSame('jose', $result);

        $this->assertNull($context->val('Articles.99.title'));
    }

    /**
     * Test error operations on a collection of entities.
     *
     * @dataProvider collectionProvider
     * @param mixed $collection
     */
    public function testErrorsOnCollections($collection): void
    {
        $context = new EntityContext([
            'entity' => $collection,
            'table' => 'Articles',
        ]);

        $this->assertTrue($context->hasError('0.title'));
        $this->assertEquals(['Required field'], $context->error('0.title'));
        $this->assertFalse($context->hasError('0.body'));

        $this->assertFalse($context->hasError('1.title'));
        $this->assertEquals(['Not long enough'], $context->error('1.body'));
        $this->assertTrue($context->hasError('1.body'));

        $this->assertFalse($context->hasError('nope'));
        $this->assertFalse($context->hasError('99.title'));
    }

    /**
     * Test schema operations on a collection of entities.
     *
     * @dataProvider collectionProvider
     * @param mixed $collection
     */
    public function testSchemaOnCollections($collection): void
    {
        $this->_setupTables();
        $context = new EntityContext([
            'entity' => $collection,
            'table' => 'Articles',
        ]);

        $this->assertSame('string', $context->type('0.title'));
        $this->assertSame('text', $context->type('1.body'));
        $this->assertSame('string', $context->type('0.user.username'));
        $this->assertSame('string', $context->type('1.user.username'));
        $this->assertSame('string', $context->type('99.title'));
        $this->assertNull($context->type('0.nope'));

        $expected = [
            'length' => 255, 'precision' => null,
            'null' => null, 'default' => null, 'comment' => null,
        ];
        $this->assertEquals($expected, $context->attributes('0.user.username'));
    }

    /**
     * Test validation operations on a collection of entities.
     *
     * @dataProvider collectionProvider
     * @param mixed $collection
     */
    public function testValidatorsOnCollections($collection): void
    {
        $this->_setupTables();

        $context = new EntityContext([
            'entity' => $collection,
            'table' => 'Articles',
            'validator' => [
                'Articles' => 'create',
                'Users' => 'custom',
            ],
        ]);
        $this->assertNull($context->isRequired('nope'));

        $this->assertTrue($context->isRequired('0.title'));
        $this->assertTrue($context->isRequired('0.user.username'));
        $this->assertFalse($context->isRequired('1.body'));

        $this->assertTrue($context->isRequired('99.title'));
        $this->assertNull($context->isRequired('99.nope'));
    }

    /**
     * Test reading data.
     */
    public function testValBasic(): void
    {
        $row = new Article([
            'title' => 'Test entity',
            'body' => 'Something new',
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('title');
        $this->assertEquals($row->title, $result);

        $result = $context->val('body');
        $this->assertEquals($row->body, $result);

        $result = $context->val('nope');
        $this->assertNull($result);
    }

    /**
     * Test reading invalid data.
     */
    public function testValInvalid(): void
    {
        $row = new Article([
            'title' => 'Valid title',
        ]);
        $row->setInvalidField('title', 'Invalid title');

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('title');
        $this->assertSame('Invalid title', $result);
    }

    /**
     * Test default values when entity is an array.
     */
    public function testValDefaultArray(): void
    {
        $context = new EntityContext([
            'entity' => new Article([
                'prop' => ['title' => 'foo'],
            ]),
            'table' => 'Articles',
        ]);
        $this->assertSame('foo', $context->val('prop.title', ['default' => 'bar']));
        $this->assertSame('bar', $context->val('prop.nope', ['default' => 'bar']));
    }

    /**
     * Test reading array values from an entity.
     */
    public function testValGetArrayValue(): void
    {
        $row = new Article([
            'title' => 'Test entity',
            'types' => [1, 2, 3],
            'tag' => [
                'name' => 'Test tag',
            ],
            'author' => new Entity([
                'roles' => ['admin', 'publisher'],
                'aliases' => new ArrayObject(['dave', 'david']),
            ]),
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('types');
        $this->assertEquals($row->types, $result);

        $result = $context->val('author.roles');
        $this->assertEquals($row->author->roles, $result);

        $result = $context->val('tag.name');
        $this->assertEquals($row->tag['name'], $result);

        $result = $context->val('author.aliases.0');
        $this->assertEquals($row->author->aliases[0], $result, 'ArrayAccess can be read');

        $this->assertNull($context->val('author.aliases.3'));
        $this->assertNull($context->val('tag.nope'));
        $this->assertNull($context->val('author.roles.3'));
    }

    /**
     * Test reading values from associated entities.
     */
    public function testValAssociated(): void
    {
        $row = new Article([
            'title' => 'Test entity',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
            ]),
            'comments' => [
                new Entity(['comment' => 'Test comment']),
                new Entity(['comment' => 'Second comment']),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $result = $context->val('user.fname');
        $this->assertEquals($row->user->fname, $result);

        $result = $context->val('comments.0.comment');
        $this->assertEquals($row->comments[0]->comment, $result);

        $result = $context->val('comments.1.comment');
        $this->assertEquals($row->comments[1]->comment, $result);

        $result = $context->val('comments.0.nope');
        $this->assertNull($result);

        $result = $context->val('comments.0.nope.no_way');
        $this->assertNull($result);
    }

    /**
     * Tests that trying to get values from missing associations returns null
     */
    public function testValMissingAssociation(): void
    {
        $row = new Article([
            'id' => 1,
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $result = $context->val('id');
        $this->assertEquals($row->id, $result);
        $this->assertNull($context->val('profile.id'));
    }

    /**
     * Test reading values from associated entities.
     */
    public function testValAssociatedHasMany(): void
    {
        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'articles' => [
                    new Article(['title' => 'First post']),
                    new Article(['title' => 'Second post']),
                ],
            ]),
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $result = $context->val('user.articles.0.title');
        $this->assertSame('First post', $result);

        $result = $context->val('user.articles.1.title');
        $this->assertSame('Second post', $result);
    }

    /**
     * Test reading values for magic _ids input
     */
    public function testValAssociatedDefaultIds(): void
    {
        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'sections' => [
                    new Entity(['title' => 'PHP', 'id' => 1]),
                    new Entity(['title' => 'Javascript', 'id' => 2]),
                ],
            ]),
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $result = $context->val('user.sections._ids');
        $this->assertEquals([1, 2], $result);
    }

    /**
     * Test reading values for magic _ids input
     */
    public function testValAssociatedCustomIds(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'sections' => [
                    new Entity(['title' => 'PHP', 'thing' => 1]),
                    new Entity(['title' => 'Javascript', 'thing' => 4]),
                ],
            ]),
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->getTableLocator()->get('Users')->belongsToMany('Sections');
        $this->getTableLocator()->get('Sections')->setPrimaryKey('thing');

        $result = $context->val('user.sections._ids');
        $this->assertEquals([1, 4], $result);
    }

    /**
     * Test getting default value from table schema.
     */
    public function testValSchemaDefault(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $column = $table->getSchema()->getColumn('title');
        $table->getSchema()->addColumn('title', ['default' => 'default title'] + $column);
        $row = $table->newEmptyEntity();

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('title');
        $this->assertSame('default title', $result);
    }

    /**
     * Test getting association default value from table schema.
     */
    public function testValAssociatedSchemaDefault(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $associatedTable = $table->hasMany('Comments')->getTarget();
        $column = $associatedTable->getSchema()->getColumn('comment');
        $associatedTable->getSchema()->addColumn('comment', ['default' => 'default comment'] + $column);
        $row = $table->newEmptyEntity();

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('comments.0.comment');
        $this->assertSame('default comment', $result);
    }

    /**
     * Test getting association join table default value from table schema.
     */
    public function testValAssociatedJoinTableSchemaDefault(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $joinTable = $table
            ->belongsToMany('Tags')
            ->setThrough('ArticlesTags')
            ->junction();
        $joinTable->getSchema()->addColumn('column', [
            'default' => 'default join table column value',
            'type' => 'text',
        ]);
        $row = $table->newEmptyEntity();

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('tags.0._joinData.column');
        $this->assertSame('default join table column value', $result);
    }

    /**
     * Test validator for boolean fields.
     */
    public function testIsRequiredBooleanField(): void
    {
        $this->_setupTables();

        $context = new EntityContext([
            'entity' => new Entity(),
            'table' => 'Articles',
        ]);
        $articles = $this->getTableLocator()->get('Articles');
        $articles->getSchema()->addColumn('comments_on', [
            'type' => 'boolean',
        ]);

        $validator = $articles->getValidator();
        $validator->add('comments_on', 'is_bool', [
            'rule' => 'boolean',
        ]);
        $articles->setValidator('default', $validator);

        $this->assertNull($context->isRequired('title'));
    }

    /**
     * Test validator as a string.
     */
    public function testIsRequiredStringValidator(): void
    {
        $this->_setupTables();

        $context = new EntityContext([
            'entity' => new Entity(),
            'table' => 'Articles',
            'validator' => 'create',
        ]);

        $this->assertTrue($context->isRequired('title'));
        $this->assertFalse($context->isRequired('body'));

        $this->assertNull($context->isRequired('Herp.derp.derp'));
        $this->assertNull($context->isRequired('nope'));
        $this->assertNull($context->isRequired(''));
    }

    /**
     * Test isRequired on associated entities.
     */
    public function testIsRequiredAssociatedHasMany(): void
    {
        $this->_setupTables();

        $comments = $this->getTableLocator()->get('Comments');
        $validator = $comments->getValidator();
        $validator->add('user_id', 'number', [
            'rule' => 'numeric',
        ]);

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment']),
                new Entity(['comment' => 'Second comment']),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
            'validator' => 'default',
        ]);

        $this->assertTrue($context->isRequired('comments.0.user_id'));
        $this->assertNull($context->isRequired('comments.0.other'));
        $this->assertNull($context->isRequired('user.0.other'));
        $this->assertNull($context->isRequired(''));
    }

    /**
     * Test isRequired on associated entities with boolean fields
     */
    public function testIsRequiredAssociatedHasManyBoolean(): void
    {
        $this->_setupTables();

        $comments = $this->getTableLocator()->get('Comments');
        $comments->getSchema()->addColumn('starred', 'boolean');
        $comments->getValidator()->add('starred', 'valid', ['rule' => 'boolean']);

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment']),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
            'validator' => 'default',
        ]);

        $this->assertFalse($context->isRequired('comments.0.starred'));
    }

    /**
     * Test isRequired on associated entities with custom validators.
     *
     * Ensures that missing associations use the correct entity class
     * so provider methods work correctly.
     */
    public function testIsRequiredAssociatedCustomValidator(): void
    {
        $this->_setupTables();
        $users = $this->getTableLocator()->get('Users');
        $articles = $this->getTableLocator()->get('Articles');

        $validator = $articles->getValidator();
        $validator->notEmptyString('title', 'nope', function ($context) {
            return $context['providers']['entity']->isRequired();
        });
        $articles->setValidator('default', $validator);

        $row = new Entity([
            'username' => 'mark',
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Users',
            'validator' => 'default',
        ]);

        $this->assertTrue($context->isRequired('articles.0.title'));
    }

    /**
     * Test isRequired on associated entities.
     */
    public function testIsRequiredAssociatedHasManyMissingObject(): void
    {
        $this->_setupTables();

        $comments = $this->getTableLocator()->get('Comments');
        $validator = $comments->getValidator();
        $validator->allowEmptyString('comment', null, function ($context) {
            return $context['providers']['entity']->isNew();
        });

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment'], ['markNew' => false]),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
            'validator' => 'default',
        ]);

        $this->assertTrue(
            $context->isRequired('comments.0.comment'),
            'comment is required as object is not new'
        );
        $this->assertFalse(
            $context->isRequired('comments.1.comment'),
            'comment is not required as missing object is "new"'
        );
    }

    /**
     * Test isRequired on associated entities with custom validators.
     */
    public function testIsRequiredAssociatedValidator(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment']),
                new Entity(['comment' => 'Second comment']),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
            'validator' => [
                'Articles' => 'create',
                'Comments' => 'custom',
            ],
        ]);

        $this->assertTrue($context->isRequired('title'));
        $this->assertFalse($context->isRequired('body'));
        $this->assertTrue($context->isRequired('comments.0.comment'));
        $this->assertTrue($context->isRequired('comments.1.comment'));
    }

    /**
     * Test isRequired on associated entities.
     */
    public function testIsRequiredAssociatedBelongsTo(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
            'validator' => [
                'Articles' => 'create',
                'Users' => 'custom',
            ],
        ]);

        $this->assertTrue($context->isRequired('user.username'));
        $this->assertNull($context->isRequired('user.first_name'));
    }

    /**
     * Test isRequired on associated join table entities.
     */
    public function testIsRequiredAssociatedJoinTable(): void
    {
        $this->_setupTables();

        $row = new Article([
            'tags' => [
                new Tag([
                    '_joinData' => new ArticlesTag([
                        'article_id' => 1,
                        'tag_id' => 2,
                    ]),
                ]),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertTrue($context->isRequired('tags.0._joinData.article_id'));
        $this->assertTrue($context->isRequired('tags.0._joinData.tag_id'));
    }

    /**
     * Test type() basic
     */
    public function testType(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'body' => 'Some content',
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertSame('string', $context->type('title'));
        $this->assertSame('text', $context->type('body'));
        $this->assertSame('integer', $context->type('user_id'));
        $this->assertNull($context->type('nope'));
    }

    /**
     * Test getting types for associated records.
     */
    public function testTypeAssociated(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertSame('string', $context->type('user.username'));
        $this->assertSame('text', $context->type('user.bio'));
        $this->assertNull($context->type('user.nope'));
    }

    /**
     * Test getting types for associated join data records.
     */
    public function testTypeAssociatedJoinData(): void
    {
        $this->_setupTables();

        $row = new Article([
            'tags' => [
                new Tag([
                    '_joinData' => new ArticlesTag([
                        'article_id' => 1,
                        'tag_id' => 2,
                    ]),
                ]),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertSame('integer', $context->type('tags.0._joinData.article_id'));
        $this->assertNull($context->type('tags.0._joinData.nonexistent'));

        // tests the fallback behavior
        $this->assertSame('integer', $context->type('tags.0._joinData._joinData.article_id'));
        $this->assertSame('integer', $context->type('tags.0._joinData.nonexistent.article_id'));
        $this->assertNull($context->type('tags.0._joinData._joinData.nonexistent'));
        $this->assertNull($context->type('tags.0._joinData.nonexistent'));
    }

    /**
     * Test attributes for fields.
     */
    public function testAttributes(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
            'tags' => [
                new Tag([
                    '_joinData' => new ArticlesTag([
                        'article_id' => 1,
                        'tag_id' => 2,
                    ]),
                ]),
            ],
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $expected = [
            'length' => 255, 'precision' => null,
            'null' => null, 'default' => null, 'comment' => null,
        ];
        $this->assertEquals($expected, $context->attributes('title'));

        $expected = [
            'length' => null, 'precision' => null,
            'null' => null, 'default' => null, 'comment' => null,
        ];
        $this->assertEquals($expected, $context->attributes('body'));

        $expected = [
            'length' => 10, 'precision' => 3,
            'null' => null, 'default' => null, 'comment' => null,
        ];
        $this->assertEquals($expected, $context->attributes('user.rating'));

        $expected = [
            'length' => 11, 'precision' => null,
            'null' => false, 'default' => null, 'comment' => null,
        ];
        $this->assertEquals($expected, $context->attributes('tags.0._joinData.article_id'));
    }

    /**
     * Test hasError
     */
    public function testHasError(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $row->setError('title', []);
        $row->setError('body', 'Gotta have one');
        $row->setError('user_id', ['Required field']);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertFalse($context->hasError('title'));
        $this->assertFalse($context->hasError('nope'));
        $this->assertTrue($context->hasError('body'));
        $this->assertTrue($context->hasError('user_id'));
    }

    /**
     * Test hasError on associated records
     */
    public function testHasErrorAssociated(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $row->setError('title', []);
        $row->setError('body', 'Gotta have one');
        $row->user->setError('username', ['Required']);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertTrue($context->hasError('user.username'));
        $this->assertFalse($context->hasError('user.nope'));
        $this->assertFalse($context->hasError('no.nope'));
    }

    /**
     * Test error
     */
    public function testError(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $row->setError('title', []);
        $row->setError('body', 'Gotta have one');
        $row->setError('user_id', ['Required field']);

        $row->user->setError('username', ['Required']);

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertEquals([], $context->error('title'));

        $expected = ['Gotta have one'];
        $this->assertEquals($expected, $context->error('body'));

        $expected = ['Required'];
        $this->assertEquals($expected, $context->error('user.username'));
    }

    /**
     * Test error on associated entities.
     */
    public function testErrorAssociatedHasMany(): void
    {
        $this->_setupTables();

        $comments = $this->getTableLocator()->get('Comments');
        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => '']),
                new Entity(['comment' => 'Second comment']),
            ],
        ]);
        $row->comments[0]->setError('comment', ['Is required']);
        $row->comments[0]->setError('article_id', ['Is required']);

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
            'validator' => 'default',
        ]);

        $this->assertEquals([], $context->error('title'));
        $this->assertEquals([], $context->error('comments.0.user_id'));
        $this->assertEquals([], $context->error('comments.0'));
        $this->assertEquals(['Is required'], $context->error('comments.0.comment'));
        $this->assertEquals(['Is required'], $context->error('comments.0.article_id'));
        $this->assertEquals([], $context->error('comments.1'));
        $this->assertEquals([], $context->error('comments.1.comment'));
        $this->assertEquals([], $context->error('comments.1.article_id'));
    }

    /**
     * Test error on associated join table entities.
     */
    public function testErrorAssociatedJoinTable(): void
    {
        $this->_setupTables();

        $row = new Article([
            'tags' => [
                new Tag([
                    '_joinData' => new ArticlesTag([
                        'article_id' => 1,
                    ]),
                ]),
            ],
        ]);
        $row->tags[0]->_joinData->setError('tag_id', ['Is required']);

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertEquals([], $context->error('tags.0._joinData.article_id'));
        $this->assertEquals(['Is required'], $context->error('tags.0._joinData.tag_id'));
    }

    /**
     * Test error on nested validation
     */
    public function testErrorNestedValidator(): void
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'options' => ['subpages' => ''],
        ]);
        $row->setError('options', ['subpages' => ['_empty' => 'required value']]);

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $expected = ['_empty' => 'required value'];
        $this->assertEquals($expected, $context->error('options.subpages'));
    }

    /**
     * Test error on nested validation
     */
    public function testErrorAssociatedNestedValidator(): void
    {
        $this->_setupTables();

        $tagOne = new Tag(['name' => 'first-post']);
        $tagTwo = new Tag(['name' => 'second-post']);
        $tagOne->setError(
            'metadata',
            ['description' => ['_empty' => 'required value']]
        );
        $row = new Article([
            'title' => 'My title',
            'tags' => [
                $tagOne,
                $tagTwo,
            ],
        ]);

        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $expected = ['_empty' => 'required value'];
        $this->assertSame([], $context->error('tags.0.notthere'));
        $this->assertSame([], $context->error('tags.1.notthere'));
        $this->assertEquals($expected, $context->error('tags.0.metadata.description'));
    }

    /**
     * Setup tables for tests.
     */
    protected function _setupTables(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Users');
        $articles->belongsToMany('Tags');
        $articles->hasMany('Comments');
        $articles->setEntityClass(Article::class);

        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $comments = $this->getTableLocator()->get('Comments');
        $users = $this->getTableLocator()->get('Users');
        $users->hasMany('Articles');

        $articles->setSchema([
            'id' => ['type' => 'integer', 'length' => 11, 'null' => false],
            'title' => ['type' => 'string', 'length' => 255],
            'user_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
            'body' => ['type' => 'crazy_text', 'baseType' => 'text'],
            '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
        ]);
        $articlesTags->setSchema([
            'article_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
            'tag_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
            '_constraints' => ['unique_tag' => ['type' => 'primary', 'columns' => ['article_id', 'tag_id']]],
        ]);
        $users->setSchema([
            'id' => ['type' => 'integer', 'length' => 11],
            'username' => ['type' => 'string', 'length' => 255],
            'bio' => ['type' => 'text'],
            'rating' => ['type' => 'decimal', 'length' => 10, 'precision' => 3],
        ]);

        $validator = new Validator();
        $validator->notEmptyString('title', 'Don\'t forget a title!');
        $validator->add('title', 'minlength', [
            'rule' => ['minlength', 10],
        ])
        ->add('body', 'maxlength', [
            'rule' => ['maxlength', 1000],
        ])->allowEmptyString('body');
        $articles->setValidator('create', $validator);

        $validator = new Validator();
        $validator->add('username', 'length', [
            'rule' => ['minlength', 10],
        ]);
        $users->setValidator('custom', $validator);

        $validator = new Validator();
        $validator->add('comment', 'length', [
            'rule' => ['minlength', 10],
        ]);
        $comments->setValidator('custom', $validator);

        $validator = new Validator();
        $validator->requirePresence('article_id', 'create');
        $validator->requirePresence('tag_id', 'create');
        $articlesTags->setValidator('default', $validator);
    }

    /**
     * Test the fieldnames method.
     */
    public function testFieldNames(): void
    {
        $context = new EntityContext([
            'entity' => new Entity(),
            'table' => 'Articles',
        ]);
        $articles = $this->getTableLocator()->get('Articles');
        $this->assertEquals($articles->getSchema()->columns(), $context->fieldNames());
    }

    /**
     * Test automatic entity provider setting
     */
    public function testValidatorEntityProvider(): void
    {
        $row = new Article([
            'title' => 'Test entity',
            'body' => 'Something new',
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $context->isRequired('title');
        $articles = $this->getTableLocator()->get('Articles');
        $this->assertSame($row, $articles->getValidator()->getProvider('entity'));

        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'articles' => [
                    new Article(['title' => 'First post']),
                    new Article(['title' => 'Second post']),
                ],
            ]),
        ]);
        $context = new EntityContext([
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $validator = $articles->getValidator();
        $context->isRequired('user.articles.0.title');
        $this->assertSame($row->user->articles[0], $validator->getProvider('entity'));

        $context->isRequired('user.articles.1.title');
        $this->assertSame($row->user->articles[1], $validator->getProvider('entity'));

        $context->isRequired('title');
        $this->assertSame($row, $validator->getProvider('entity'));
    }
}
