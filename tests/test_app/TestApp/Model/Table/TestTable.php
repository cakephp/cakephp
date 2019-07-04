<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\Datasource\QueryInterface;
use Cake\ORM\Table;

/**
 * A Test double used to assert that default tables are created
 */
class TestTable extends Table
{
    /**
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setSchema(['id' => ['type' => 'integer']]);
    }

    /**
     * @param \Cake\Datasource\QueryInterface $query
     * @return \Cake\Datasource\QueryInterface
     */
    public function findPublished(QueryInterface $query)
    {
        return $query->applyOptions(['this' => 'worked']);
    }
}
