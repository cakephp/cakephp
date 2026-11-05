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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Expression\AggregateExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Query\SelectQuery;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests queries using table and column aliases longer than the database identifier limit.
 *
 * The tests enable identifier shortening. On databases without a limit (Sqlite)
 * the queries run unchanged.
 */
class LongIdentifiersTest extends TestCase
{
    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
        'core.Comments',
    ];

    protected Connection $connection;

    protected bool $shortening;

    /**
     * A table alias over the Postgres limit of 63 bytes.
     */
    protected string $articles = 'Creator_Contacts_Organisations_BillingAddresses_Countries_Articles';

    /**
     * A table alias that has the same first 63 bytes as `$articles`.
     */
    protected string $authors = 'Creator_Contacts_Organisations_BillingAddresses_Countries_ArticlesAuthors';

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->shortening = $this->connection->getDriver()->isIdentifierShorteningEnabled();
        $this->connection->getDriver()->enableIdentifierShortening();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->connection->getDriver()->enableAutoQuoting(false);
        $this->connection->getDriver()->enableIdentifierShortening($this->shortening);
        unset($this->connection);
    }

    public static function autoQuotingProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * Column aliases that only differ after the identifier limit must not collide,
     * and must be fetched under their full name with the right types.
     */
    #[DataProvider('autoQuotingProvider')]
    public function testLongTableAndColumnAliases(bool $autoQuoting): void
    {
        $this->connection->getDriver()->enableAutoQuoting($autoQuoting);
        $articles = $this->articles;
        $authors = $this->authors;

        $query = (new SelectQuery($this->connection))
            ->select([
                "{$articles}__id" => "{$articles}.id",
                "{$articles}__title" => "{$articles}.title",
                "{$authors}__name" => "{$authors}.name",
            ])
            ->from([$articles => 'articles'])
            ->innerJoin(
                [$authors => 'authors'],
                ["{$authors}.id" => new IdentifierExpression("{$articles}.author_id")],
            )
            ->where(["{$articles}.published" => 'Y', "{$authors}.name" => 'mariano'])
            ->orderBy(["{$articles}.id" => 'ASC'])
            ->setSelectTypeMap(new TypeMap(["{$articles}__id" => 'integer']));

        $this->assertLongIdentifiersAreShortened($query);

        $rows = $query->execute()->fetchAll('assoc');
        $this->assertSame(
            [
                ["{$articles}__id" => 1, "{$articles}__title" => 'First Article', "{$authors}__name" => 'mariano'],
                ["{$articles}__id" => 3, "{$articles}__title" => 'Third Article', "{$authors}__name" => 'mariano'],
            ],
            $rows,
        );

        if (!$autoQuoting) {
            // Auto quoting modifies the query in place, the original aliases are then quoted.
            $this->assertArrayHasKey("{$articles}__id", $query->clause('select'), 'The query must keep the original aliases');
            $this->assertArrayHasKey($authors, $query->clause('join'), 'The query must keep the original aliases');
        }
    }

    /**
     * Group by, order by and plain SQL conditions referencing long aliases.
     */
    public function testGroupOrderAndPlainConditions(): void
    {
        $articles = $this->articles;
        $query = (new SelectQuery($this->connection))
            ->select(["{$articles}__author_id" => "{$articles}.author_id", 'total' => 'COUNT(*)'])
            ->from([$articles => 'articles'])
            ->where("{$articles}.id > 0")
            ->groupBy(["{$articles}.author_id"])
            ->orderBy(["{$articles}.author_id" => 'DESC'])
            ->setSelectTypeMap(new TypeMap([
                "{$articles}__author_id" => 'integer',
                'total' => 'integer',
            ]));

        $this->assertLongIdentifiersAreShortened($query);
        $this->assertSame(
            [
                ["{$articles}__author_id" => 3, 'total' => 1],
                ["{$articles}__author_id" => 1, 'total' => 2],
            ],
            $query->execute()->fetchAll('assoc'),
        );
    }

    /**
     * A sub-query referencing a long alias of the outer query.
     */
    public function testCorrelatedSubquery(): void
    {
        $articles = $this->articles;
        $comments = (new SelectQuery($this->connection))
            ->select(['Comments.id'])
            ->from(['Comments' => 'comments'])
            ->where([
                'Comments.article_id' => new IdentifierExpression("{$articles}.id"),
                'Comments.user_id' => 2,
            ]);
        $query = (new SelectQuery($this->connection))
            ->select(["{$articles}__id" => "{$articles}.id"])
            ->from([$articles => 'articles'])
            ->where(fn($exp) => $exp->exists($comments))
            ->orderBy(["{$articles}.id" => 'ASC'])
            ->setSelectTypeMap(new TypeMap(["{$articles}__id" => 'integer']));

        $this->assertSame(
            [["{$articles}__id" => 1], ["{$articles}__id" => 2]],
            $query->execute()->fetchAll('assoc'),
        );
    }

    /**
     * An outer query referencing long column aliases of a derived table.
     */
    public function testDerivedTable(): void
    {
        // Postgres quotes column aliases, the unquoted outer references must use lower case aliases.
        $articles = strtolower($this->articles);
        $derived = (new SelectQuery($this->connection))
            ->select(["{$articles}__id" => 'Articles.id', "{$articles}__title" => 'Articles.title'])
            ->from(['Articles' => 'articles']);
        $query = (new SelectQuery($this->connection))
            ->select(['title' => "derived.{$articles}__title"])
            ->from(['derived' => $derived])
            ->where(["derived.{$articles}__id" => 2]);

        $this->assertSame([['title' => 'Second Article']], $query->execute()->fetchAll('assoc'));
    }

    /**
     * Having clauses referencing long aliases, and string literals containing them.
     */
    public function testHavingAndStringLiterals(): void
    {
        // Postgres quotes column aliases, the unquoted references must use lower case aliases.
        $articles = strtolower($this->articles);
        $query = (new SelectQuery($this->connection))
            ->select(["{$articles}__author_id" => "{$articles}.author_id", "{$articles}__total" => 'COUNT(*)'])
            ->from([$articles => 'articles'])
            ->where("{$articles}.title <> '{$articles}.title'")
            ->groupBy(["{$articles}.author_id"])
            ->having(["COUNT({$articles}.id) >" => 1], ["COUNT({$articles}.id)" => 'integer'])
            ->andHaving("COUNT({$articles}.id) < 3")
            ->orderBy("{$articles}__total DESC")
            ->setSelectTypeMap(new TypeMap([
                "{$articles}__author_id" => 'integer',
                "{$articles}__total" => 'integer',
            ]));

        $this->assertLongIdentifiersAreShortened($query, "'{$articles}.title'");
        $this->assertSame(
            [["{$articles}__author_id" => 1, "{$articles}__total" => 2]],
            $query->execute()->fetchAll('assoc'),
        );
    }

    /**
     * Update, delete and insert queries can only reference long aliases through sub-queries.
     */
    public function testWriteQueriesWithSubqueries(): void
    {
        $articles = $this->articles;
        $second = fn() => (new SelectQuery($this->connection))
            ->select(["{$articles}__id" => "{$articles}.id"])
            ->from([$articles => 'articles'])
            ->where(["{$articles}.id" => 2]);

        $update = $this->connection->updateQuery('comments', ['published' => 'N'], ['article_id IN' => $second()]);
        $this->assertSame(2, $update->execute()->rowCount());

        $insert = $this->connection->insertQuery('comments')
            ->insert(['article_id', 'user_id', 'comment', 'published'])
            ->values(
                (new SelectQuery($this->connection))
                    ->select([
                        "{$articles}__id" => "{$articles}.id",
                        "{$articles}__author_id" => "{$articles}.author_id",
                        "{$articles}__title" => "{$articles}.title",
                        "{$articles}__published" => "{$articles}.published",
                    ])
                    ->from([$articles => 'articles'])
                    ->where(["{$articles}.id" => 2]),
            );
        $this->assertSame(1, $insert->execute()->rowCount());

        $delete = $this->connection->deleteQuery('comments', ['article_id IN' => $second()]);
        $this->assertSame(3, $delete->execute()->rowCount());
    }

    /**
     * Quotes inside comments must not change which parts of the SQL are string literals.
     */
    public function testPlainSqlComments(): void
    {
        // Postgres quotes column aliases, the unquoted references must use lower case aliases.
        $articles = strtolower($this->articles);
        $query = (new SelectQuery($this->connection))
            ->select(['id' => "{$articles}.id"])
            ->from([$articles => 'articles'])
            ->where("/* don't */ {$articles}.title <> '{$articles}.title' -- don't\n AND {$articles}.id = 1");

        $this->assertLongIdentifiersAreShortened($query, "'{$articles}.title'");
        $this->assertEquals([['id' => 1]], $query->execute()->fetchAll('assoc'));
    }

    /**
     * Postgres escape string and dollar quoted literals.
     */
    public function testPostgresStringLiterals(): void
    {
        $this->skipIf(!$this->connection->getDriver() instanceof Postgres, 'Postgres only syntax.');

        $articles = strtolower($this->articles);
        $query = (new SelectQuery($this->connection))
            ->select(['id' => "{$articles}.id"])
            ->from([$articles => 'articles'])
            ->where("{$articles}.title <> E'it\\'s' AND title <> '{$articles}.title' AND {$articles}.id = 1");

        $this->assertLongIdentifiersAreShortened($query, "'{$articles}.title'");
        $this->assertEquals([['id' => 1]], $query->execute()->fetchAll('assoc'));

        $query = (new SelectQuery($this->connection))
            ->select(['id' => "{$articles}.id"])
            ->from([$articles => 'articles'])
            ->where("{$articles}.title <> \$\$it's {$articles}.title\$\$ AND {$articles}.id = 1");

        $this->assertLongIdentifiersAreShortened($query, "\$\$it's {$articles}.title\$\$");
        $this->assertEquals([['id' => 1]], $query->execute()->fetchAll('assoc'));
    }

    /**
     * Common table expressions with long names.
     */
    public function testCommonTableExpression(): void
    {
        $this->skipIfNoCommonTableExpressions();
        $articles = $this->articles;
        $query = (new SelectQuery($this->connection))
            ->with(fn($cte) => $cte
                ->name($articles)
                ->query((new SelectQuery($this->connection))->select(['id', 'title'])->from('articles')))
            ->select(['title' => "{$articles}.title"])
            ->from($articles)
            ->where(["{$articles}.id" => 2]);

        $this->assertLongIdentifiersAreShortened($query);
        $this->assertSame([['title' => 'Second Article']], $query->execute()->fetchAll('assoc'));
    }

    /**
     * Common table expressions whose long names only differ after the identifier limit,
     * referenced from joins.
     */
    public function testCommonTableExpressionsWithTheSamePrefix(): void
    {
        $this->skipIfNoCommonTableExpressions();
        [$first, $second] = $this->commonTableExpressionsWithTheSamePrefix();
        $articles = $this->articles;

        $query = (new SelectQuery($this->connection))
            ->with($first)
            ->with($second)
            ->select(['title' => 'Second.title'])
            ->from(['Articles' => 'articles'])
            ->innerJoin(['Second' => "{$articles}_second"], ['Second.id = Articles.id']);

        $this->assertLongIdentifiersAreShortened($query);
        $this->assertSame([['title' => 'Second Article']], $query->execute()->fetchAll('assoc'));
    }

    /**
     * Common table expressions whose long names only differ after the identifier limit,
     * in update queries.
     */
    public function testCommonTableExpressionsInUpdateQuery(): void
    {
        $this->skipIfNoCommonTableExpressions();
        $driver = $this->connection->getDriver();
        $this->skipIf(
            $driver instanceof Mysql && str_contains($driver->version(), 'MariaDB'),
            'MariaDB does not support CTEs in UPDATE query.',
        );
        [$first, $second] = $this->commonTableExpressionsWithTheSamePrefix();
        $articles = $this->articles;

        $update = $this->connection->updateQuery('comments', ['published' => 'N'])
            ->with($first)
            ->with($second)
            ->where(fn($exp) => $exp->in(
                'article_id',
                (new SelectQuery($this->connection))->select(['id'])->from("{$articles}_second"),
            ));
        $this->assertSame(2, $update->execute()->rowCount());
    }

    /**
     * Skips the test when the database does not support common table expressions.
     */
    protected function skipIfNoCommonTableExpressions(): void
    {
        $this->skipIf(
            !$this->connection->getDriver()->supports(DriverFeatureEnum::CTE),
            'Common table expressions are not supported.',
        );
    }

    /**
     * Returns two common table expression builders whose long names have the same first 64 bytes.
     *
     * @return array<\Closure>
     */
    protected function commonTableExpressionsWithTheSamePrefix(): array
    {
        $articles = $this->articles;

        return [
            fn($cte) => $cte
                ->name("{$articles}_first")
                ->query((new SelectQuery($this->connection))->select(['id', 'title'])->from('articles')->where(['id' => 1])),
            fn($cte) => $cte
                ->name("{$articles}_second")
                ->query((new SelectQuery($this->connection))->select(['id', 'title'])->from('articles')->where(['id' => 2])),
        ];
    }

    /**
     * Named windows whose long names only differ after the identifier limit.
     */
    public function testNamedWindowsWithTheSamePrefix(): void
    {
        $driver = $this->connection->getDriver();
        $this->skipIf(
            !$driver->supports(DriverFeatureEnum::WINDOW) || $driver instanceof Sqlserver,
            'Named windows are not supported.',
        );
        $articles = $this->articles;
        $query = (new SelectQuery($this->connection))
            ->select([
                'id',
                'first' => (new AggregateExpression('ROW_NUMBER'))->over("{$articles}_first"),
                'second' => (new AggregateExpression('ROW_NUMBER'))->over("{$articles}_second"),
            ])
            ->from('articles')
            ->window("{$articles}_first", fn($window) => $window->orderBy(['id' => 'ASC']))
            ->window("{$articles}_second", fn($window) => $window->orderBy(['id' => 'DESC']))
            ->orderBy(['id' => 'ASC'])
            ->setSelectTypeMap(new TypeMap(['id' => 'integer', 'first' => 'integer', 'second' => 'integer']));

        $this->assertLongIdentifiersAreShortened($query);
        $this->assertSame(
            [
                ['id' => 1, 'first' => 1, 'second' => 3],
                ['id' => 2, 'first' => 2, 'second' => 2],
                ['id' => 3, 'first' => 3, 'second' => 1],
            ],
            $query->execute()->fetchAll('assoc'),
        );
    }

    /**
     * Asserts that the SQL sent to databases with an identifier limit has no identifier over it.
     */
    protected function assertLongIdentifiersAreShortened(SelectQuery $query, ?string $literal = null): void
    {
        $maxLength = $this->connection->getDriver()->shortener()?->getMaxLength();
        if ($maxLength === null) {
            return;
        }

        $sql = $query->sql();
        if ($literal !== null) {
            $this->assertStringContainsString($literal, $sql, 'String literals must be kept as they are');
            $sql = str_replace($literal, '', $sql);
        }
        preg_match_all('/\w+/u', $sql, $matches);
        foreach ($matches[0] as $identifier) {
            $this->assertLessThanOrEqual($maxLength, strlen($identifier), "`{$identifier}` is too long");
        }
    }
}
