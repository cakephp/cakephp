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
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

use ArrayIterator;
use Cake\Core\Exception\CakeException;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Query\SelectQuery as DatabaseSelectQuery;
use Cake\Datasource\Paging\Exception\InvalidCursorException;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;

/**
 * Seek / keyset pagination strategy.
 *
 * In contrast to {@see \Cake\Datasource\Paging\NumericPaginator}, this paginator
 * advances through results via opaque cursor tokens anchored to a stable
 * deterministic ordering, avoiding the `OFFSET` scaling and consistency issues
 * of page-number based pagination.
 *
 * ## Required configuration
 *
 * - Query must have an explicit `orderBy()` that is deterministic (typically
 *   ending in a unique tie-breaker column).
 * - Only simple column ordering is supported as a cursor source.
 *
 * ## Request parameters
 *
 * Recognized keys from request params / settings:
 *
 * - `cursor` — signed cursor token (or structured array) produced by a prior page.
 * - `direction` — `'after'` (default, next page) or `'before'` (previous page).
 * - `limit` — page size.
 *
 * ## Example
 *
 * ```
 * $articles = $this->paginate($query, [
 *     'className' => \Cake\Datasource\Paging\SeekPaginator::class,
 * ]);
 * ```
 */
class SeekPaginator extends NumericPaginator
{
    /**
     * {@inheritDoc}
     *
     * Seek pagination does not expose `sort`/`direction` (which control offset
     * ordering): a seek paginator's ordering must be deterministic and stable
     * across requests, so changing it via request params would invalidate
     * outstanding cursor tokens. The `cursor` token is the sole advancement
     * mechanism. Direction (`after`/`before`) is taken from raw request params
     * directly to bypass NumericPaginator's sort-direction stripping.
     */
    protected array $_defaultConfig = [
        'page' => 1,
        'limit' => 20,
        'maxLimit' => 100,
        'allowedParameters' => ['limit', 'cursor'],
        'sortableFields' => null,
        'finder' => 'all',
        'scope' => null,
        'cursor' => null,
        'direction' => null,
    ];

    /**
     * Resolved cursor for the requested page, or `null` if this is the first page.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $requestedCursor = null;

    /**
     * Requested seek direction: `'after'` (forward) or `'before'` (backward).
     *
     * @var string
     */
    protected string $requestedDirection = 'after';

    /**
     * Ordered list of cursor column names derived from the query ordering.
     *
     * @var array<int, string>
     */
    protected array $cursorColumns = [];

    /**
     * @inheritDoc
     */
    public function paginate(
        mixed $target,
        array $params = [],
        array $settings = [],
    ): PaginatedInterface {
        $query = null;
        if ($target instanceof QueryInterface) {
            $query = $target;
            $target = $query->getRepository();
            if ($target === null) {
                throw new CakeException('No repository set for query.');
            }
        }

        assert(
            $target instanceof RepositoryInterface,
            'Pagination target must be an instance of `' . QueryInterface::class
                . '` or `' . RepositoryInterface::class . '`.',
        );

        $this->captureSeekParams($target->getAlias(), $params, $settings);

        $data = $this->extractData($target, $params, $settings);

        $query = $this->getQuery($target, $query, $data);
        if (!$query instanceof DatabaseSelectQuery) {
            throw new InvalidCursorException(
                'Seek pagination is only supported for `Cake\\Database\\Query\\SelectQuery` instances.',
            );
        }
        $orderPairs = $this->extractOrderPairs($query);
        $this->cursorColumns = array_map(fn(array $p) => $p[0], $orderPairs);
        $this->applyCursor($query);

        // For a backward page we need the N records immediately preceding the
        // cursor in the query's logical ordering. We flip the ORDER BY so LIMIT
        // picks the right end of the predicate, then reverse the rows in PHP so
        // the caller sees them in the original ordering.
        if ($this->requestedDirection === 'before') {
            $this->flipOrdering($query, $orderPairs);
        }

        $limit = (int)$data['options']['limit'];
        $items = $query->limit($limit + 1)->all();

        $hasMore = count($items) > $limit;
        if ($hasMore) {
            $items = $items->take($limit);
        }

        $rows = iterator_to_array($items, false);
        if ($this->requestedDirection === 'before') {
            $rows = array_reverse($rows);
        }

        $this->pagingParams['count'] = count($rows);
        $this->pagingParams['totalCount'] = null;

        $pagingParams = $this->buildParams($data);
        $pagingParams = $this->addCursorParams($pagingParams, $rows, $hasMore);

        return $this->buildCursorPaginated($rows, $pagingParams);
    }

