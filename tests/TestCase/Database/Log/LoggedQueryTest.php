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
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Driver\Sqlserver;
use Cake\Database\Log\LoggedQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Exception;
use RuntimeException;

/**
 * Tests LoggedQuery class
 */
class LoggedQueryTest extends TestCase
{
    protected $driver;

    protected $true = 'TRUE';

    protected $false = 'FALSE';

    protected function setUp(): void
    {
        $this->driver = ConnectionManager::get('test')->getDriver();

        if ($this->driver instanceof Sqlserver) {
            $this->true = '1';
            $this->false = '0';
        }
    }

    /**
     * Tests that LoggedQuery can be converted to string
     */
    public function testStringConversion(): void
    {
        $logged = new LoggedQuery();
        $logged->setContext(['query' => 'SELECT foo FROM bar']);
        $this->assertSame('SELECT foo FROM bar', (string)$logged);
    }

    /**
     * Tests that query placeholders are replaced when logged
     */
    public function testStringInterpolation(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'driver' => $this->driver,
            'query' => 'SELECT a FROM b where a = :p1 AND b = :p2 AND c = :p3 AND d = :p4 AND e = :p5 AND f = :p6',
            'params' => ['p1' => 'string', 'p3' => null, 'p2' => 3, 'p4' => true, 'p5' => false, 'p6' => 0],
        ]);

        $expected = "SELECT a FROM b where a = 'string' AND b = 3 AND c = NULL AND d = $this->true AND e = $this->false AND f = 0";
        $this->assertSame($expected, (string)$query);
    }

    /**
     * Tests that positional placeholders are replaced when logging a query
     */
    public function testStringInterpolationNotNamed(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'driver' => $this->driver,
            'query' => 'SELECT a FROM b where a = ? AND b = ? AND c = ? AND d = ? AND e = ? AND f = ?',
            'params' => ['string', '3', null, true, false, 0],
        ]);

        $expected = "SELECT a FROM b where a = 'string' AND b = '3' AND c = NULL AND d = $this->true AND e = $this->false AND f = 0";
        $this->assertSame($expected, (string)$query);
    }

    /**
     * Tests that repeated placeholders are correctly replaced
     */
    public function testStringInterpolationDuplicate(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1 AND b = :p1 AND c = :p2 AND d = :p2',
            'params' => ['p1' => 'string', 'p2' => 3],
        ]);

        $expected = "SELECT a FROM b where a = 'string' AND b = 'string' AND c = 3 AND d = 3";
        $this->assertSame($expected, (string)$query);
    }

    /**
     * Tests that named placeholders
     */
    public function testStringInterpolationNamed(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1 AND b = :p11 AND c = :p20 AND d = :p2',
            'params' => ['p11' => 'test', 'p1' => 'string', 'p2' => 3, 'p20' => 5],
        ]);

        $expected = "SELECT a FROM b where a = 'string' AND b = 'test' AND c = 5 AND d = 3";
        $this->assertSame($expected, (string)$query);
    }

    /**
     * Tests that placeholders are replaced with correctly escaped strings
     */
    public function testStringInterpolationSpecialChars(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1 AND b = :p2 AND c = :p3 AND d = :p4',
            'params' => ['p1' => '$2y$10$dUAIj', 'p2' => '$0.23', 'p3' => 'a\\0b\\1c\\d', 'p4' => "a'b"],
        ]);

        $expected = "SELECT a FROM b where a = '\$2y\$10\$dUAIj' AND b = '\$0.23' AND c = 'a\\\\0b\\\\1c\\\\d' AND d = 'a''b'";
        $this->assertSame($expected, (string)$query);
    }

    /**
     * Tests that query placeholders are replaced when logged
     */
    public function testBinaryInterpolation(): void
    {
        $query = new LoggedQuery();
        $uuid = str_replace('-', '', Text::uuid());
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1',
            'params' => ['p1' => hex2bin($uuid)],
        ]);

        $expected = "SELECT a FROM b where a = '{$uuid}'";
        $this->assertSame($expected, (string)$query);
    }

    /**
     * Tests that unknown possible binary data is not replaced to hex.
     */
    public function testBinaryInterpolationIgnored(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1',
            'params' => ['p1' => "a\tz"],
        ]);

        $expected = "SELECT a FROM b where a = 'a\tz'";
        $this->assertSame($expected, (string)$query);
    }

    public function testGetContext(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1',
            'numRows' => 10,
            'took' => 15,
        ]);

        $expected = [
            'query' => 'SELECT a FROM b where a = :p1',
            'numRows' => 10,
            'took' => 15.0,
            'role' => '',
        ];
        $this->assertSame($expected, $query->getContext());
    }

    public function testGetContextWithDriver(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1',
            'numRows' => 10,
            'took' => 15,
            'driver' => $this->driver,
        ]);

        $context = $query->getContext();
        $this->assertSame('SELECT a FROM b where a = :p1', $context['query']);
        $this->assertSame(10, $context['numRows']);
        $this->assertSame(15.0, $context['took']);
        $this->assertSame('test', $context['connection']);
    }

    public function testSetContext(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1',
            'lol' => 'nope',
            'connection' => $this->driver,
        ]);

        $expected = [
            'query' => 'SELECT a FROM b where a = :p1',
            'numRows' => 0,
            'took' => 0.0,
            'role' => '',
        ];
        $this->assertSame($expected, $query->getContext());
    }

    public function testGetConnectionName(): void
    {
        $query = new LoggedQuery();
        $this->assertSame('', $query->getConnectionName());

        $query->setContext([
            'driver' => $this->driver,
        ]);
        $this->assertSame('test', $query->getConnectionName());
    }

    public function testJsonSerialize(): void
    {
        $error = new Exception('You fail!');

        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = :p1',
            'params' => ['p1' => '$2y$10$dUAIj'],
            'numRows' => 4,
            'error' => $error,
        ]);

        $expected = json_encode([
            'query' => 'SELECT a FROM b where a = :p1',
            'numRows' => 4,
            'params' => ['p1' => '$2y$10$dUAIj'],
            'took' => 0,
            'error' => [
                'class' => $error::class,
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
            ],
        ]);

        $this->assertEquals($expected, json_encode($query));
    }

    /**
     * The configured redactor should be applied to the rendered SQL produced
     * by `__toString()` so secrets bound as parameters never reach log
     * engines, and to any literals that the redactor recognizes inside the
     * query string itself.
     */
    public function testRedactorAppliedToInterpolatedString(): void
    {
        LoggedQuery::setRedactor(static function (string $query, array $params): array {
            $params['secret'] = '«REDACTED»';

            return [$query, $params];
        });

        try {
            $query = new LoggedQuery();
            $query->setContext([
                'query' => 'SELECT a FROM b WHERE a = :secret AND b = :other',
                'params' => ['secret' => 'super-private', 'other' => 'visible'],
            ]);

            $this->assertSame(
                "SELECT a FROM b WHERE a = '«REDACTED»' AND b = 'visible'",
                (string)$query,
            );
        } finally {
            LoggedQuery::setRedactor(null);
        }
    }

    /**
     * The redactor should also rewrite the query string itself, so call sites
     * that inline secrets directly into the SQL fragment (rather than binding
     * them) get scrubbed too.
     */
    public function testRedactorRewritesInlinedQueryText(): void
    {
        LoggedQuery::setRedactor(static function (string $query, array $params): array {
            return [str_replace('SECRET-KEY', '«REDACTED»', $query), $params];
        });

        try {
            $query = new LoggedQuery();
            $query->setContext([
                'query' => "SELECT AES_DECRYPT(field, 'SECRET-KEY') FROM t",
            ]);

            $this->assertSame(
                "SELECT AES_DECRYPT(field, '«REDACTED»') FROM t",
                (string)$query,
            );
        } finally {
            LoggedQuery::setRedactor(null);
        }
    }

    /**
     * `getContext()` exposes the unrendered query — must apply the redactor
     * so structured loggers reading `context.query` don't leak the inlined
     * literal.
     */
    public function testRedactorAppliedToGetContext(): void
    {
        LoggedQuery::setRedactor(static function (string $query, array $params): array {
            return [str_replace('SECRET-KEY', '«REDACTED»', $query), $params];
        });

        try {
            $query = new LoggedQuery();
            $query->setContext([
                'query' => "SELECT AES_DECRYPT(field, 'SECRET-KEY') FROM t",
            ]);

            $this->assertSame(
                "SELECT AES_DECRYPT(field, '«REDACTED»') FROM t",
                $query->getContext()['query'],
            );
        } finally {
            LoggedQuery::setRedactor(null);
        }
    }

    /**
     * `jsonSerialize()` is consumed by structured loggers that round-trip
     * the LoggedQuery as JSON — both `query` and `params` must come out
     * sanitized.
     */
    public function testRedactorAppliedToJsonSerialize(): void
    {
        LoggedQuery::setRedactor(static function (string $query, array $params): array {
            $params['secret'] = '«REDACTED»';

            return [str_replace('SECRET-KEY', '«REDACTED»', $query), $params];
        });

        try {
            $query = new LoggedQuery();
            $query->setContext([
                'query' => "SELECT AES_DECRYPT(field, 'SECRET-KEY') FROM t WHERE x = :secret",
                'params' => ['secret' => 'super-private'],
            ]);

            $serialized = json_decode(json_encode($query), true);
            $this->assertSame(
                "SELECT AES_DECRYPT(field, '«REDACTED»') FROM t WHERE x = :secret",
                $serialized['query'],
            );
            $this->assertSame(['secret' => '«REDACTED»'], $serialized['params']);
        } finally {
            LoggedQuery::setRedactor(null);
        }
    }

    /**
     * A redactor that returns a malformed value must raise so the broken
     * configuration surfaces immediately — silently falling back would
     * leak the very secrets the redactor was meant to scrub.
     */
    public function testRedactorMalformedReturnThrows(): void
    {
        LoggedQuery::setRedactor(static function (string $query, array $params): mixed {
            return 'not-a-tuple';
        });

        try {
            $query = new LoggedQuery();
            $query->setContext([
                'query' => 'SELECT a FROM b WHERE a = :p1',
                'params' => ['p1' => 'visible'],
            ]);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('LoggedQuery redactor must return [string $query, array $params]; got string.');

            (string)$query;
        } finally {
            LoggedQuery::setRedactor(null);
        }
    }

    /**
     * Setting the redactor to null restores the pre-PR behaviour for
     * subsequent LoggedQuery instances.
     */
    public function testRedactorCanBeCleared(): void
    {
        LoggedQuery::setRedactor(static function (string $query, array $params): array {
            $params = array_map(static fn() => '«REDACTED»', $params);

            return [$query, $params];
        });
        LoggedQuery::setRedactor(null);

        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b WHERE a = :p1',
            'params' => ['p1' => 'visible'],
        ]);

        $this->assertSame(
            "SELECT a FROM b WHERE a = 'visible'",
            (string)$query,
        );
    }

    /**
     * Exceptions thrown by the redactor must propagate — a broken
     * redactor that silently falls back would leak secrets, so the
     * failure surfaces at the call site instead.
     */
    public function testRedactorThatThrowsPropagates(): void
    {
        LoggedQuery::setRedactor(static function (string $query, array $params): array {
            throw new RuntimeException('redactor blew up');
        });

        try {
            $query = new LoggedQuery();
            $query->setContext([
                'query' => 'SELECT a FROM b WHERE a = :p1',
                'params' => ['p1' => 'visible'],
            ]);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('redactor blew up');

            (string)$query;
        } finally {
            LoggedQuery::setRedactor(null);
        }
    }

    /**
     * `setContext()` walks `property_exists()`, which returns true for
     * static properties too. Without a guard, a `'redactor'` context key
     * would attempt `$this->redactor = $val`, creating a dynamic instance
     * property (PHP 8.2+ deprecation) instead of touching the static.
     * Verifies the guard skips static properties cleanly.
     */
    public function testSetContextSkipsStaticProperties(): void
    {
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT 1',
            'redactor' => static fn() => ['', []],
        ]);

        // The static stays null (no app-set redactor).
        $this->assertSame('SELECT 1', (string)$query);
        // No dynamic instance property was created — the call ran clean.
        $this->assertSame('SELECT 1', $query->getContext()['query']);
    }
}
