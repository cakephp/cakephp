<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

use Cake\Database\DriverInterface;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\BaseType;
use Cake\Database\Type\ExpressionTypeInterface;
use Cake\Database\Type\SchemaAwareTypeInterface;
use Cake\Database\Type\SelectExpressionTypeInterface;
use TestApp\Database\Point;

class PointType extends BaseType implements ExpressionTypeInterface, SelectExpressionTypeInterface, SchemaAwareTypeInterface
{
    public function toPHP($value, DriverInterface $d)
    {
        return Point::fromGeoJSON(json_decode($value, true));
    }

    public function marshal($value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (is_array($value)) {
            return new Point($value[0], $value[1]);
        }

        return null;
    }

    public function toExpression($value): ExpressionInterface
    {
        if ($value instanceof Point) {
            return new FunctionExpression('POINT', [$value->lat(), $value->long()]);
        }

        if (is_array($value)) {
            return new FunctionExpression('POINT', [$value[0], $value[1]]);
        }

        throw new \InvalidArgumentException(sprintf(
            'The `$value` argument must be an instance of `\%s`, or an array, `%s` given.',
            Point::class,
            getTypeName($value)
        ));
    }

    public function toSelectExpression($field): ExpressionInterface
    {
        if (is_string($field)) {
            $args = [$field => 'identifier'];
        } else {
            $args = [$field];
        }

        return new FunctionExpression('ST_AsGeoJSON', $args);
    }

    public function toDatabase($value, DriverInterface $driver)
    {
        return $value;
    }

    public function getSchemaColumnSql(TableSchemaInterface $schema, string $column, DriverInterface $driver): ?string
    {
        $data = $schema->getColumn($column);

        $sql = $driver->quoteIdentifier($column);
        $sql .= ' POINT';

        if (
            isset($data['null']) &&
            $data['null'] === false
        ) {
            $sql .= ' NOT NULL';
        }

        if (
            isset($data['comment']) &&
            $data['comment'] !== ''
        ) {
            $sql .= ' COMMENT ' . $driver->schemaValue($data['comment']);
        }

        return $sql;
    }

    public function convertSchemaColumn(array $definition, DriverInterface $driver): ?array
    {
        return ['type' => $this->_name, 'length' => null];
    }
}
