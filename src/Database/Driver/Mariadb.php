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

use Cake\Database\Enum\DriverFeature;
use Cake\Database\Schema\MariadbSchemaDialect;
use Cake\Database\Schema\SchemaDialect;
use PDO;
use function Cake\Core\deprecationWarning;

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
        return $this->schemaDialect ?? ($this->schemaDialect = new MariadbSchemaDialect($this));
    }

    /**
     * @inheritDoc
     */
    public function supports(DriverFeature $feature): bool
    {
        $versionCompare = function () use ($feature) {
            return version_compare(
                $this->version(),
                $this->featureVersionMap[$feature->value],
                '>=',
            );
        };

        return match ($feature) {
            DriverFeature::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION,
            DriverFeature::SAVEPOINT => true,

            DriverFeature::TRUNCATE_WITH_CONSTRAINTS => false,

            DriverFeature::CTE,
            DriverFeature::JSON,
            DriverFeature::WINDOW => $versionCompare(),
            DriverFeature::STRING_AGG => $versionCompare(),
            DriverFeature::GROUP_CONCAT => true,
            DriverFeature::INTERSECT => $versionCompare(),
            DriverFeature::INTERSECT_ALL => $versionCompare(),
            DriverFeature::EXCEPT => $versionCompare(),
            DriverFeature::EXCEPT_ALL => $versionCompare(),
            DriverFeature::CHECK_CONSTRAINTS => $versionCompare(),
            DriverFeature::SET_OPERATIONS_ORDER_BY => true,
            DriverFeature::OPTIMIZER_HINT_COMMENT => true,
            DriverFeature::CASE_SENSITIVE_QUOTED_IDENTIFIERS => false,
            DriverFeature::SUBQUERY_FILTER_DISTINCT => true,
        };
    }

    /**
     * Returns connected server version.
     *
     * @return string
     */
    public function version(): string
    {
        if ($this->version === null) {
            $version = (string)$this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

            if (preg_match('/^(?:5\.5\.5-)?(\d+\.\d+\.\d+.*-MariaDB[^:]*)/', $version, $matches)) {
                $version = $matches[1];
            }
            $this->version = $version;
        }

        return $this->version;
    }

    /**
     * @inheritDoc
     */
    public function isMariadb(): bool
    {
        deprecationWarning(
            '5.5.0',
            'The isMariadb() method is deprecated.' .
            'Use `instanceof Mariadb` instead for runtime checks.',
        );

        return true;
    }
}
