<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Table;

class PointsTable extends Table
{
    protected $_table = 'points';

    protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface
    {
        $schema = parent::_initializeSchema($schema);
        $schema->setColumnType('pt', 'point');

        return $schema;
    }
}
