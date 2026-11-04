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

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\IdentifierShortener;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests IdentifierShortener class
 */
class IdentifierShortenerTest extends TestCase
{
    protected IdentifierShortener $shortener;

    protected bool $autoQuoting;

    /**
     * An alias 1 character over the limit of 63.
     */
    protected string $long = 'Creator_Contacts_Organisations_BillingAddresses_Countries_Region';

    protected function setUp(): void
    {
        parent::setUp();
        $this->shortener = new IdentifierShortener(63);

        // The assertions compare against unquoted SQL.
        $driver = ConnectionManager::get('test')->getDriver();
        $this->autoQuoting = $driver->isAutoQuotingEnabled();
        $driver->enableAutoQuoting(false);
    }

    protected function tearDown(): void
    {
        ConnectionManager::get('test')->getDriver()->enableAutoQuoting($this->autoQuoting);
        parent::tearDown();
    }

    public function testInvalidMaxLength(): void
    {
        $this->expectException(DatabaseException::class);
        new IdentifierShortener(9);
    }

    public function testShortenKeepsShortIdentifiers(): void
    {
        $this->assertSame('Articles', $this->shortener->shorten('Articles'));
        $name = str_repeat('a', 63);
        $this->assertSame($name, $this->shortener->shorten($name));
    }

    public function testShorten(): void
    {
        $this->assertSame(64, strlen($this->long));
        $short = $this->shortener->shorten($this->long);

        $this->assertSame(63, strlen($short));
        $this->assertStringStartsWith(substr($this->long, 0, 54) . '_', $short);
        $this->assertSame($short, $this->shortener->shorten($this->long), 'Shortening must be deterministic');
        $this->assertSame($short, $this->shortener->shorten($short), 'Shortening must be idempotent');
        $this->assertMatchesRegularExpression('/^\w+$/', $short);
    }

    public function testShortenTellsApartIdentifiersWithTheSamePrefix(): void
    {
        $first = $this->shortener->shorten($this->long . '__first_name');
        $second = $this->shortener->shorten($this->long . '__last_name');

        $this->assertNotSame($first, $second);
        $this->assertSame(substr($first, 0, 54), substr($second, 0, 54));
    }

    public function testShortenDoesNotSplitMultibyteCharacters(): void
    {
        $name = str_repeat('é', 40);
        $short = $this->shortener->shorten($name);

        $this->assertLessThanOrEqual(63, strlen($short));
        $this->assertTrue(mb_check_encoding($short, 'UTF-8'));
        $this->assertStringStartsWith(str_repeat('é', 27) . '_', $short);
    }

    public function testShortenIdentifier(): void
    {
        $short = $this->shortener->shorten($this->long);

        $this->assertSame('Articles.title', $this->shortener->shortenIdentifier('Articles.title'));
        $this->assertSame("{$short}.title", $this->shortener->shortenIdentifier("{$this->long}.title"));
        $this->assertSame("\"{$short}\".\"title\"", $this->shortener->shortenIdentifier("\"{$this->long}\".\"title\""));
        $this->assertSame("COUNT({$short}.id)", $this->shortener->shortenIdentifier("COUNT({$this->long}.id)"));
        $this->assertSame(
            $this->shortener->shorten($this->long . '__title'),
            $this->shortener->shortenIdentifier($this->long . '__title'),
        );
    }

    public function testNeedsShortening(): void
    {
        $connection = ConnectionManager::get('test');

        $query = (new SelectQuery($connection))->select(['id'])->from(['Articles' => 'articles']);
        $this->assertFalse($this->shortener->needsShortening($query));

        $query = (new SelectQuery($connection))->select(['id'])->from([$this->long => 'articles']);
        $this->assertTrue($this->shortener->needsShortening($query));

        $query = (new SelectQuery($connection))->select([$this->long . '__id' => 'Articles.id'])->from('articles');
        $this->assertTrue($this->shortener->needsShortening($query));

        $query = (new SelectQuery($connection))
            ->select(['id'])
            ->from('articles')
            ->join([$this->long => ['table' => 'authors', 'conditions' => []]]);
        $this->assertTrue($this->shortener->needsShortening($query));

        $derived = (new SelectQuery($connection))->select(['id'])->from([$this->long => 'articles']);
        $query = (new SelectQuery($connection))->select(['id'])->from(['derived' => $derived]);
        $this->assertTrue($this->shortener->needsShortening($query));
    }

