<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

use Cake\Database\Driver\Mysql;
use Cake\Database\DriverInterface;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\BaseType;
use Cake\Database\Type\ExpressionTypeInterface;
use Cake\Database\Type\SchemaAwareTypeInterface;
use TestApp\Database\SchemaAwareTypeValueObject;

class SchemaAwareType extends BaseType implements ExpressionTypeInterface, SchemaAwareTypeInterface
{
    public function toPHP($value, DriverInterface $driver)
    {
        return $value;
    }

    public function marshal($value)
    {
        return $value;
    }

    public function toDatabase($value, DriverInterface $driver)
    {
        return $value;
    }

    public function toExpression($value): ExpressionInterface
    {
        if ($value instanceof SchemaAwareTypeValueObject) {
            $value = $value->value();
        }

        if (is_string($value)) {
            return new FunctionExpression(
                'REPLACE',
                [
                    new FunctionExpression('LOWER', [$value]),
                    'should be',
                    'has been',
                ]
            );
        }

        throw new \InvalidArgumentException(sprintf(
            'The `$value` argument must be an instance of `\%s`, or a string, `%s` given.',
            SchemaAwareTypeValueObject::class,
            getTypeName($value)
        ));
    }

    public function getSchemaColumnSql(TableSchemaInterface $schema, string $column, DriverInterface $driver): ?string
    {
        $data = $schema->getColumn($column);

        $sql = $driver->quoteIdentifier($column);
        $sql .= ' TEXT';

        if (
            isset($data['null']) &&
            $data['null'] === false
        ) {
            $sql .= ' NOT NULL';
        }

        if (
            ($driver instanceof Mysql) &&
            isset($data['comment']) &&
            $data['comment'] !== ''
        ) {
            $sql .= ' COMMENT ' . $driver->schemaValue($data['comment'] . ' (schema aware)');
        }

        return $sql;
    }

    public function convertSchemaColumn(array $definition, DriverInterface $driver): ?array
    {
        return [
            'type' => $this->_name,
            'length' => 255,
            'comment' => 'Custom schema aware type comment',
        ];
    }
}
