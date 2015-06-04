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
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests BelongsTo class
 *
 */
class BelongsToTest extends TestCase
{

    /**
     * Fixtures to use.
     *
     * @var array
     */
    public $fixtures = ['core.articles', 'core.comments', 'core.authors'];

    /**
     * Don't autoload fixtures as most tests uses mocks.
     *
     * @var bool
     */
    public $autoFixture = false;

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->company = TableRegistry::get('Companies', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'company_name' => ['type' => 'string'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ]
        ]);
        $this->client = TableRegistry::get('Clients', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'client_name' => ['type' => 'string'],
                'company_id' => ['type' => 'integer'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ]
        ]);
        $this->companiesTypeMap = new TypeMap([
            'Companies.id' => 'integer',
            'id' => 'integer',
            'Companies.company_name' => 'string',
            'company_name' => 'string',
        ]);
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
     * Test that foreignKey generation ignores database names in target table.
     *
     * @return void
     */
    public function testForeignKey()
    {
        $this->company->table('schema.companies');
        $this->client->table('schema.clients');
        $assoc = new BelongsTo('Companies', [
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
        ]);
        $this->assertEquals('company_id', $assoc->foreignKey());
    }

    /**
     * Tests that the association reports it can be joined
     *
     * @return void
     */
    public function testCanBeJoined()
    {
        $assoc = new BelongsTo('Test');
        $this->assertTrue($assoc->canBeJoined());
    }

    /**
     * Tests that the alias set on associations is actually on the Entity
     *
     * @return void
     */
    public function testCustomAlias()
    {
        $table = TableRegistry::get('Articles', [
            'className' => 'TestPlugin.Articles'
        ]);
        $table->addAssociations([
            'belongsTo' => [
                'FooAuthors' => ['className' => 'TestPlugin.Authors', 'foreignKey' => 'author_id']
            ]
        ]);
        $article = $table->find()->contain(['FooAuthors'])->first();

        $this->assertTrue(isset($article->foo_author));
        $this->assertEquals($article->foo_author->name, 'mariano');
        $this->assertNull($article->Authors);
    }

    /**
     * Tests that the correct join and fields are attached to a query depending on
     * the association config
     *
     * @return void
     */
    public function testAttachTo()
    {
        $query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true]
        ];
        $association = new BelongsTo('Companies', $config);
        $field = new IdentifierExpression('Clients.company_id');
        $query->expects($this->once())->method('join')->with([
            'Companies' => [
                'conditions' => new QueryExpression([
                    'Companies.is_active' => true,
                    ['Companies.id' => $field]
                ], $this->companiesTypeMap),
                'table' => 'companies',
                'type' => 'LEFT'
            ]
        ]);
        $query->expects($this->once())->method('select')->with([
            'Companies__id' => 'Companies.id',
            'Companies__company_name' => 'Companies.company_name'
        ]);
        $association->attachTo($query);
    }

    /**
     * Tests that it is possible to avoid fields inclusion for the associated table
     *
     * @return void
     */
    public function testAttachToNoFields()
    {
        $query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
        $config = [
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true]
        ];
        $association = new BelongsTo('Companies', $config);
        $field = new IdentifierExpression('Clients.company_id');
        $query->expects($this->once())->method('join')->with([
            'Companies' => [
                'conditions' => new QueryExpression([
                    'Companies.is_active' => true,
                    ['Companies.id' => $field]
                ], $this->companiesTypeMap),
                'type' => 'LEFT',
                'table' => 'companies',
            ]
        ]);
        $query->expects($this->never())->method('select');
        $association->attachTo($query, ['includeFields' => false]);
    }

    /**
     * Test the cascading delete of BelongsTo.
     *
     * @return void
     */
    public function testCascadeDelete()
    {
        $mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
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
     *
     * @return void
     */
    public function testSaveAssociatedOnlyEntities()
    {
        $mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
        $config = [
            'sourceTable' => $this->client,
            'targetTable' => $mock,
        ];
        $mock->expects($this->never())
            ->method('saveAssociated');

        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
            'author' => ['name' => 'Jose']
        ]);

        $association = new BelongsTo('Authors', $config);
        $result = $association->saveAssociated($entity);
        $this->assertSame($result, $entity);
        $this->assertNull($entity->author_id);
    }

    /**
     * Tests that using belongsto with a table having a multi column primary
     * key will work if the foreign key is passed
     *
     * @return void
     */
    public function testAttachToMultiPrimaryKey()
    {
        $this->company->primaryKey(['id', 'tenant_id']);
        $query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
        $config = [
            'foreignKey' => ['company_id', 'company_tenant_id'],
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true]
        ];
        $association = new BelongsTo('Companies', $config);
        $field1 = new IdentifierExpression('Clients.company_id');
        $field2 = new IdentifierExpression('Clients.company_tenant_id');
        $query->expects($this->once())->method('join')->with([
            'Companies' => [
                'conditions' => new QueryExpression([
                    'Companies.is_active' => true,
                    ['Companies.id' => $field1, 'Companies.tenant_id' => $field2]
                ], $this->companiesTypeMap),
                'table' => 'companies',
                'type' => 'LEFT'
            ]
        ]);
        $query->expects($this->once())->method('select')->with([
            'Companies__id' => 'Companies.id',
            'Companies__company_name' => 'Companies.company_name'
        ]);
        $association->attachTo($query);
    }

    /**
     * Tests that using belongsto with a table having a multi column primary
     * key will work if the foreign key is passed
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot match provided foreignKey for "Companies", got "(company_id)" but expected foreign key for "(id, tenant_id)"
     * @return void
     */
    public function testAttachToMultiPrimaryKeyMistmatch()
    {
        $this->company->primaryKey(['id', 'tenant_id']);
        $query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company,
            'conditions' => ['Companies.is_active' => true]
        ];
        $association = new BelongsTo('Companies', $config);
        $association->attachTo($query);
    }

    /**
     * Tests that property is being set using the constructor options.
     *
     * @return void
     */
    public function testPropertyOption()
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new BelongsTo('Thing', $config);
        $this->assertEquals('thing_placeholder', $association->property());
    }

    /**
     * Test that plugin names are omitted from property()
     *
     * @return void
     */
    public function testPropertyNoPlugin()
    {
        $mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
        $config = [
            'sourceTable' => $this->client,
            'targetTable' => $mock,
        ];
        $association = new BelongsTo('Contacts.Companies', $config);
        $this->assertEquals('company', $association->property());
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     *
     * @return void
     */
    public function testAttachToBeforeFind()
    {
        $query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company
        ];
        $listener = $this->getMock('stdClass', ['__invoke']);
        $this->company->eventManager()->attach($listener, 'Model.beforeFind');
        $association = new BelongsTo('Companies', $config);
        $listener->expects($this->once())->method('__invoke')
            ->with(
                $this->isInstanceOf('\Cake\Event\Event'),
                $this->isInstanceOf('\Cake\ORM\Query'),
                $this->isInstanceOf('\ArrayObject'),
                false
            );
        $association->attachTo($query);
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     *
     * @return void
     */
    public function testAttachToBeforeFindExtraOptions()
    {
        $query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
        $config = [
            'foreignKey' => 'company_id',
            'sourceTable' => $this->client,
            'targetTable' => $this->company
        ];
        $listener = $this->getMock('stdClass', ['__invoke']);
        $this->company->eventManager()->attach($listener, 'Model.beforeFind');
        $association = new BelongsTo('Companies', $config);
        $options = new \ArrayObject(['something' => 'more']);
        $listener->expects($this->once())->method('__invoke')
            ->with(
                $this->isInstanceOf('\Cake\Event\Event'),
                $this->isInstanceOf('\Cake\ORM\Query'),
                $options,
                false
            );
        $association->attachTo($query, ['queryBuilder' => function ($q) {
            return $q->applyOptions(['something' => 'more']);
        }]);
    }
}
