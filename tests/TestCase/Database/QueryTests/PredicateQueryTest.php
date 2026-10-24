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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\QueryTests;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function Cake\Database\Expression\{between, eq, gt, in, like};

/**
 * Exercises predicates through query compilation, execution, and ORM schema types.
 */
class PredicateQueryTest extends TestCase
{
    /**
     * Records used for select and mutation queries.
     *
     * @var array<string>
     */
    protected array $fixtures = ['core.Articles'];

    /**
     * Supplies both supported identifier quoting modes.
     *
     * @return array<string, array{bool}>
     */
    public static function quotingProvider(): array
    {
        return ['unquoted' => [false], 'quoted' => [true]];
    }

    /**
     * Checks SQL-shaped values remain data with either identifier quoting mode.
     *
     * @param bool $quote Whether to quote identifiers.
     */
    #[DataProvider('quotingProvider')]
    public function testBindingAndQuoting(bool $quote): void
    {
        $connection = ConnectionManager::get('test');
        $driver = $connection->getDriver();
        $previous = $driver->isAutoQuotingEnabled();
        $driver->enableAutoQuoting($quote);
        try {
            $input = "First Article' OR 1=1 --";
            $query = $connection->selectQuery()->select('id')->from('articles')->where(['title' => eq($input)]);
            $this->assertStringNotContainsString($input, $query->sql());
            $this->assertSame([], $query->execute()->fetchAll('assoc'));
            $this->assertSame([], (clone $query)->execute()->fetchAll('assoc'));

            $query = $connection->selectQuery()->select('id')->from('articles')
                ->where(['id' => between(1, 2, 'integer')])->orderByAsc('id');
            $this->assertSame([1, 2], array_map('intval', array_column($query->execute()->fetchAll('assoc'), 'id')));
        } finally {
            $driver->enableAutoQuoting($previous);
        }
    }

    /**
     * Checks a subquery can be reused in a membership predicate.
     */
    public function testMembershipSubquery(): void
    {
        $connection = ConnectionManager::get('test');
        $subquery = $connection->selectQuery()->select('id')->from('articles')->where(['id' => gt(1)]);
        $predicate = in($subquery);
        $query = $connection->selectQuery()->select('id')->from('articles')->where(['id' => $predicate])->orderByAsc('id');
        $this->assertSame([2, 3], array_map('intval', array_column($query->execute()->fetchAll('assoc'), 'id')));
        $this->assertSame($query->sql(), (clone $query)->sql());
    }

    /**
     * Checks query filters work in aggregate, having, join, and CASE contexts.
     */
    public function testConditionConsumers(): void
    {
        $connection = ConnectionManager::get('test');
        $query = $connection->selectQuery();
        $case = new QueryExpression()->case()->when(['a.id' => gt(1)])->then(1)->else(0);
        $query->select(['id' => 'a.id', 'selected' => $case])->from(['a' => 'articles'])
            ->innerJoin(['b' => 'articles'], ['b.id' => eq($query->identifier('a.id'))])
            ->where(['a.title' => like('%Article')])->orderByAsc('a.id');
        $this->assertCount(3, $query->execute()->fetchAll('assoc'));

        $query = $connection->selectQuery();
        $query->select(['author_id', 'total' => $query->func()->count('*')])->from('articles')
            ->groupBy('author_id')->having(['COUNT(*)' => gt(1, 'integer')]);
        $this->assertCount(1, $query->execute()->fetchAll('assoc'));

        $filter = $connection->selectQuery()->func()->count('*')->filter(['id' => gt(1)]);
        $binder = new ValueBinder();
        $this->assertStringContainsString('FILTER (WHERE id > :c0)', $filter->sql($binder));
    }

    /**
     * Checks ORM field types and predicate-based update and delete filters.
     */
    public function testOrmTypesAndMutationFilters(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $query = $articles->find()->where(['Articles.id' => between(1, 2)]);
        $this->assertCount(2, $query->all());
        $bindings = $query->getValueBinder()->bindings();
        $this->assertSame(['integer', 'integer'], array_column($bindings, 'type'));

        $connection = ConnectionManager::get('test');
        $updated = $connection->updateQuery()->update('articles')->set(['title' => 'Updated'])
            ->where(['id' => eq(1, 'integer')])->execute();
        $this->assertSame(1, $updated->rowCount());
        $deleted = $connection->deleteQuery()->delete('articles')->where(['id' => gt(2, 'integer')])->execute();
        $this->assertSame(1, $deleted->rowCount());
    }
}
