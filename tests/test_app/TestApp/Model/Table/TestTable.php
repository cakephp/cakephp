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
    /**
     * @param array $config
     */
    public function initialize(array $config): void
    {
        $this->setSchema(['id' => ['type' => 'integer']]);
    }

    public function findPublished(SelectQuery $query, array $options, string $what = 'worked'): SelectQuery
    {
        return $query->applyOptions(['this' => $what]);
    }

    public function findPublishedWithArgOnly(SelectQuery $query, string $what = 'worked'): SelectQuery
    {
        return $query->applyOptions(['this' => $what]);
    }
}
