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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Query;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Query\DeleteQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Test\TestCase\Database\QueryAssertsTrait;
use Cake\TestSuite\TestCase;

/**
 * Tests DeleteQuery class
 */
class DeleteQueryTest extends TestCase
{
    use QueryAssertsTrait;

    /**
     * @var int
     */
    public const AUTHOR_COUNT = 4;

    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
    ];

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $autoQuote;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->autoQuote = $this->connection->getDriver()->isAutoQuotingEnabled();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->connection->getDriver()->enableAutoQuoting($this->autoQuote);
        unset($this->connection);
    }

    /**
     * Test a basic delete using from()
     */
    public function testDeleteWithFrom(): void
    {
        $query = new DeleteQuery($this->connection);

        $query->delete()
            ->from('authors')
            ->where('1 = 1');

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
        $this->assertSame(self::AUTHOR_COUNT, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Test delete with from and alias.
     */
    public function testDeleteWithAliasedFrom(): void
    {
        $query = new DeleteQuery($this->connection);

        $query->delete()
            ->from(['a ' => 'authors'])
            ->where(['a.id !=' => 99]);

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors> WHERE <id> != :c0', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
        $this->assertSame(self::AUTHOR_COUNT, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Test a basic delete with no from() call.
     */
    public function testDeleteNoFrom(): void
    {
        $query = new DeleteQuery($this->connection);

        $query->delete('authors')
            ->where('1 = 1');

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
        $this->assertSame(self::AUTHOR_COUNT, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Tests that delete queries that contain joins do trigger a notice,
     * warning about possible incompatibilities with aliases being removed
     * from the conditions.
     */
    public function testDeleteRemovingAliasesCanBreakJoins(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.');
        $query = new DeleteQuery($this->connection);

        $query
            ->delete('authors')
            ->from(['a ' => 'authors'])
            ->innerJoin('articles')
            ->where(['a.id' => 1]);

        $query->sql();
    }

    /**
     * Tests that aliases are stripped from delete query conditions
     * where possible.
     */
    public function testDeleteStripAliasesFromConditions(): void
    {
        $query = new DeleteQuery($this->connection);

        $query
            ->delete()
            ->from(['a' => 'authors'])
            ->where([
                'OR' => [
                    'a.id' => 1,
                    'a.name IS' => null,
                    'a.email IS NOT' => null,
                    'AND' => [
                        'b.name NOT IN' => ['foo', 'bar'],
                        'OR' => [
                            $query->newExpr()->eq(new IdentifierExpression('c.name'), 'zap'),
                            'd.name' => 'baz',
                            (new SelectQuery($this->connection))->select(['e.name'])->where(['e.name' => 'oof']),
                        ],
                    ],
                ],
            ]);

        $this->assertQuotedQuery(
            'DELETE FROM <authors> WHERE \(' .
                '<id> = :c0 OR \(<name>\) IS NULL OR \(<email>\) IS NOT NULL OR \(' .
                    '<name> NOT IN \(:c1,:c2\) AND \(' .
                        '<name> = :c3 OR <name> = :c4 OR \(SELECT <e>\.<name> WHERE <e>\.<name> = :c5\)' .
                    '\)' .
                '\)' .
            '\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test that epilog() will actually append a string to a delete query
     */
    public function testAppendDelete(): void
    {
        $query = new DeleteQuery($this->connection);
        $sql = $query
            ->delete('articles')
            ->where(['id' => 1])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertStringContainsString('DELETE FROM', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    /**
     * Test use of modifiers in a DELETE query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testDeleteModifiers(): void
    {
        $query = new DeleteQuery($this->connection);
        $result = $query->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier('IGNORE');
        $this->assertQuotedQuery(
            'DELETE IGNORE FROM <authors> WHERE 1 = 1',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new DeleteQuery($this->connection);
        $result = $query->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier(['IGNORE', 'QUICK']);
        $this->assertQuotedQuery(
            'DELETE IGNORE QUICK FROM <authors> WHERE 1 = 1',
            $result->sql(),
            !$this->autoQuote
        );
    }
}
