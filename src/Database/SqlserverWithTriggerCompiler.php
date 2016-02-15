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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Responsible for compiling a Query object into its SQL representation
 * for SQL Server
 *
 * @internal
 */
class SqlserverWithTriggerCompiler extends SqlserverCompiler
{
    /**
     * Generates the INSERT part of a SQL query
     *
     * SQL Server with enalbed triggers does not allow the OUTPUT clause 
     * so that the INSERT is generated with a temporary variable.
     *
     * @param array $parts The parts to build
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildInsertPart($parts, $query, $generator)
    {
        $table = $parts[0];
        $talbeParts = explode('.', $table);
        if (count($talbeParts) > 1) {
            list(,$tableWithoutSchema) = $talbeParts;
        } else {
            $tableWithoutSchema = $parts[0];
        }
        $tableWithoutSchema = str_replace(['[', ']'], '', $tableWithoutSchema);
        $db = $query->connection();
        $driver = $db->driver();
        $dialect = $driver->schemaDialect();
        $collection = $db->schemaCollection();
        $description = $collection->describe($tableWithoutSchema);
        $constraints = $description->constraints();
        $primaryKeyColumns = [];
        foreach ($constraints as $constraintName) {
            $constraint = $description->constraint($constraintName);
            if ($constraint['type'] === \Cake\Database\Schema\Table::CONSTRAINT_PRIMARY) {
                $primaryKeyColumns = $constraint['columns'];
                $description->dropConstraint($constraintName);
            }
        }
        $sqlCreateColumns = [];
        $primaryKeyColumnsForSql = [];
        foreach($description->columns() as $columnName) {
            if (in_array($columnName, $primaryKeyColumns)) {
                $descriptionOfSingleColumn = $description->column($columnName);
                if (isset($descriptionOfSingleColumn['autoIncrement']) && $descriptionOfSingleColumn['autoIncrement']) {
                    $descriptionOfSingleColumn['autoIncrement'] = false;
                }
                $description->addColumn($columnName, $descriptionOfSingleColumn);
                $sqlCreateColumns[] = $dialect->columnSql($description, $columnName);
                $primaryKeyColumnsForSql[] = $columnName;
            }
        }
        $columns = $this->_stringifyExpressions($parts[1], $generator);
        return sprintf('DECLARE @var TABLE (%s);INSERT INTO %s (%s) OUTPUT INSERTED.%s INTO @var', implode(', ', $sqlCreateColumns), $table, implode(', ', $columns), implode(' , INSERTED.', $primaryKeyColumnsForSql));
    }

    /**
     * Builds the SQL fragment for INSERT INTO.
     *
     * @param array $parts The values parts.
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string SQL fragment.
     */
    protected function _buildValuesPart($parts, $query, $generator)
    {
        $result = implode('', $this->_stringifyExpressions($parts, $generator)) .
                ';SELECT * FROM @var';
        return $result;
    }
}
