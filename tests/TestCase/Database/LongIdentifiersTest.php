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
     * Asserts that the SQL sent to databases with an identifier limit has no identifier over it.
     */
    protected function assertLongIdentifiersAreShortened(SelectQuery $query): void
    {
        $maxLength = $this->connection->getDriver()->getMaxAliasLength();
        if ($maxLength === null) {
            return;
        }

        preg_match_all('/\w+/u', $query->sql(), $matches);
        foreach ($matches[0] as $identifier) {
            $this->assertLessThanOrEqual($maxLength, strlen($identifier), "`{$identifier}` is too long");
        }
    }
}
