<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Database\Driver;

use Cake\Database\Driver;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\SqliteSchemaDialect;

class StubDriver extends Driver
{
    public function connect(): void
    {
        $this->pdo = $this->createPdo('', []);
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
