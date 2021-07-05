<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Query;
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

    public function findPublished(Query $query): Query
    {
        return $query->applyOptions(['this' => 'worked']);
    }
}
