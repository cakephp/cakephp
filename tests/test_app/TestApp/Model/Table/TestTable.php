<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * A Test double used to assert that default tables are created
 */
class TestTable extends Table
{
    public mixed $first;
    public array $variadic;
    public array $variadicOptions;

    /**
     * @param array $config
     */
    public function initialize(array $config): void
    {
        $this->setSchema(['id' => ['type' => 'integer']]);
    }

    public function findPublishedWithArgOnly(SelectQuery $query, string $what = 'worked', mixed $other = null): SelectQuery
    {
        return $query->applyOptions(['this' => $what]);
    }

    public function findWithOptions(SelectQuery $query, array $options): SelectQuery
    {
        return $query->applyOptions(['this' => 'worked']);
    }

    public function findVariadicOptions(SelectQuery $query, ...$options)
    {
        $this->variadicOptions = $options;

        return $query;
    }

    public function findVariadic(SelectQuery $query, mixed $first = null, mixed ...$variadic)
    {
        $this->first = $first;
        $this->variadic = $variadic;

        return $query;
    }
}
