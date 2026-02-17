<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\ORM\Query\QueryFactory;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

class TableGetWithCustomFinderTest extends TestCase
{
    protected ConnectionInterface $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        static::setAppNamespace();
    }

    public static function providerForTestGetWithCustomFinder(): array
    {
        return [
            [['fields' => ['id'], 'finder' => 'custom']],
        ];
    }

    /**
     * Test that get() will call a custom finder.
     *
     * @param array $options
     */
    #[DataProvider('providerForTestGetWithCustomFinder')]
    public function testGetWithCustomFinder($options): void
    {
        $queryFactory = Mockery::mock(QueryFactory::class);
        $table = new GetWithCustomFinderTable([
            'connection' => $this->connection,
            'schema' => [
                'id' => ['type' => 'integer'],
                'bar' => ['type' => 'integer'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]],
            ],
            'queryFactory' => $queryFactory,
        ]);

        $query = Mockery::mock(new SelectQuery($table))->makePartial();
        $queryFactory->shouldReceive('select')->once()->with($table)->andReturn($query);

        $entity = new Entity();
        $query->shouldReceive('applyOptions')
            ->once()
            ->with(['fields' => ['id']])
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->once()
            ->with([$table->getAlias() . '.bar' => 10])
            ->andReturnSelf();
        $query->shouldReceive('cache')->never();
        $query->shouldReceive('firstOrFail')
            ->once()
            ->andReturn($entity);

        $result = $table->get(10, ...$options);
        $this->assertSame($entity, $result);
    }
}

// phpcs:disable
class GetWithCustomFinderTable extends Table
{
    public function findCustom($query)
    {
        return $query;
    }
}
// phpcs:enable
