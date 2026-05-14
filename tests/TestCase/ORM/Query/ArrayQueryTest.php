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
 * @since         5.next
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Query;

use BadMethodCallException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query\ArrayQuery;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Tests the type-safe non-hydrated query path: Table::findArray() and
 * the ArrayQuery class it returns.
 */
class ArrayQueryTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
    ];

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $articles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->articles = $this->getTableLocator()->get('Articles');
    }

    /**
     * findArray() is the type-safe entry point for non-hydrated reads.
     * It returns an ArrayQuery (not a plain SelectQuery), so consumers know
     * up-front that results will be arrays.
     */
    public function testFindArrayReturnsArrayQuery(): void
    {
        $query = $this->articles->findArray();

        $this->assertInstanceOf(ArrayQuery::class, $query);
        $this->assertInstanceOf(SelectQuery::class, $query);
        $this->assertFalse($query->isHydrationEnabled());
    }

    /**
     * first() on an ArrayQuery resolves to an array (or null when empty),
     * matching the runtime hydration setting locked in by the constructor.
     */
    public function testFirstReturnsArrayOrNull(): void
    {
        $row = $this->articles->findArray()->where(['id' => 1])->first();

        $this->assertIsArray($row);
        $this->assertSame(1, $row['id']);

        $missing = $this->articles->findArray()->where(['id' => 99999])->first();
        $this->assertNull($missing);
    }

    /**
     * firstOrFail() returns an array on success and throws the same
     * RecordNotFoundException as the entity path on miss.
     */
    public function testFirstOrFailReturnsArrayOrThrows(): void
    {
        $row = $this->articles->findArray()->where(['id' => 1])->firstOrFail();
        $this->assertIsArray($row);
        $this->assertSame(1, $row['id']);

        $this->expectException(RecordNotFoundException::class);
        $this->articles->findArray()->where(['id' => 99999])->firstOrFail();
    }

    /**
     * all() and iteration both produce array rows — confirms the locked
     * `_hydrate=false` flag flows through the result-set decoration.
     */
    public function testAllAndIterationProduceArrays(): void
    {
        $resultSet = $this->articles->findArray()->orderBy(['id' => 'ASC'])->all();
        $rows = $resultSet->toArray();

        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertIsArray($row);
            $this->assertArrayHasKey('id', $row);
        }
    }

    /**
     * enableHydration(true) must throw — re-enabling hydration mid-flight
     * would break the type contract that ArrayQuery's TSubject binding promises.
     */
    public function testEnableHydrationTrueThrows(): void
    {
        $query = $this->articles->findArray();

        $this->expectException(BadMethodCallException::class);
        $query->enableHydration(true);
    }

    /**
     * enableHydration(false) is a no-op (hydration is already off) and
     * preserves fluent chaining.
     */
    public function testEnableHydrationFalseIsNoop(): void
    {
        $query = $this->articles->findArray();
        $returned = $query->enableHydration(false);

        $this->assertSame($query, $returned);
        $this->assertFalse($query->isHydrationEnabled());
    }

    /**
     * Custom finders called via findArray() receive the ArrayQuery itself,
     * so finder-applied builder methods (where/orderBy/contain/...) flow
     * through without losing the array shape.
     */
    public function testFinderReceivesArrayQuery(): void
    {
        $query = $this->articles->findArray('all')->where(['id >' => 0]);

        $this->assertInstanceOf(ArrayQuery::class, $query);
        $rows = $query->orderBy(['id' => 'ASC'])->limit(2)->toArray();

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertIsArray($row);
        }
    }
}
