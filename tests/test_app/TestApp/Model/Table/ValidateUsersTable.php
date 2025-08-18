<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;

class ValidateUsersTable extends Table
{
    /**
     * Initializes the schema
     *
     * @param array $config
     */
    public function initialize(array $config): void
    {
        $this->setSchema([
            'id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => 8],
            'name' => ['type' => 'string', 'null' => true, 'default' => '', 'length' => 255],
            'email' => ['type' => 'string', 'null' => true, 'default' => '', 'length' => 255],
            'balance' => ['type' => 'float', 'null' => false, 'length' => 5, 'precision' => 2],
            'cost_decimal' => ['type' => 'decimal', 'null' => false, 'length' => 6, 'precision' => 3],
            'null_decimal' => ['type' => 'decimal', 'null' => false, 'length' => null, 'precision' => null],
            'ratio' => ['type' => 'decimal', 'null' => false, 'length' => 10, 'precision' => 6],
            'population' => ['type' => 'decimal', 'null' => false, 'length' => 15, 'precision' => 0],
            'created' => ['type' => 'date', 'null' => true, 'default' => '', 'length' => null],
            'updated' => ['type' => 'datetime', 'null' => true, 'default' => '', 'length' => null],
            '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
        ]);
    }
}
