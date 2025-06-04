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
namespace Cake\Datasource\Paging;

use IteratorAggregate;
use JsonSerializable;
use Traversable;
use function Cake\Core\deprecationWarning;

/**
 * Paginated result set.
 *
 * @template T of mixed
 * @template-implements \IteratorAggregate<mixed>
 */
class PaginatedResultSet implements IteratorAggregate, JsonSerializable, PaginatedInterface
{
    /**
     * Resultset instance.
     *
     * @var \Traversable<T>
     */
    protected Traversable $results;

    /**
     * Paging params.
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Constructor
     *
     * @param \Traversable<T> $results Resultset instance.
     * @param array $params Paging params.
     */
    public function __construct(Traversable $results, array $params)
    {
        $this->results = $results;
        $this->params = $params;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->params['count'];
    }

    /**
     * Get the paginated items as an array.
     *
     * This will exhaust the iterator `items`.
     *
     * @return array<array-key, T>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Get paginated items.
     *
     * @return \Traversable<T> The paginated items result set.
     */
    public function items(): Traversable
    {
        return $this->results;
    }

    /**
     * Provide data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return iterator_to_array($this->items());
    }

    /**
     * @inheritDoc
     */
    public function totalCount(): ?int
    {
        return $this->params['totalCount'];
    }

    /**
     * @inheritDoc
     */
    public function perPage(): int
    {
        return $this->params['perPage'];
    }

    /**
     * @inheritDoc
     */
    public function pageCount(): ?int
    {
        return $this->params['pageCount'];
    }

    /**
     * @inheritDoc
     */
    public function currentPage(): int
    {
        return $this->params['currentPage'];
    }

    /**
     * @inheritDoc
     */
    public function hasPrevPage(): bool
    {
        return $this->params['hasPrevPage'];
    }

    /**
     * @inheritDoc
     */
    public function hasNextPage(): bool
    {
        return $this->params['hasNextPage'];
    }

    /**
     * @inheritDoc
     */
    public function pagingParam(string $name): mixed
    {
        return $this->params[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function pagingParams(): array
    {
        return $this->params;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        return $this->results;
    }

    /**
     * Proxies method calls to internal result set instance.
     *
     * @param string $name Method name
     * @param array $arguments Arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        deprecationWarning(
            '5.1.0',
            sprintf(
                'Calling `%s` methods, such as `%s()`, on PaginatedResultSet is deprecated. ' .
                'You must call `items()` first (for example, `items()->%s()`).',
                $this->results::class,
                $name,
                $name,
            ),
        );

        return $this->results->$name(...$arguments);
    }
}
