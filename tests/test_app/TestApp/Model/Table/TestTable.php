<?php
declare(strict_types=1);
namespace TestApp\Model\Table;

use Cake\ORM\Table;

/**
 * A Test double used to assert that default tables are created
 */
class TestTable extends Table
{
    public function initialize(array $config = []): void
    {
        $this->setSchema(['id' => ['type' => 'integer']]);
    }

    public function findPublished($query)
    {
        return $query->applyOptions(['this' => 'worked']);
    }
}
