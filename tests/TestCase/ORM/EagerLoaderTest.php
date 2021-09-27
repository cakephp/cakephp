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

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\EagerLoader;
use Cake\ORM\Query;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Tests EagerLoader
 */
class EagerLoaderTest extends TestCase
{
    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Cake\ORM\Table
     */
    protected $table;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $clientsTypeMap;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $ordersTypeMap;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $orderTypesTypeMap;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $stuffTypeMap;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $stuffTypesTypeMap;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $companiesTypeMap;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $categoriesTypeMap;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $schema = [
            'id' => ['type' => 'integer'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];
        $schema1 = [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'phone' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];
        $schema2 = [
            'id' => ['type' => 'integer'],
            'total' => ['type' => 'string'],
            'placed' => ['type' => 'datetime'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];

        $this->table = $table = $this->getTableLocator()->get('foo', ['schema' => $schema]);
        $clients = $this->getTableLocator()->get('clients', ['schema' => $schema1]);
        $orders = $this->getTableLocator()->get('orders', ['schema' => $schema2]);
        $companies = $this->getTableLocator()->get('companies', ['schema' => $schema, 'table' => 'organizations']);
        $orderTypes = $this->getTableLocator()->get('orderTypes', ['schema' => $schema]);
        $stuff = $this->getTableLocator()->get('stuff', ['schema' => $schema, 'table' => 'things']);
        $stuffTypes = $this->getTableLocator()->get('stuffTypes', ['schema' => $schema]);
        $categories = $this->getTableLocator()->get('categories', ['schema' => $schema]);

        $table->belongsTo('clients');
        $clients->hasOne('orders');
        $clients->belongsTo('companies');
        $orders->belongsTo('orderTypes');
        $orders->hasOne('stuff');
        $stuff->belongsTo('stuffTypes');
        $companies->belongsTo('categories');

        $this->clientsTypeMap = new TypeMap([
            'clients.id' => 'integer',
            'id' => 'integer',
            'clients.name' => 'string',
            'name' => 'string',
            'clients.phone' => 'string',
            'phone' => 'string',
            'clients__id' => 'integer',
            'clients__name' => 'string',
            'clients__phone' => 'string',
        ]);
        $this->ordersTypeMap = new TypeMap([
            'orders.id' => 'integer',
            'id' => 'integer',
            'orders.total' => 'string',
            'total' => 'string',
            'orders.placed' => 'datetime',
            'placed' => 'datetime',
            'orders__id' => 'integer',
            'orders__total' => 'string',
            'orders__placed' => 'datetime',
        ]);
        $this->orderTypesTypeMap = new TypeMap([
            'orderTypes.id' => 'integer',
            'id' => 'integer',
            'orderTypes__id' => 'integer',
        ]);
        $this->stuffTypeMap = new TypeMap([
            'stuff.id' => 'integer',
            'id' => 'integer',
            'stuff__id' => 'integer',
        ]);
        $this->stuffTypesTypeMap = new TypeMap([
            'stuffTypes.id' => 'integer',
            'id' => 'integer',
            'stuffTypes__id' => 'integer',
        ]);
        $this->companiesTypeMap = new TypeMap([
            'companies.id' => 'integer',
            'id' => 'integer',
            'companies__id' => 'integer',
        ]);
        $this->categoriesTypeMap = new TypeMap([
            'categories.id' => 'integer',
            'id' => 'integer',
            'categories__id' => 'integer',
        ]);
    }

    /**
     * Tests that fully defined belongsTo and hasOne relationships are joined correctly
     */
    public function testContainToJoinsOneLevel(): void
    {
        $contains = [
            'clients' => [
                'orders' => [
                    'orderTypes',
                    'stuff' => ['stuffTypes'],
                ],
                'companies' => [
                    'foreignKey' => 'organization_id',
                    'categories',
                ],
            ],
        ];

        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['join'])
            ->setConstructorArgs([$this->connection, $this->table])
            ->getMock();

        $query->setTypeMap($this->clientsTypeMap);

        $query->expects($this->exactly(7))
            ->method('join')
            ->withConsecutive(
                [
                    ['clients' => [
                        'table' => 'clients',
                        'type' => 'LEFT',
                        'conditions' => new QueryExpression([
                            ['clients.id' => new IdentifierExpression('foo.client_id')],
                        ], new TypeMap($this->clientsTypeMap->getDefaults())),
                    ]],
                ],
                [
                    ['orders' => [
                        'table' => 'orders',
                        'type' => 'LEFT',
                        'conditions' => new QueryExpression([
                            ['clients.id' => new IdentifierExpression('orders.client_id')],
                        ], $this->ordersTypeMap),
                    ]],
                ],
                [
                    ['orderTypes' => [
                        'table' => 'order_types',
                        'type' => 'LEFT',
                        'conditions' => new QueryExpression([
                            ['orderTypes.id' => new IdentifierExpression('orders.order_type_id')],
                        ], $this->orderTypesTypeMap),
                    ]],
                ],
                [
                    ['stuff' => [
                        'table' => 'things',
                        'type' => 'LEFT',
                        'conditions' => new QueryExpression([
                            ['orders.id' => new IdentifierExpression('stuff.order_id')],
                        ], $this->stuffTypeMap),
                    ]],
                ],
                [
                    ['stuffTypes' => [
                        'table' => 'stuff_types',
                        'type' => 'LEFT',
                        'conditions' => new QueryExpression([
                            ['stuffTypes.id' => new IdentifierExpression('stuff.stuff_type_id')],
                        ], $this->stuffTypesTypeMap),
                    ]],
                ],
                [
                    ['companies' => [
                        'table' => 'organizations',
                        'type' => 'LEFT',
                        'conditions' => new QueryExpression([
                            ['companies.id' => new IdentifierExpression('clients.organization_id')],
                        ], $this->companiesTypeMap),
                    ]],
                ],
                [
                    ['categories' => [
                        'table' => 'categories',
                        'type' => 'LEFT',
                        'conditions' => new QueryExpression([
                            ['categories.id' => new IdentifierExpression('companies.category_id')],
                        ], $this->categoriesTypeMap),
                    ]],
                ]
            )
            ->will($this->returnValue($query));

        $loader = new EagerLoader();
        $loader->contain($contains);
        $query->select('foo.id')->setEagerLoader($loader)->sql();
    }

