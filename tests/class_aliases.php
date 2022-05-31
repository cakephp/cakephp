<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

class_alias('Cake\Command\Command', 'Cake\Console\Command');
class_alias('Cake\Controller\ControllerFactory', 'Cake\Http\ControllerFactory');
class_alias('Cake\Core\Exception\CakeException', 'Cake\Core\Exception\Exception');

class_alias('Cake\Database\Driver\SqlDialectTrait', 'Cake\Database\SqlDialectTrait');
class_alias('Cake\Database\Exception\DatabaseException', 'Cake\Database\Exception');
class_alias('Cake\Database\Expression\ComparisonExpression', 'Cake\Database\Expression\Comparison');

class_alias('Cake\Database\Schema\MysqlSchemaDialect', 'Cake\Database\Schema\MysqlSchema');
class_alias('Cake\Database\Schema\PostgresSchemaDialect', 'Cake\Database\Schema\PostgresSchema');
class_alias('Cake\Database\Schema\SchemaDialect', 'Cake\Database\Schema\BaseSchema');
class_alias('Cake\Database\Schema\SqliteSchemaDialect', 'Cake\Database\Schema\SqliteSchema');
class_alias('Cake\Database\Schema\SqlserverSchemaDialect', 'Cake\Database\Schema\SqlserverSchema');
class_alias('Cake\Database\TypeFactory', 'Cake\Database\Type');

class_alias('Cake\Error\ConsoleErrorHandler', 'Cake\Console\ConsoleErrorHandler');
class_alias('Cake\Http\Exception\MissingControllerException', 'Cake\Routing\Exception\MissingControllerException');

if (!class_exists('Aura\Intl\Package')) {
    class_alias('Cake\I18n\Package', 'Aura\Intl\Package');
}
