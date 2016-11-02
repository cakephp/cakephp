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
namespace Cake\Test\TestCase\View\Form;

use ArrayIterator;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Cake\View\Form\EntityContext;

/**
 * Test stub.
 */
class Article extends Entity
{

    /**
     * Testing stub method.
     *
     * @return bool
     */
    public function isRequired()
    {
        return true;
    }
}

/**
 * Entity context test case.
 */
class EntityContextTest extends TestCase
{

    /**
     * Fixtures to use.
     *
     * @var array
     */
    public $fixtures = ['core.articles', 'core.comments'];

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->request = new Request();
    }

    /**
     * Test getting entity back from context.
     *
     * @return void
     */
    public function testEntity()
    {
        $row = new Article();
        $context = new EntityContext($this->request, [
            'entity' => $row,
        ]);
        $this->assertSame($row, $context->entity());
    }

    /**
     * Test getting primary key data.
     *
     * @return void
     */
    public function testPrimaryKey()
    {
        $row = new Article();
        $context = new EntityContext($this->request, [
            'entity' => $row,
        ]);
        $this->assertEquals(['id'], $context->primaryKey());
    }

    /**
     * Test isPrimaryKey
     *
     * @return void
     */
    public function testIsPrimaryKey()
    {
        $row = new Article();
        $context = new EntityContext($this->request, [
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
    }

    /**
     * Test isCreate on a single entity.
     *
     * @return void
     */
    public function testIsCreateSingle()
    {
        $row = new Article();
        $context = new EntityContext($this->request, [
            'entity' => $row,
        ]);
        $this->assertTrue($context->isCreate());

        $row->isNew(false);
        $this->assertFalse($context->isCreate());

        $row->isNew(true);
        $this->assertTrue($context->isCreate());
    }

    /**
     * Test isCreate on a collection.
     *
     * @dataProvider collectionProvider
     * @return void
     */
    public function testIsCreateCollection($collection)
    {
        $context = new EntityContext($this->request, [
            'entity' => $collection,
        ]);
        $this->assertTrue($context->isCreate());
    }

    /**
     * Test an invalid table scope throws an error.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to find table class for current entity
     */
    public function testInvalidTable()
    {
        $row = new \StdClass();
        $context = new EntityContext($this->request, [
            'entity' => $row,
        ]);
    }

    /**
     * Tests that passing a plain entity will give an error as it cannot be matched
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to find table class for current entity
     */
    public function testDefaultEntityError()
    {
        $context = new EntityContext($this->request, [
            'entity' => new Entity,
        ]);
    }

    /**
     * Tests that the table can be derived from the entity source if it is present
     *
     * @return void
     */
    public function testTableFromEntitySource()
    {
        $entity = new Entity;
        $entity->source('Articles');
        $context = new EntityContext($this->request, [
            'entity' => $entity,
        ]);
        $expected = ['id', 'author_id', 'title', 'body', 'published'];
        $this->assertEquals($expected, $context->fieldNames());
    }

    /**
     * Test operations with no entity.
     *
     * @return void
     */
    public function testOperationsNoEntity()
    {
        $context = new EntityContext($this->request, [
            'table' => 'Articles'
        ]);

        $this->assertNull($context->val('title'));
        $this->assertFalse($context->isRequired('title'));
        $this->assertFalse($context->hasError('title'));
        $this->assertEquals('string', $context->type('title'));
        $this->assertEquals([], $context->error('title'));

        $attrs = $context->attributes('title');
        $this->assertArrayHasKey('length', $attrs);
        $this->assertArrayHasKey('precision', $attrs);
    }

    /**
     * Test operations that lack a table argument.
     *
     * @return void
     */
    public function testOperationsNoTableArg()
    {
        $row = new Article([
            'title' => 'Test entity',
            'body' => 'Something new'
        ]);
        $row->errors('title', ['Title is required.']);

        $context = new EntityContext($this->request, [
            'entity' => $row,
        ]);

        $result = $context->val('title');
        $this->assertEquals($row->title, $result);

        $result = $context->error('title');
        $this->assertEquals($row->errors('title'), $result);
        $this->assertTrue($context->hasError('title'));
    }

    /**
     * Test collection operations that lack a table argument.
     *
     * @dataProvider collectionProvider
     * @return void
     */
    public function testCollectionOperationsNoTableArg($collection)
    {
        $context = new EntityContext($this->request, [
            'entity' => $collection,
        ]);

        $result = $context->val('0.title');
        $this->assertEquals('First post', $result);

        $result = $context->error('1.body');
        $this->assertEquals(['Not long enough'], $result);
    }

    /**
     * Data provider for testing collections.
     *
     * @return array
     */
    public static function collectionProvider()
    {
        $one = new Article([
            'title' => 'First post',
            'body' => 'Stuff',
            'user' => new Entity(['username' => 'mark'])
        ]);
        $one->errors('title', 'Required field');

        $two = new Article([
            'title' => 'Second post',
            'body' => 'Some text',
            'user' => new Entity(['username' => 'jose'])
        ]);
        $two->errors('body', 'Not long enough');

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
     * @return void
     */
    public function testValOnCollections($collection)
    {
        $context = new EntityContext($this->request, [
            'entity' => $collection,
            'table' => 'Articles',
        ]);

        $result = $context->val('0.title');
        $this->assertEquals('First post', $result);

        $result = $context->val('0.user.username');
        $this->assertEquals('mark', $result);

        $result = $context->val('1.title');
        $this->assertEquals('Second post', $result);

        $result = $context->val('1.user.username');
        $this->assertEquals('jose', $result);

        $this->assertNull($context->val('nope'));
        $this->assertNull($context->val('99.title'));
    }

    /**
     * Test operations on a collection of entities when prefixing with the
     * table name
     *
     * @dataProvider collectionProvider
     * @return void
     */
    public function testValOnCollectionsWithRootName($collection)
    {
        $context = new EntityContext($this->request, [
            'entity' => $collection,
            'table' => 'Articles',
        ]);

        $result = $context->val('Articles.0.title');
        $this->assertEquals('First post', $result);

        $result = $context->val('Articles.0.user.username');
        $this->assertEquals('mark', $result);

        $result = $context->val('Articles.1.title');
        $this->assertEquals('Second post', $result);

        $result = $context->val('Articles.1.user.username');
        $this->assertEquals('jose', $result);

        $this->assertNull($context->val('Articles.99.title'));
    }

    /**
     * Test error operations on a collection of entities.
     *
     * @dataProvider collectionProvider
     * @return void
     */
    public function testErrorsOnCollections($collection)
    {
        $context = new EntityContext($this->request, [
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
     * @return void
     */
    public function testSchemaOnCollections($collection)
    {
        $this->_setupTables();
        $context = new EntityContext($this->request, [
            'entity' => $collection,
            'table' => 'Articles',
        ]);

        $this->assertEquals('string', $context->type('0.title'));
        $this->assertEquals('text', $context->type('1.body'));
        $this->assertEquals('string', $context->type('0.user.username'));
        $this->assertEquals('string', $context->type('1.user.username'));
        $this->assertEquals('string', $context->type('99.title'));
        $this->assertNull($context->type('0.nope'));

        $expected = ['length' => 255, 'precision' => null];
        $this->assertEquals($expected, $context->attributes('0.user.username'));
    }

    /**
     * Test validation operations on a collection of entities.
     *
     * @dataProvider collectionProvider
     * @return void
     */
    public function testValidatorsOnCollections($collection)
    {
        $this->_setupTables();

        $context = new EntityContext($this->request, [
            'entity' => $collection,
            'table' => 'Articles',
            'validator' => [
                'Articles' => 'create',
                'Users' => 'custom',
            ]
        ]);
        $this->assertFalse($context->isRequired('nope'));

        $this->assertTrue($context->isRequired('0.title'));
        $this->assertTrue($context->isRequired('0.user.username'));
        $this->assertFalse($context->isRequired('1.body'));

        $this->assertTrue($context->isRequired('99.title'));
        $this->assertFalse($context->isRequired('99.nope'));
    }

    /**
     * Test reading data.
     *
     * @return void
     */
    public function testValBasic()
    {
        $row = new Article([
            'title' => 'Test entity',
            'body' => 'Something new'
        ]);
        $context = new EntityContext($this->request, [
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
     * Test reading array values from an entity.
     *
     * @return void
     */
    public function testValGetArrayValue()
    {
        $row = new Article([
            'title' => 'Test entity',
            'types' => [1, 2, 3],
            'tag' => [
                'name' => 'Test tag',
            ],
            'author' => new Entity([
                'roles' => ['admin', 'publisher']
            ])
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('types');
        $this->assertEquals($row->types, $result);

        $result = $context->val('author.roles');
        $this->assertEquals($row->author->roles, $result);

        $result = $context->val('tag.name');
        $this->assertEquals($row->tag['name'], $result);

        $result = $context->val('tag.nope');
        $this->assertNull($result);

        $result = $context->val('author.roles.3');
        $this->assertNull($result);
    }

    /**
     * Test that val() reads from the request.
     *
     * @return void
     */
    public function testValReadsRequest()
    {
        $this->request->data = [
            'title' => 'New title',
            'notInEntity' => 'yes',
        ];
        $row = new Article([
            'title' => 'Test entity',
            'body' => 'Something new'
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $this->assertEquals('New title', $context->val('title'));
        $this->assertEquals('yes', $context->val('notInEntity'));
        $this->assertEquals($row->body, $context->val('body'));
    }

    /**
     * Test reading values from associated entities.
     *
     * @return void
     */
    public function testValAssociated()
    {
        $row = new Article([
            'title' => 'Test entity',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark'
            ]),
            'comments' => [
                new Entity(['comment' => 'Test comment']),
                new Entity(['comment' => 'Second comment']),
            ]
        ]);
        $context = new EntityContext($this->request, [
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
     *
     * @return void
     */
    public function testValMissingAssociation()
    {
        $row = new Article([
            'id' => 1
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $result = $context->val('id');
        $this->assertEquals($row->id, $result);
        $this->assertNull($context->val('profile.id'));
    }

    /**
     * Test reading values from associated entities.
     *
     * @return void
     */
    public function testValAssociatedHasMany()
    {
        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'articles' => [
                    new Article(['title' => 'First post']),
                    new Article(['title' => 'Second post']),
                ]
            ]),
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $result = $context->val('user.articles.0.title');
        $this->assertEquals('First post', $result);

        $result = $context->val('user.articles.1.title');
        $this->assertEquals('Second post', $result);
    }

    /**
     * Test reading values for magic _ids input
     *
     * @return void
     */
    public function testValAssociatedDefaultIds()
    {
        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'groups' => [
                    new Entity(['title' => 'PHP', 'id' => 1]),
                    new Entity(['title' => 'Javascript', 'id' => 2]),
                ]
            ]),
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $result = $context->val('user.groups._ids');
        $this->assertEquals([1, 2], $result);
    }

    /**
     * Test reading values for magic _ids input
     *
     * @return void
     */
    public function testValAssociatedCustomIds()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'groups' => [
                    new Entity(['title' => 'PHP', 'thing' => 1]),
                    new Entity(['title' => 'Javascript', 'thing' => 4]),
                ]
            ]),
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        TableRegistry::get('Users')->belongsToMany('Groups');
        TableRegistry::get('Groups')->primaryKey('thing');

        $result = $context->val('user.groups._ids');
        $this->assertEquals([1, 4], $result);
    }

    /**
     * Test getting default value from table schema.
     *
     * @return void
     */
    public function testValSchemaDefault()
    {
        $table = TableRegistry::get('Articles');
        $column = $table->schema()->column('title');
        $table->schema()->addColumn('title', ['default' => 'default title'] + $column);
        $row = $table->newEntity();

        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $result = $context->val('title');
        $this->assertEquals('default title', $result);
    }

    /**
     * Test validator for boolean fields.
     *
     * @return void
     */
    public function testIsRequiredBooleanField()
    {
        $this->_setupTables();

        $context = new EntityContext($this->request, [
            'entity' => new Entity(),
            'table' => 'Articles',
        ]);
        $articles = TableRegistry::get('Articles');
        $articles->schema()->addColumn('comments_on', [
            'type' => 'boolean'
        ]);

        $validator = $articles->validator();
        $validator->add('comments_on', 'is_bool', [
            'rule' => 'boolean'
        ]);
        $articles->validator('default', $validator);

        $this->assertFalse($context->isRequired('title'));
    }

    /**
     * Test validator as a string.
     *
     * @return void
     */
    public function testIsRequiredStringValidator()
    {
        $this->_setupTables();

        $context = new EntityContext($this->request, [
            'entity' => new Entity(),
            'table' => 'Articles',
            'validator' => 'create',
        ]);

        $this->assertTrue($context->isRequired('title'));
        $this->assertFalse($context->isRequired('body'));

        $this->assertFalse($context->isRequired('Herp.derp.derp'));
        $this->assertFalse($context->isRequired('nope'));
        $this->assertFalse($context->isRequired(''));
    }

    /**
     * Test isRequired on associated entities.
     *
     * @return void
     */
    public function testIsRequiredAssociatedHasMany()
    {
        $this->_setupTables();

        $comments = TableRegistry::get('Comments');
        $validator = $comments->validator();
        $validator->add('user_id', 'number', [
            'rule' => 'numeric',
        ]);

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment']),
                new Entity(['comment' => 'Second comment']),
            ]
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
            'validator' => 'default',
        ]);

        $this->assertTrue($context->isRequired('comments.0.user_id'));
        $this->assertFalse($context->isRequired('comments.0.other'));
        $this->assertFalse($context->isRequired('user.0.other'));
        $this->assertFalse($context->isRequired(''));
    }

    /**
     * Test isRequired on associated entities with boolean fields
     *
     * @return void
     */
    public function testIsRequiredAssociatedHasManyBoolean()
    {
        $this->_setupTables();

        $comments = TableRegistry::get('Comments');
        $comments->schema()->addColumn('starred', 'boolean');
        $comments->validator()->add('starred', 'valid', ['rule' => 'boolean']);

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment']),
            ]
        ]);
        $context = new EntityContext($this->request, [
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
     *
     * @return void
     */
    public function testIsRequiredAssociatedCustomValidator()
    {
        $this->_setupTables();
        $users = TableRegistry::get('Users');
        $articles = TableRegistry::get('Articles');

        $validator = $articles->validator();
        $validator->notEmpty('title', 'nope', function ($context) {
            return $context['providers']['entity']->isRequired();
        });
        $articles->validator('default', $validator);

        $row = new Entity([
            'username' => 'mark'
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Users',
            'validator' => 'default',
        ]);

        $this->assertTrue($context->isRequired('articles.0.title'));
    }

    /**
     * Test isRequired on associated entities.
     *
     * @return void
     */
    public function testIsRequiredAssociatedHasManyMissingObject()
    {
        $this->_setupTables();

        $comments = TableRegistry::get('Comments');
        $validator = $comments->validator();
        $validator->allowEmpty('comment', function ($context) {
            return $context['providers']['entity']->isNew();
        });

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment'], ['markNew' => false]),
            ]
        ]);
        $context = new EntityContext($this->request, [
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
     *
     * @return void
     */
    public function testIsRequiredAssociatedValidator()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => 'First comment']),
                new Entity(['comment' => 'Second comment']),
            ]
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
            'validator' => [
                'Articles' => 'create',
                'Comments' => 'custom'
            ]
        ]);

        $this->assertTrue($context->isRequired('title'));
        $this->assertFalse($context->isRequired('body'));
        $this->assertTrue($context->isRequired('comments.0.comment'));
        $this->assertTrue($context->isRequired('comments.1.comment'));
    }

    /**
     * Test isRequired on associated entities.
     *
     * @return void
     */
    public function testIsRequiredAssociatedBelongsTo()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
            'validator' => [
                'Articles' => 'create',
                'Users' => 'custom'
            ]
        ]);

        $this->assertTrue($context->isRequired('user.username'));
        $this->assertFalse($context->isRequired('user.first_name'));
    }

    /**
     * Test type() basic
     *
     * @return void
     */
    public function testType()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'body' => 'Some content',
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertEquals('string', $context->type('title'));
        $this->assertEquals('text', $context->type('body'));
        $this->assertEquals('integer', $context->type('user_id'));
        $this->assertNull($context->type('nope'));
    }

    /**
     * Test getting types for associated records.
     *
     * @return void
     */
    public function testTypeAssociated()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertEquals('string', $context->type('user.username'));
        $this->assertEquals('text', $context->type('user.bio'));
        $this->assertNull($context->type('user.nope'));
    }

    /**
     * Test attributes for fields.
     *
     * @return void
     */
    public function testAttributes()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $expected = [
            'length' => 255, 'precision' => null
        ];
        $this->assertEquals($expected, $context->attributes('title'));

        $expected = [
            'length' => null, 'precision' => null
        ];
        $this->assertEquals($expected, $context->attributes('body'));

        $expected = [
            'length' => 10, 'precision' => 3
        ];
        $this->assertEquals($expected, $context->attributes('user.rating'));
    }

    /**
     * Test hasError
     *
     * @return void
     */
    public function testHasError()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $row->errors('title', []);
        $row->errors('body', 'Gotta have one');
        $row->errors('user_id', ['Required field']);
        $context = new EntityContext($this->request, [
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
     *
     * @return void
     */
    public function testHasErrorAssociated()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $row->errors('title', []);
        $row->errors('body', 'Gotta have one');
        $row->user->errors('username', ['Required']);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $this->assertTrue($context->hasError('user.username'));
        $this->assertFalse($context->hasError('user.nope'));
        $this->assertFalse($context->hasError('no.nope'));
    }

    /**
     * Test error
     *
     * @return void
     */
    public function testError()
    {
        $this->_setupTables();

        $row = new Article([
            'title' => 'My title',
            'user' => new Entity(['username' => 'Mark']),
        ]);
        $row->errors('title', []);
        $row->errors('body', 'Gotta have one');
        $row->errors('user_id', ['Required field']);

        $row->user->errors('username', ['Required']);

        $context = new EntityContext($this->request, [
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
     *
     * @return void
     */
    public function testErrorAssociatedHasMany()
    {
        $this->_setupTables();

        $comments = TableRegistry::get('Comments');
        $row = new Article([
            'title' => 'My title',
            'comments' => [
                new Entity(['comment' => '']),
                new Entity(['comment' => 'Second comment']),
            ]
        ]);
        $row->comments[0]->errors('comment', ['Is required']);
        $row->comments[0]->errors('article_id', ['Is required']);

        $context = new EntityContext($this->request, [
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
     * Setup tables for tests.
     *
     * @return void
     */
    protected function _setupTables()
    {
        $articles = TableRegistry::get('Articles');
        $articles->belongsTo('Users');
        $articles->hasMany('Comments');
        $articles->entityClass(__NAMESPACE__ . '\Article');

        $comments = TableRegistry::get('Comments');
        $users = TableRegistry::get('Users');
        $users->hasMany('Articles');

        $articles->schema([
            'id' => ['type' => 'integer', 'length' => 11, 'null' => false],
            'title' => ['type' => 'string', 'length' => 255],
            'user_id' => ['type' => 'integer', 'length' => 11, 'null' => false],
            'body' => ['type' => 'crazy_text', 'baseType' => 'text']
        ]);
        $users->schema([
            'id' => ['type' => 'integer', 'length' => 11],
            'username' => ['type' => 'string', 'length' => 255],
            'bio' => ['type' => 'text'],
            'rating' => ['type' => 'decimal', 'length' => 10, 'precision' => 3],
        ]);

        $validator = new Validator();
        $validator->add('title', 'minlength', [
            'rule' => ['minlength', 10]
        ])
        ->add('body', 'maxlength', [
            'rule' => ['maxlength', 1000]
        ])->allowEmpty('body');
        $articles->validator('create', $validator);

        $validator = new Validator();
        $validator->add('username', 'length', [
            'rule' => ['minlength', 10]
        ]);
        $users->validator('custom', $validator);

        $validator = new Validator();
        $validator->add('comment', 'length', [
            'rule' => ['minlength', 10]
        ]);
        $comments->validator('custom', $validator);
    }

    /**
     * Test the fieldnames method.
     *
     * @return void
     */
    public function testFieldNames()
    {
        $context = new EntityContext($this->request, [
            'entity' => new Entity(),
            'table' => 'Articles',
        ]);
        $articles = TableRegistry::get('Articles');
        $this->assertEquals($articles->schema()->columns(), $context->fieldNames());
    }

    /**
     * Test automatic entity provider setting
     *
     * @return void
     */
    public function testValidatorEntityProvider()
    {
        $row = new Article([
            'title' => 'Test entity',
            'body' => 'Something new'
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);
        $context->isRequired('title');
        $articles = TableRegistry::get('Articles');
        $this->assertSame($row, $articles->validator()->provider('entity'));

        $row = new Article([
            'title' => 'First post',
            'user' => new Entity([
                'username' => 'mark',
                'fname' => 'Mark',
                'articles' => [
                    new Article(['title' => 'First post']),
                    new Article(['title' => 'Second post']),
                ]
            ]),
        ]);
        $context = new EntityContext($this->request, [
            'entity' => $row,
            'table' => 'Articles',
        ]);

        $validator = $articles->validator();
        $context->isRequired('user.articles.0.title');
        $this->assertSame($row->user->articles[0], $validator->provider('entity'));

        $context->isRequired('user.articles.1.title');
        $this->assertSame($row->user->articles[1], $validator->provider('entity'));

        $context->isRequired('title');
        $this->assertSame($row, $validator->provider('entity'));
    }
}
