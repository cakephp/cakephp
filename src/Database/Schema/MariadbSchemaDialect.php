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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

/**
 * Schema generation/reflection features for Mariadb
 *
 * @internal
 */
class MariadbSchemaDialect extends MysqlSchemaDialect
{
    /**
     * Parse the default value if required.
     *
     * @param string $type The type of column
     * @param array $row a Row of schema reflection data
     * @return ?string The default value of a column.
     */
    protected function parseDefault(string $type, array $row): ?string
    {
        $default = parent::parseDefault($type, $row);
        if ($default === 'current_timestamp()') {
            return 'CURRENT_TIMESTAMP';
        }

        return $default;
    }

    /**
     * Describes geometry-specific column information.
     *
     * @param string $table The table name.
     * @return array<string, array{name: string, srid: int}> The column information.
     */
    protected function describeGeometryColumns(string $table): array
    {
        return [];
    }
}
