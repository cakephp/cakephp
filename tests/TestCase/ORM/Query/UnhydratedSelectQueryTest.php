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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Query;

use Cake\Core\Exception\CakeException;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query\QueryFactory;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Query\UnhydratedSelectQuery;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Tests the type-safe non-hydrated query path: Table::unhydratedFind() and
 * the UnhydratedSelectQuery class it returns.
 */
class UnhydratedSelectQueryTest extends TestCase
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
     * unhydratedFind() is the type-safe entry point for non-hydrated reads.
     * It returns an UnhydratedSelectQuery (not a plain SelectQuery), so consumers know
     * up-front that results will be arrays.
     */
    public function testUnhydratedFindReturnsUnhydratedSelectQuery(): void
    {
        $query = $this->articles->unhydratedFind();

        $this->assertInstanceOf(UnhydratedSelectQuery::class, $query);
        $this->assertInstanceOf(SelectQuery::class, $query);
        $this->assertFalse($query->isHydrationEnabled());
    }

    /**
     * first() on an UnhydratedSelectQuery resolves to an array (or null when empty),
     * matching the runtime hydration setting locked in by the constructor.
     */
    public function testFirstReturnsArrayOrNull(): void
    {
        $row = $this->articles->unhydratedFind()->where(['id' => 1])->first();

        $this->assertIsArray($row);
        $this->assertSame(1, $row['id']);

        $missing = $this->articles->unhydratedFind()->where(['id' => 99999])->first();
        $this->assertNull($missing);
    }

    /**
     * firstOrFail() returns an array on success and throws the same
     * RecordNotFoundException as the entity path on miss.
     */
    public function testFirstOrFailReturnsArrayOrThrows(): void
    {
        $row = $this->articles->unhydratedFind()->where(['id' => 1])->firstOrFail();
        $this->assertIsArray($row);
        $this->assertSame(1, $row['id']);

        $this->expectException(RecordNotFoundException::class);
        $this->articles->unhydratedFind()->where(['id' => 99999])->firstOrFail();
    }

    /**
     * all() and iteration both produce array rows — confirms the locked
     * `_hydrate=false` flag flows through the result-set decoration.
     */
    public function testAllAndIterationProduceArrays(): void
    {
        $resultSet = $this->articles->unhydratedFind()->orderBy(['id' => 'ASC'])->all();
        $rows = $resultSet->toArray();

        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertIsArray($row);
            $this->assertArrayHasKey('id', $row);
        }
    }

    /**
     * UnhydratedSelectQuery is fully substitutable for SelectQuery: the ORM
     * may re-enable hydration on it (the eager loader does exactly this when
     * normalizing association queries). It must not fight that — contain()
     * with a hydrated parent has to keep working.
     */
    public function testInteroperatesWithContainEagerLoading(): void
    {
        $this->articles->belongsTo('Authors');

        $rows = $this->articles
            ->unhydratedFind()
            ->contain('Authors')
            ->where(['Articles.id' => 1])
            ->toArray();

        $this->assertNotEmpty($rows);
        $this->assertIsArray($rows[0]);
        $this->assertArrayHasKey('author', $rows[0]);
        $this->assertIsArray($rows[0]['author']);
    }

    /**
     * Re-enabling hydration is allowed (it just flips the flag, like any
     * SelectQuery) — the value of this class is the static type, not a
     * runtime lock.
     */
    public function testEnableHydrationIsNotLocked(): void
    {
        $query = $this->articles->unhydratedFind();
        $this->assertFalse($query->isHydrationEnabled());

        $query->enableHydration(true);
        $this->assertTrue($query->isHydrationEnabled());
    }

    /**
     * Custom finders called via unhydratedFind() receive the UnhydratedSelectQuery itself,
     * so finder-applied builder methods (where/orderBy/contain/...) flow
     * through without losing the array shape.
     */
    public function testFinderReceivesUnhydratedSelectQuery(): void
    {
        $query = $this->articles->unhydratedFind('all')->where(['id >' => 0]);

        $this->assertInstanceOf(UnhydratedSelectQuery::class, $query);
        $rows = $query->orderBy(['id' => 'ASC'])->limit(2)->toArray();

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertIsArray($row);
        }
    }

    /**
     * unhydratedFind() must build through the injected QueryFactory (like
     * find() does), not by instantiating UnhydratedSelectQuery directly —
     * otherwise apps with a custom QueryFactory get divergent behavior
     * between find() and unhydratedFind().
     */
    public function testHonorsInjectedQueryFactory(): void
    {
        $factory = new class extends QueryFactory {
            public function unhydratedSelect(Table $table): UnhydratedSelectQuery
            {
                return new class ($table) extends UnhydratedSelectQuery {
                };
            }
        };
        $table = $this->getTableLocator()->get('ArticlesCustomFactory', [
            'className' => Table::class,
            'table' => 'articles',
            'queryFactory' => $factory,
        ]);

        $query = $table->unhydratedFind();

        $this->assertInstanceOf(UnhydratedSelectQuery::class, $query);
        $this->assertNotSame(
            UnhydratedSelectQuery::class,
            $query::class,
            'unhydratedFind() bypassed the injected QueryFactory.',
        );
    }

    /**
     * A finder that discards the passed query and returns a freshly built
     * one cannot preserve the non-hydrating contract. unhydratedFind() must
     * fail loudly with a clear message naming the finder, not return a
     * silently hydrated query or hit a cryptic TypeError.
     */
    public function testFinderReturningFreshQueryThrows(): void
    {
        $table = new class (['alias' => 'Articles', 'table' => 'articles', 'connection' => ConnectionManager::get('test')]) extends Table {
            /**
             * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array> $query The passed query.
             * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array>
             */
            public function findFresh(SelectQuery $query): SelectQuery
            {
                return $this->find();
            }
        };

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('`fresh` finder must return the query it was given');
        $table->unhydratedFind('fresh');
    }
}
