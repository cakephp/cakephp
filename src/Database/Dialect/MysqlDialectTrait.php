<?php
declare(strict_types=1);

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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Dialect;

use Cake\Database\Schema\BaseSchema;
use Cake\Database\Schema\MysqlSchema;
use Cake\Database\SqlDialectTrait;

/**
 * Contains functions that encapsulates the SQL dialect used by MySQL,
 * including query translators and schema introspection.
 *
 * @internal
 */
trait MysqlDialectTrait
{
    use SqlDialectTrait;

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_startQuote = '`';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_endQuote = '`';

    /**
     * The schema dialect class for this driver
     *
     * @var \Cake\Database\Schema\MysqlSchema
     */
    protected $_schemaDialect;

    /**
     * Get the schema dialect.
     *
     * Used by Cake\Database\Schema package to reflect schema and
     * generate schema.
     *
     * @return \Cake\Database\Schema\BaseSchema
     */
    public function schemaDialect(): BaseSchema
    {
        if ($this->_schemaDialect === null) {
            $this->_schemaDialect = new MysqlSchema($this);
        }

        return $this->_schemaDialect;
    }

    /**
     * @inheritDoc
     */
    public function disableForeignKeySQL(): string
    {
        return 'SET foreign_key_checks = 0';
    }

    /**
     * @inheritDoc
     */
    public function enableForeignKeySQL(): string
    {
        return 'SET foreign_key_checks = 1';
    }
}
