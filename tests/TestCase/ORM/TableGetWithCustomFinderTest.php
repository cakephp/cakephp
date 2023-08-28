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
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

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
     * @dataProvider providerForTestGetWithCustomFinder
     * @param array $options
     */
    public function testGetWithCustomFinder($options): void
    {
        $table = $this->getMockBuilder(GetWithCustomFinderTable::class)
            ->onlyMethods(['selectQuery', 'findCustom'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'bar' => ['type' => 'integer'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]],
                ],
            ]])
            ->getMock();

        $query = $this->getMockBuilder(SelectQuery::class)
            ->onlyMethods(['addDefaultTypes', 'firstOrFail', 'where', 'cache', 'applyOptions'])
            ->setConstructorArgs([$table])
            ->getMock();

        $table->expects($this->once())->method('selectQuery')
            ->willReturn($query);
        $table->expects($this->any())->method('findCustom')
            ->willReturn($query);

        $entity = new Entity();
        $query->expects($this->once())->method('applyOptions')
            ->with(['fields' => ['id']]);
        $query->expects($this->once())->method('where')
            ->with([$table->getAlias() . '.bar' => 10])
            ->willReturnSelf();
        $query->expects($this->never())->method('cache');
        $query->expects($this->once())->method('firstOrFail')
            ->willReturn($entity);

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
