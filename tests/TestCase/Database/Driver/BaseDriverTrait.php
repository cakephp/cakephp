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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Driver;

use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\SqliteSchemaDialect;
use Mockery;

trait BaseDriverTrait
{
    public function connect(): void
    {
        $this->pdo = Mockery::mock('PDO')
            ->shouldReceive('inTransaction', 'beginTransaction')
            ->getMock();
    }

    public function enabled(): bool
    {
        return true;
    }

    public function disableForeignKeySQL(): string
    {
        return '';
    }

    public function enableForeignKeySQL(): string
    {
        return '';
    }

    public function schemaDialect(): SchemaDialect
    {
        return new SqliteSchemaDialect($this);
    }

    public function supports(DriverFeatureEnum $feature): bool
    {
        return true;
    }
}
