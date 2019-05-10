<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;

class ValidateUsersTable extends Table
{
    /**
     * schema method
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'email' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'balance' => ['type' => 'float', 'null' => false, 'length' => 5, 'precision' => 2],
        'cost_decimal' => ['type' => 'decimal', 'null' => false, 'length' => 6, 'precision' => 3],
        'null_decimal' => ['type' => 'decimal', 'null' => false, 'length' => null, 'precision' => null],
        'ratio' => ['type' => 'decimal', 'null' => false, 'length' => 10, 'precision' => 6],
        'population' => ['type' => 'decimal', 'null' => false, 'length' => 15, 'precision' => 0],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * Initializes the schema
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setSchema($this->_schema);
    }
}
