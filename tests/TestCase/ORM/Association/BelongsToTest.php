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
namespace Cake\Test\TestCase\ORM\Association;

use ArrayObject;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\Event\Event;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tests BelongsTo class
 */
class BelongsToTest extends TestCase
{
    /**
     * Fixtures to use.
     *
     * @var array<string>
     */
    protected $fixtures = ['core.Articles', 'core.Authors', 'core.Comments'];

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $company;

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $companiesTypeMap;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->company = $this->getTableLocator()->get('Companies', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'company_name' => ['type' => 'string'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']],
                ],
            ],
        ]);
        $this->client = $this->getTableLocator()->get('Clients', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'client_name' => ['type' => 'string'],
                'company_id' => ['type' => 'integer'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']],
                ],
            ],
        ]);
        $this->companiesTypeMap = new TypeMap([
            'Companies.id' => 'integer',
            'id' => 'integer',
            'Companies.company_name' => 'string',
            'company_name' => 'string',
            'Companies__id' => 'integer',
            'Companies__company_name' => 'string',
        ]);
    }

    /**
     * Test that foreignKey generation
     */
    public function testSetForeignKey(): void
    {
        $assoc = new BelongsTo('Companies', [
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
        ]);
        $this->assertSame('company_id', $assoc->getForeignKey());
        $this->assertSame($assoc, $assoc->setForeignKey('another_key'));
        $this->assertSame('another_key', $assoc->getForeignKey());
    }

    /**
     * Test that foreignKey generation ignores database names in target table.
     */
    public function testForeignKeyIgnoreDatabaseName(): void
    {
        $this->company->setTable('schema.companies');
        $this->client->setTable('schema.clients');
        $assoc = new BelongsTo('Companies', [
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
        ]);
        $this->assertSame('company_id', $assoc->getForeignKey());
    }

    /**
     * Tests that the association reports it can be joined
     */
    public function testCanBeJoined(): void
    {
        $assoc = new BelongsTo('Test');
        $this->assertTrue($assoc->canBeJoined());
    }

    /**
     * Tests that the alias set on associations is actually on the Entity
     */
    public function testCustomAlias(): void
    {
        $table = $this->getTableLocator()->get('Articles', [
            'className' => 'TestPlugin.Articles',
        ]);
        $table->addAssociations([
            'belongsTo' => [
                'FooAuthors' => ['className' => 'TestPlugin.Authors', 'foreignKey' => 'author_id'],
            ],
        ]);
        $article = $table->find()->contain(['FooAuthors'])->first();

        $this->assertTrue(isset($article->foo_author));
        $this->assertEquals($article->foo_author->name, 'mariano');
        $this->assertNull($article->Authors);
    }

    /**
     * Tests that the correct join and fields are attached to a query depending on
     * the association config
     */
    public function testAttachTo(): void
    {
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true],
        ];
        $association = new BelongsTo('Companies', $config);
        $query = $this->client->query();
        $association->attachTo($query);

        $expected = [
            'Companies__id' => 'Companies.id',
            'Companies__company_name' => 'Companies.company_name',
        ];
        $this->assertEquals($expected, $query->clause('select'));
        $expected = [
            'Companies' => [
                'alias' => 'Companies',
                'table' => 'companies',
                'type' => 'LEFT',
                'conditions' => new QueryExpression([
                    'Companies.is_active' => true,
                    ['Companies.id' => new IdentifierExpression('Clients.company_id')],
                ], $this->companiesTypeMap),
            ],
        ];
        $this->assertEquals($expected, $query->clause('join'));

        $this->assertSame(
            'integer',
            $query->getTypeMap()->type('Companies__id'),
            'Associations should map types.'
        );
    }

    /**
     * Tests that it is possible to avoid fields inclusion for the associated table
     */
    public function testAttachToNoFields(): void
    {
        $config = [
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true],
        ];
        $query = $this->client->query();
        $association = new BelongsTo('Companies', $config);

        $association->attachTo($query, ['includeFields' => false]);
        $this->assertEmpty($query->clause('select'), 'no fields should be added.');
    }

    /**
     * Tests that using belongsto with a table having a multi column primary
     * key will work if the foreign key is passed
     */
    public function testAttachToMultiPrimaryKey(): void
    {
        $this->company->setPrimaryKey(['id', 'tenant_id']);
        $config = [
            'foreignKey' => ['company_id', 'company_tenant_id'],
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true],
        ];
        $association = new BelongsTo('Companies', $config);
        $query = $this->client->query();
        $association->attachTo($query);

        $expected = [
            'Companies__id' => 'Companies.id',
            'Companies__company_name' => 'Companies.company_name',
        ];
        $this->assertEquals($expected, $query->clause('select'));

        $field1 = new IdentifierExpression('Clients.company_id');
        $field2 = new IdentifierExpression('Clients.company_tenant_id');
        $expected = [
            'Companies' => [
                'conditions' => new QueryExpression([
                    'Companies.is_active' => true,
                    ['Companies.id' => $field1, 'Companies.tenant_id' => $field2],
                ], $this->companiesTypeMap),
                'table' => 'companies',
                'type' => 'LEFT',
                'alias' => 'Companies',
            ],
        ];
        $this->assertEquals($expected, $query->clause('join'));
    }

    /**
     * Tests that using belongsto with a table having a multi column primary
     * key will work if the foreign key is passed
     */
    public function testAttachToMultiPrimaryKeyMismatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot match provided foreignKey for "Companies", got "(company_id)" but expected foreign key for "(id, tenant_id)"');
        $this->company->setPrimaryKey(['id', 'tenant_id']);
        $query = $this->client->query();
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true],
        ];
        $association = new BelongsTo('Companies', $config);
        $association->attachTo($query);
    }

    /**
     * Test the cascading delete of BelongsTo.
     */
    public function testCascadeDelete(): void
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->client,
            'targetTable' => $mock,
        ];
        $mock->expects($this->never())
            ->method('find');
        $mock->expects($this->never())
            ->method('delete');

        $association = new BelongsTo('Companies', $config);
        $entity = new Entity(['company_name' => 'CakePHP', 'id' => 1]);
        $this->assertTrue($association->cascadeDelete($entity));
    }

    /**
     * Test that saveAssociated() ignores non entity values.
     */
    public function testSaveAssociatedOnlyEntities(): void
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['saveAssociated'])
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->client,
            'targetTable' => $mock,
        ];
        $mock->expects($this->never())
            ->method('saveAssociated');

        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
            'author' => ['name' => 'Jose'],
        ]);

        $association = new BelongsTo('Authors', $config);
        $result = $association->saveAssociated($entity);
        $this->assertSame($result, $entity);
        $this->assertNull($entity->author_id);
    }

    /**
     * Tests that property is being set using the constructor options.
     */
    public function testPropertyOption(): void
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new BelongsTo('Thing', $config);
        $this->assertSame('thing_placeholder', $association->getProperty());
    }

    /**
     * Test that plugin names are omitted from property()
     */
    public function testPropertyNoPlugin(): void
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->client,
            'targetTable' => $mock,
        ];
        $association = new BelongsTo('Contacts.Companies', $config);
        $this->assertSame('company', $association->getProperty());
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     */
    public function testAttachToBeforeFind(): void
    {
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
        ];
        $called = false;
        $this->company->getEventManager()->on('Model.beforeFind', function ($event, $query, $options) use (&$called): void {
            $this->assertInstanceOf(Event::class, $event);
            $this->assertInstanceOf(Query::class, $query);
            $this->assertInstanceOf(ArrayObject::class, $options);
            $called = true;
        });
        $association = new BelongsTo('Companies', $config);
        $association->attachTo($this->client->query());
        $this->assertTrue($called, 'Listener should be called.');
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     */
    public function testAttachToBeforeFindExtraOptions(): void
    {
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
        ];
        $called = false;
        $this->company->getEventManager()->on('Model.beforeFind', function ($event, $query, $options) use (&$called): void {
            $this->assertSame('more', $options['something']);
            $called = true;
        });
        $association = new BelongsTo('Companies', $config);
        $query = $this->client->query();
        $association->attachTo($query, ['queryBuilder' => function ($q) {
            return $q->applyOptions(['something' => 'more']);
        }]);
        $this->assertTrue($called, 'Listener should be called.');
    }

    /**
     * Test that failing to add the foreignKey to the list of fields will
     * still attach associated data.
     */
    public function testAttachToNoFieldsSelected(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors');

        $query = $articles->find()
            ->select(['Authors.name'])
            ->where(['Articles.id' => 1])
            ->contain('Authors');
        $result = $query->firstOrFail();

        $this->assertNotEmpty($result->author);
        $this->assertSame('mariano', $result->author->name);
        $this->assertSame(['author'], array_keys($result->toArray()), 'No other properties included.');
    }

    /**
     * Test that not selecting join keys with strategy=select fails
     */
    public function testAttachToNoForeignKeySelect(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors')->setStrategy('select');

        $query = $articles->find()
            ->select(['Articles.title', 'Articles.author_id'])
            ->where(['Articles.id' => 1])
            ->contain('Authors');
        $result = $query->firstOrFail();
        $this->assertNotEmpty($result->author);
        $this->assertSame(1, $result->author->id);

        $query = $articles->find()
            ->select(['Articles.title'])
            ->where(['Articles.id' => 1])
            ->contain('Authors');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to load `Authors` association. Ensure foreign key in `Articles`');
        $query->first();
    }

    /**
     * Test that formatResults in a joined association finder doesn't dirty
     * the root entity.
     */
    public function testAttachToFormatResultsNoDirtyResults(): void
    {
        $this->setAppNamespace('TestApp');
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors')
            ->setFinder('formatted');

        $query = $articles->find()
            ->where(['Articles.id' => 1])
            ->contain('Authors');
        $result = $query->firstOrFail();

        $this->assertNotEmpty($result->author);
        $this->assertNotEmpty($result->author->formatted);
        $this->assertFalse($result->isDirty(), 'Record should be clean as it was pulled from the db.');
    }
}
