<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;

/**
 * Used to test correct class is instantiated when using $this->_locator->get();
 */
class MyUsersTable extends Table
{
    /**
     * Overrides default table name
     *
     * @var string
     */
    protected $_table = 'users';
}
