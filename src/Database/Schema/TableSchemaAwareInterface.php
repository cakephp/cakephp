<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

/**
 * Defines the interface for getting the schema.
 */
interface TableSchemaAwareInterface
{

    /**
     * Get and set the schema for this fixture.
     *
     * @return \Cake\Database\Schema\TableSchemaInterface|null
     */
    public function getTableSchema();

    /**
     * Get and set the schema for this fixture.
     *
     * @param \Cake\Database\Schema\TableSchemaInterface $schema The table to set.
     * @return $this
     */
    public function setTableSchema(TableSchemaInterface $schema);
}
