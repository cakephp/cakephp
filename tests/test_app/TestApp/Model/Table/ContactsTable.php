<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;

class ContactsTable extends Table
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
            'phone' => ['type' => 'string', 'null' => true, 'default' => '', 'length' => 255],
            'password' => ['type' => 'string', 'null' => true, 'default' => '', 'length' => 255],
            'published' => ['type' => 'date', 'null' => true, 'default' => null, 'length' => null],
            'created' => ['type' => 'date', 'null' => true, 'default' => '', 'length' => null],
            'updated' => ['type' => 'datetime', 'null' => true, 'default' => '', 'length' => null],
            'age' => ['type' => 'integer', 'null' => true, 'default' => '', 'length' => null],
            '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
        ]);
    }
}