    /**
     * Tests setting containments using dot notation, additionally proves that options
     * are not overwritten when combining dot notation and array notation
     */
    public function testContainDotNotation(): void
    {
        $loader = new EagerLoader();
        $loader->contain([
            'clients.orders.stuff',
            'clients.companies.categories' => ['conditions' => ['a >' => 1]],
        ]);
        $expected = [
            'clients' => [
                'orders' => [
                    'stuff' => [],
                ],
                'companies' => [
                    'categories' => [
                        'conditions' => ['a >' => 1],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $loader->getContain());
        $loader->contain([
            'clients.orders' => ['fields' => ['a', 'b']],
            'clients' => ['sort' => ['a' => 'desc']],
        ]);

        $expected['clients']['orders'] += ['fields' => ['a', 'b']];
        $expected['clients'] += ['sort' => ['a' => 'desc']];
        $this->assertEquals($expected, $loader->getContain());
    }

    /**
     * Tests setting containments using direct key value pairs works just as with key array.
     */
    public function testContainKeyValueNotation(): void
    {
        $loader = new EagerLoader();
        $loader->contain([
            'clients',
            'companies' => 'categories',
        ]);
        $expected = [
            'clients' => [],
            'companies' => [
                'categories' => [],
            ],
        ];
        $this->assertEquals($expected, $loader->getContain());
    }

    /**
     * Tests that it is possible to pass a function as the array value for contain
     */
    public function testContainClosure(): void
    {
        $builder = function ($query): void {
        };
        $loader = new EagerLoader();
        $loader->contain([
            'clients.orders.stuff' => ['fields' => ['a']],
            'clients' => $builder,
        ]);

        $expected = [
            'clients' => [
                'orders' => [
                    'stuff' => ['fields' => ['a']],
                ],
                'queryBuilder' => $builder,
            ],
        ];
        $this->assertEquals($expected, $loader->getContain());

        $loader = new EagerLoader();
        $loader->contain([
            'clients.orders.stuff' => ['fields' => ['a']],
            'clients' => ['queryBuilder' => $builder],
        ]);
        $this->assertEquals($expected, $loader->getContain());
    }

    /**
     * Tests using the same signature as matching with contain
     */
    public function testContainSecondSignature(): void
    {
        $builder = function ($query): void {
        };
        $loader = new EagerLoader();
        $loader->contain('clients', $builder);

        $expected = [
            'clients' => [
                'queryBuilder' => $builder,
            ],
        ];
        $this->assertEquals($expected, $loader->getContain());
    }

    /**
     * Tests passing an array of associations with a query builder
     */
    public function testContainSecondSignatureInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $builder = function ($query): void {
        };
        $loader = new EagerLoader();
        $loader->contain(['clients'], $builder);

        $expected = [
            'clients' => [
                'queryBuilder' => $builder,
            ],
        ];
        $this->assertEquals($expected, $loader->getContain());
    }

    /**
     * Tests that query builders are stacked
     */
    public function testContainMergeBuilders(): void
    {
        $loader = new EagerLoader();
        $loader->contain([
            'clients' => function ($query) {
                return $query->select(['a']);
            },
        ]);
        $loader->contain([
            'clients' => function ($query) {
                return $query->select(['b']);
            },
        ]);
        $builder = $loader->getContain()['clients']['queryBuilder'];
        $table = $this->getTableLocator()->get('foo');
        $query = new Query($this->connection, $table);
        $query = $builder($query);
        $this->assertEquals(['a', 'b'], $query->clause('select'));
    }

    /**
     * Test that fields for contained models are aliased and added to the select clause
     */
    public function testContainToFieldsPredefined(): void
    {
        $contains = [
            'clients' => [
                'fields' => ['name', 'company_id', 'clients.telephone'],
                'orders' => [
                    'fields' => ['total', 'placed'],
                ],
            ],
        ];

        $table = $this->getTableLocator()->get('foo');
        $query = new Query($this->connection, $table);
        $loader = new EagerLoader();
        $loader->contain($contains);
        $query->select('foo.id');
        $loader->attachAssociations($query, $table, true);

        $select = $query->clause('select');
        $expected = [
            'foo.id', 'clients__name' => 'clients.name',
            'clients__company_id' => 'clients.company_id',
            'clients__telephone' => 'clients.telephone',
            'orders__total' => 'orders.total', 'orders__placed' => 'orders.placed',
        ];
        $this->assertEquals($expected, $select);
    }

    /**
     * Tests that default fields for associations are added to the select clause when
     * none is specified
     */
    public function testContainToFieldsDefault(): void
    {
        $contains = ['clients' => ['orders']];

        $query = new Query($this->connection, $this->table);
        $query->select()->contain($contains)->sql();
        $select = $query->clause('select');
        $expected = [
            'foo__id' => 'foo.id', 'clients__name' => 'clients.name',
            'clients__id' => 'clients.id', 'clients__phone' => 'clients.phone',
            'orders__id' => 'orders.id', 'orders__total' => 'orders.total',
            'orders__placed' => 'orders.placed',
        ];
        $expected = $this->_quoteArray($expected);
        $this->assertEquals($expected, $select);

        $contains['clients']['fields'] = ['name'];
        $query = new Query($this->connection, $this->table);
        $query->select('foo.id')->contain($contains)->sql();
        $select = $query->clause('select');
        $expected = ['foo__id' => 'foo.id', 'clients__name' => 'clients.name'];
        $expected = $this->_quoteArray($expected);
        $this->assertEquals($expected, $select);

        $contains['clients']['fields'] = [];
        $contains['clients']['orders']['fields'] = false;
        $query = new Query($this->connection, $this->table);
        $query->select()->contain($contains)->sql();
        $select = $query->clause('select');
        $expected = [
            'foo__id' => 'foo.id',
            'clients__id' => 'clients.id',
            'clients__name' => 'clients.name',
            'clients__phone' => 'clients.phone',
        ];
        $expected = $this->_quoteArray($expected);
        $this->assertEquals($expected, $select);
    }

    /**
     * Tests that the path for getting to a deep association is materialized in an
     * array key
     */
    public function testNormalizedPath(): void
    {
        $contains = [
            'clients' => [
                'orders' => [
                    'orderTypes',
                    'stuff' => ['stuffTypes'],
                ],
                'companies' => [
                    'categories',
                ],
            ],
        ];

        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['join'])
            ->setConstructorArgs([$this->connection, $this->table])
            ->getMock();

        $loader = new EagerLoader();
        $loader->contain($contains);
        $normalized = $loader->normalized($this->table);
        $this->assertSame('clients', $normalized['clients']->aliasPath());
        $this->assertSame('client', $normalized['clients']->propertyPath());

        $assocs = $normalized['clients']->associations();
        $this->assertSame('clients.orders', $assocs['orders']->aliasPath());
        $this->assertSame('client.order', $assocs['orders']->propertyPath());

        $assocs = $assocs['orders']->associations();
        $this->assertSame('clients.orders.orderTypes', $assocs['orderTypes']->aliasPath());
        $this->assertSame('client.order.order_type', $assocs['orderTypes']->propertyPath());
        $this->assertSame('clients.orders.stuff', $assocs['stuff']->aliasPath());
        $this->assertSame('client.order.stuff', $assocs['stuff']->propertyPath());

        $assocs = $assocs['stuff']->associations();
        $this->assertSame(
            'clients.orders.stuff.stuffTypes',
            $assocs['stuffTypes']->aliasPath()
        );
        $this->assertSame(
            'client.order.stuff.stuff_type',
            $assocs['stuffTypes']->propertyPath()
        );
    }

    /**
     * Tests that the paths for matching containments point to _matchingData.
     */
    public function testNormalizedMatchingPath(): void
    {
        $loader = new EagerLoader();
        $loader->setMatching('clients');
        $assocs = $loader->attachableAssociations($this->table);

        $this->assertSame('clients', $assocs['clients']->aliasPath());
        $this->assertSame('_matchingData.clients', $assocs['clients']->propertyPath());
    }

    /**
     * Tests that the paths for deep matching containments point to _matchingData.
     */
    public function testNormalizedDeepMatchingPath(): void
    {
        $loader = new EagerLoader();
        $loader->setMatching('clients.orders');
        $assocs = $loader->attachableAssociations($this->table);

        $this->assertSame('clients', $assocs['clients']->aliasPath());
        $this->assertSame('_matchingData.clients', $assocs['clients']->propertyPath());

        $assocs = $assocs['clients']->associations();
        $this->assertSame('clients.orders', $assocs['orders']->aliasPath());
        $this->assertSame('_matchingData.orders', $assocs['orders']->propertyPath());
    }

    /**
     * Test clearing containments but not matching joins.
     */
    public function testClearContain(): void
    {
        $contains = [
            'clients' => [
                'orders' => [
                    'orderTypes',
                    'stuff' => ['stuffTypes'],
                ],
                'companies' => [
                    'categories',
                ],
            ],
        ];

        $loader = new EagerLoader();
        $loader->contain($contains);
        $loader->setMatching('clients.addresses');

        $loader->clearContain();
        $result = $loader->normalized($this->table);
        $this->assertEquals([], $result);
        $this->assertArrayHasKey('clients', $loader->getMatching());
    }

    /**
     * Test for enableAutoFields()
     */
    public function testEnableAutoFields(): void
    {
        $loader = new EagerLoader();
        $this->assertTrue($loader->isAutoFieldsEnabled());
        $this->assertSame($loader, $loader->disableAutoFields());
        $this->assertFalse($loader->isAutoFieldsEnabled());
    }

    /**
     * Helper function sued to quoted both keys and values in an array in case
     * the test suite is running with auto quoting enabled
     *
     * @param array $elements
     * @return array
     */
    protected function _quoteArray($elements): array
    {
        if ($this->connection->getDriver()->isAutoQuotingEnabled()) {
            $quoter = function ($e) {
                return $this->connection->getDriver()->quoteIdentifier($e);
            };

            return array_combine(
                array_map($quoter, array_keys($elements)),
                array_map($quoter, array_values($elements))
            );
        }

        return $elements;
    }

    /**
     * Asserts that matching('something') and setMatching('something') return consistent type.
     */
    public function testSetMatchingReturnType(): void
    {
        $loader = new EagerLoader();
        $result = $loader->setMatching('clients');
        $this->assertInstanceOf(EagerLoader::class, $result);
        $this->assertArrayHasKey('clients', $loader->getMatching());
    }
}
