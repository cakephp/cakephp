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
namespace Cake\Database\Driver;

use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\MariadbSchemaDialect;
use Cake\Database\Schema\SchemaDialect;
use PDO;

/**
 * Mariadb Driver
 *
 * While MariaDb shares the same PDO driver, the SQL dialect,
 * and schema reflection have enough minor differences to justify
 * a separate driver.
 */
class Mariadb extends Mysql
{
    /**
     * Mapping of feature to db server version for feature availability checks.
     *
     * @var array<string, string>
     */
    protected array $featureVersionMap = [
        'json' => '10.2.7',
        'cte' => '10.2.1',
        'window' => '10.2.0',
        'string-agg' => '10.5.0',
        'intersect' => '10.3.0',
        'intersect-all' => '10.5.0',
        'except' => '10.3.0',
        'except-all' => '10.5.0',
        'check-constraints' => '10.2.1',
    ];

    /**
     * @inheritDoc
     */
    public function schemaDialect(): SchemaDialect
    {
        return $this->_schemaDialect ?? ($this->_schemaDialect = new MariadbSchemaDialect($this));
    }

    /**
     * @inheritDoc
     */
    public function supports(DriverFeatureEnum $feature): bool
    {
        $versionCompare = function () use ($feature) {
            return version_compare(
                $this->version(),
                $this->featureVersionMap[$feature->value],
                '>=',
            );
        };

        return match ($feature) {
            DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION,
            DriverFeatureEnum::SAVEPOINT => true,

            DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS => false,

            DriverFeatureEnum::CTE,
            DriverFeatureEnum::JSON,
            DriverFeatureEnum::WINDOW => $versionCompare(),
            DriverFeatureEnum::STRING_AGG => $versionCompare(),
            DriverFeatureEnum::GROUP_CONCAT => true,
            DriverFeatureEnum::INTERSECT => $versionCompare(),
            DriverFeatureEnum::INTERSECT_ALL => $versionCompare(),
            DriverFeatureEnum::EXCEPT => $versionCompare(),
            DriverFeatureEnum::EXCEPT_ALL => $versionCompare(),
            DriverFeatureEnum::CHECK_CONSTRAINTS => $versionCompare(),
            DriverFeatureEnum::SET_OPERATIONS_ORDER_BY => true,
            DriverFeatureEnum::OPTIMIZER_HINT_COMMENT => true,
            DriverFeatureEnum::CASE_SENSITIVE_QUOTED_IDENTIFIERS => false,
            DriverFeatureEnum::SUBQUERY_FILTER_DISTINCT => true,
        };
    }

    /**
     * Returns connected server version.
     *
     * @return string
     */
    public function version(): string
    {
        if ($this->_version === null) {
            $version = (string)$this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

            if (preg_match('/^(?:5\.5\.5-)?(\d+\.\d+\.\d+.*-MariaDB[^:]*)/', $version, $matches)) {
                $version = $matches[1];
            }
            $this->_version = $version;
        }

        return $this->_version;
    }
}
