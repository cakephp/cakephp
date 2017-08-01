<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.2.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @deprecated 3.5.0 Use Cake\Database\Schema\TableSchemaAwareInterface instead.
 */
namespace Cake\Datasource;

use Cake\Database\Schema\TableSchema;

/**
 * Defines the interface for getting the schema.
 */
interface TableSchemaInterface
{

    /**
     * Get and set the schema for this fixture.
     *
     * @param \Cake\Database\Schema\TableSchema|null $schema The table to set.
     * @return \Cake\Database\Schema\TableSchema|null
     */
    public function schema(TableSchema $schema = null);
}