    /**
     * Read the seek `cursor` and `direction` out of raw request params (before
     * the inherited extractData/validateSort flow mangles or strips them).
     *
     * Honors the same alias-scoped settings pattern as
     * {@see \Cake\Datasource\Paging\NumericPaginator::getDefaults()}: when
     * `$settings[$alias]` is an array, its contents take precedence as the
     * effective settings for cursor/direction/scope.
     *
     * @param string $alias Repository alias for per-model settings unwrapping.
     * @param array<string, mixed> $params Raw request params.
     * @param array<string, mixed> $settings Paginator settings.
     * @return void
     */
    protected function captureSeekParams(string $alias, array $params, array $settings): void
    {
        if (isset($settings[$alias]) && is_array($settings[$alias])) {
            $settings = $settings[$alias] + $settings;
        }

        $scope = $settings['scope'] ?? null;
        if ($scope !== null && isset($params[$scope]) && is_array($params[$scope])) {
            $params = $params[$scope];
        }

        $cursor = $params['cursor'] ?? $settings['cursor'] ?? null;
        if ($cursor === null || $cursor === '') {
            // `direction = before` only makes sense paired with a cursor. Without
            // one, the caller's intent is "the first page", so normalize to
            // `after` to avoid flipping ORDER BY without a predicate (which would
            // return the last page instead).
            $this->requestedCursor = null;
            $this->requestedDirection = 'after';

            return;
        }

        $direction = $params['direction'] ?? $settings['direction'] ?? 'after';
        if (!in_array($direction, ['after', 'before'], true)) {
            $direction = 'after';
        }
        $this->requestedDirection = $direction;

        if (is_array($cursor)) {
            $this->requestedCursor = $cursor;

            return;
        }

        if (!is_string($cursor)) {
            throw new InvalidCursorException('Cursor must be a signed token string or a structured array.');
        }

        $this->requestedCursor = CursorEncoder::decode($cursor);
    }

    /**
     * Extract `[field, direction]` pairs from the query's ORDER BY clause and
     * validate that every entry is usable as a cursor column.
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query Query instance.
     * @return array<int, array{0: string, 1: 'ASC'|'DESC'}>
     * @throws \Cake\Datasource\Paging\Exception\InvalidCursorException
     */
    protected function extractOrderPairs(DatabaseSelectQuery $query): array
    {
        $order = $query->clause('order');
        if (!$order instanceof OrderByExpression) {
            throw new InvalidCursorException(
                'Seek pagination requires an explicit `orderBy()` on the query. '
                . 'The ordering must be deterministic — typically ending in a unique tie-breaker column.',
            );
        }

        $pairs = [];
        foreach ($order->toList() as [$field, $direction]) {
            if (!is_string($field) || $direction === null) {
                throw new InvalidCursorException(
                    'Seek pagination does not support complex order expressions as cursor columns. '
                    . 'Use explicit column references.',
                );
            }
            $pairs[] = [$field, $direction];
        }

        if ($pairs === []) {
            throw new InvalidCursorException('Seek pagination requires an explicit `orderBy()` on the query.');
        }

        return $pairs;
    }

    /**
     * Replace the query's ORDER BY with the same columns in the opposite direction.
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query Query instance.
     * @param array<int, array{0: string, 1: 'ASC'|'DESC'}> $pairs Current order pairs.
     * @return void
     */
    protected function flipOrdering(DatabaseSelectQuery $query, array $pairs): void
    {
        $flipped = [];
        foreach ($pairs as [$field, $direction]) {
            $flipped[$field] = $direction === 'ASC' ? 'DESC' : 'ASC';
        }
        $query->orderBy($flipped, true);
    }

    /**
     * Apply the resolved cursor to the query, if one was provided.
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query Query instance.
     * @return void
     */
    protected function applyCursor(DatabaseSelectQuery $query): void
    {
        if ($this->requestedCursor === null) {
            return;
        }

        try {
            if ($this->requestedDirection === 'before') {
                $query->seekBefore($this->requestedCursor);
            } else {
                $query->seekAfter($this->requestedCursor);
            }
        } catch (DatabaseException $e) {
            // Re-wrap database-layer validation errors as a paging-layer exception
            // so callers of paginate() only have to catch one type.
            throw new InvalidCursorException($e->getMessage(), null, $e);
        }
    }

    /**
     * Build cursor values for next/previous pages from the fetched rows.
     *
     * @param array<string, mixed> $pagingParams Base paging params.
     * @param array<int, mixed> $rows Fetched rows (in result order).
     * @param bool $hasMore Whether the query returned more rows than the page size
     *   (i.e. there is at least one additional record beyond this page in the
     *   forward direction of the query).
     * @return array<string, mixed>
     */
    protected function addCursorParams(array $pagingParams, array $rows, bool $hasMore): array
    {
        $pagingParams['nextCursor'] = null;
        $pagingParams['previousCursor'] = null;
        $pagingParams['nextCursorToken'] = null;
        $pagingParams['previousCursorToken'] = null;

        if ($rows === []) {
            $pagingParams['hasNextPage'] = false;
            $pagingParams['hasPrevPage'] = $this->requestedCursor !== null;

            return $pagingParams;
        }

        $first = $rows[0];
        $last = $rows[count($rows) - 1];

        if ($this->requestedDirection === 'after') {
            $hasNextPage = $hasMore;
            $hasPrevPage = $this->requestedCursor !== null;
        } else {
            // For backward pages, a "next" is the row we came from (always true when
            // the user navigated back), and a "prev" exists if more records preceded
            // this page.
            $hasNextPage = true;
            $hasPrevPage = $hasMore;
        }

        if ($hasNextPage) {
            $cursor = $this->buildCursor($last);
            $pagingParams['nextCursor'] = $cursor;
            $pagingParams['nextCursorToken'] = CursorEncoder::encode($cursor);
        }
        if ($hasPrevPage) {
            $cursor = $this->buildCursor($first);
            $pagingParams['previousCursor'] = $cursor;
            $pagingParams['previousCursorToken'] = CursorEncoder::encode($cursor);
        }

        $pagingParams['hasNextPage'] = $hasNextPage;
        $pagingParams['hasPrevPage'] = $hasPrevPage;

        return $pagingParams;
    }

