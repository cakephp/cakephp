<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.2.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestPlugin\Database\Driver;

use Cake\Database\Driver\Sqlite;

class TestDriver extends Sqlite
{
    public function enabled()
    {
        return true;
    }
}