    public function testShortenQuery(): void
    {
        $connection = ConnectionManager::get('test');
        $long = $this->long;
        $short = $this->shortener->shorten($long);
        $shortTitle = $this->shortener->shorten($long . '__title');

        $query = (new SelectQuery($connection))
            ->select([$long . '__title' => $long . '.title', 'author' => 'Authors.name'])
            ->from([$long => 'articles'])
            ->join([
                'Authors' => [
                    'table' => 'authors',
                    'conditions' => ['Authors.id' => new IdentifierExpression("{$long}.author_id")],
                ],
            ])
            ->where(["{$long}.published" => 'Y'])
            ->andWhere("{$long}.id > 0")
            ->groupBy(["{$long}.title", 'Authors.name'])
            ->orderBy([$long . '__title' => 'DESC']);

        $sql = $this->shortener->shortenQuery(clone $query)->sql();

        $this->assertArrayHasKey($long . '__title', $query->clause('select'), 'The original query must not be modified');
        $this->assertArrayHasKey($long, $query->clause('from'), 'The original query must not be modified');
        $this->assertStringNotContainsString($long, $sql);
        $this->assertStringContainsString("{$short}.title", $sql);
        $this->assertStringContainsString("{$short}.author_id", $sql);
        $this->assertStringContainsString("{$short}.published", $sql);
        $this->assertStringContainsString("{$short}.id > 0", $sql);
        $this->assertStringContainsString($shortTitle, $sql);
        $this->assertStringContainsString('Authors.name', $sql);
    }

    public function testShortenQueryCorrelatedSubquery(): void
    {
        $connection = ConnectionManager::get('test');
        $long = $this->long;
        $short = $this->shortener->shorten($long);

        $subquery = (new SelectQuery($connection))
            ->select(['Comments.id'])
            ->from(['Comments' => 'comments'])
            ->where(['Comments.article_id' => new IdentifierExpression("{$long}.id")]);
        $query = (new SelectQuery($connection))
            ->select(['id' => "{$long}.id"])
            ->from([$long => 'articles'])
            ->where(function ($exp) use ($subquery) {
                return $exp->exists($subquery);
            });

        $sql = $this->shortener->shortenQuery(clone $query)->sql();
        $this->assertStringNotContainsString($long, $sql);
        $this->assertStringContainsString("Comments.article_id = {$short}.id", $sql);
    }

    public function testAliasMap(): void
    {
        $connection = ConnectionManager::get('test');
        $query = (new SelectQuery($connection))
            ->select([
                'id',
                'title' => 'Articles.title',
                $this->long . '__name' => 'Authors.name',
            ])
            ->from('articles');

        $expected = [$this->shortener->shorten($this->long . '__name') => $this->long . '__name'];
        $this->assertSame($expected, $this->shortener->aliasMap($query));

        $decorator = $this->shortener->resultDecorator($query);
        $row = ['id' => 1, 'title' => 'First', $this->shortener->shorten($this->long . '__name') => 'mariano'];
        $this->assertSame(
            ['id' => 1, 'title' => 'First', $this->long . '__name' => 'mariano'],
            $decorator($row),
        );
        $this->assertSame([1, 'First', 'mariano'], $decorator([1, 'First', 'mariano']));
        $this->assertFalse($decorator(false));
    }

    public function testResultDecoratorNotNeeded(): void
    {
        $query = (new SelectQuery(ConnectionManager::get('test')))->select(['id', 'title'])->from('articles');
        $this->assertNull($this->shortener->resultDecorator($query));
    }

    public function testAliasMapCollision(): void
    {
        $shortener = new class (63) extends IdentifierShortener {
            protected function hash(string $name): string
            {
                return '00000000';
            }
        };
        $query = (new SelectQuery(ConnectionManager::get('test')))
            ->select([
                $this->long . '__first_name' => 'Authors.first_name',
                $this->long . '__last_name' => 'Authors.last_name',
            ])
            ->from('authors');

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('are both shortened to');
        $shortener->aliasMap($query);
    }
}