    /**
     * Extract cursor values from a result row.
     *
     * Fails loudly if a boundary row has `null` in a cursor column: emitting
     * such a cursor would produce tokens that silently skip rows on the
     * follow-up request due to SQL's NULL comparison semantics. Cursor columns
     * must be `NOT NULL` — use a non-nullable tie-breaker (typically the primary
     * key) when leading order columns are nullable.
     *
     * @param mixed $row Result row — entity or array.
     * @return array<string, mixed>
     * @throws \Cake\Datasource\Paging\Exception\InvalidCursorException When any
     *   cursor column on the boundary row is `null`.
     */
    protected function buildCursor(mixed $row): array
    {
        $cursor = [];
        foreach ($this->cursorColumns as $column) {
            $value = $this->readRowValue($row, $column);
            if ($value === null) {
                throw new InvalidCursorException(sprintf(
                    'Boundary row has `null` in cursor column `%s`. Seek pagination requires non-nullable '
                    . 'cursor columns — use a `NOT NULL` tie-breaker (typically the primary key) or exclude '
                    . 'nullable columns from the seek order.',
                    $column,
                ));
            }
            $cursor[$column] = $value;
        }

        return $cursor;
    }

    /**
     * Read a column value from a row, handling both entities and arrays and
     * stripping any table alias prefix.
     *
     * Distinguishes "column not selected" from "column present but null" so
     * misconfigured queries get a precise error instead of being mistaken for
     * a NULL boundary value.
     *
     * @param mixed $row Row.
     * @param string $column Column identifier, optionally prefixed with alias.
     * @return mixed
     * @throws \Cake\Datasource\Paging\Exception\InvalidCursorException When the
     *   column is not present on the row at all (i.e. not selected by the query).
     */
    protected function readRowValue(mixed $row, string $column): mixed
    {
        $short = $column;
        if (str_contains($column, '.')) {
            [, $short] = explode('.', $column, 2);
        }

        if (is_array($row)) {
            if (array_key_exists($column, $row)) {
                return $row[$column];
            }
            if (array_key_exists($short, $row)) {
                return $row[$short];
            }
        } elseif (is_object($row)) {
            if (method_exists($row, 'has') && $row->has($short)) {
                return method_exists($row, 'get') ? $row->get($short) : $row->{$short};
            }
            if (property_exists($row, $short)) {
                return $row->{$short};
            }
        }

        throw new InvalidCursorException(sprintf(
            'Cursor column `%s` is not present on the result row. '
            . 'Ensure the column is included in the query\'s `select()` (or available via the entity).',
            $column,
        ));
    }

    /**
     * Build the final cursor paginated result set.
     *
     * @param array<int, mixed> $rows Rows in display order.
     * @param array<string, mixed> $pagingParams Paging params including cursors.
     * @return \Cake\Datasource\Paging\CursorPaginatedInterface<int, mixed>
     */
    protected function buildCursorPaginated(array $rows, array $pagingParams): CursorPaginatedInterface
    {
        return new CursorPaginatedResultSet(new ArrayIterator($rows), $pagingParams);
    }

    /**
     * @inheritDoc
     */
    protected function getCount(QueryInterface $query, array $data): ?int
    {
        // Seek pagination intentionally skips the count query.
        return null;
    }

    /**
     * @inheritDoc
     */
    protected function buildParams(array $data): array
    {
        $this->pagingParams = [
            'perPage' => $data['options']['limit'],
            'requestedPage' => 1,
            'alias' => $data['alias'],
            'scope' => $data['options']['scope'],
            'maxLimit' => $data['options']['maxLimit'],
            'currentPage' => 1,
            'pageCount' => null,
            'totalCount' => null,
            'start' => 0,
            'end' => 0,
            'hasPrevPage' => false,
            'hasNextPage' => false,
            'sort' => $data['options']['sort'] ?? null,
            'direction' => $data['options']['direction'] ?? null,
            'sortDefault' => false,
            'directionDefault' => false,
            'completeSort' => [],
        ] + $this->pagingParams;

        return $this->pagingParams;
    }
}
