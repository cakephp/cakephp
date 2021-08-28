<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

use Cake\Database\Driver\Mysql;
use Cake\Database\DriverInterface;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\BaseType;
use Cake\Database\Type\ColumnSchemaAwareInterface;
use Cake\Database\Type\ExpressionTypeInterface;
use InvalidArgumentException;
use TestApp\Database\ColumnSchemaAwareTypeValueObject;

class ColumnSchemaAwareType extends BaseType implements ExpressionTypeInterface, ColumnSchemaAwareInterface
{
    /**
     * @inheritDoc
     */
    public function toPHP($value, DriverInterface $driver)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function marshal($value)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function toDatabase($value, DriverInterface $driver)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function toExpression($value): ExpressionInterface
    {
        if ($value instanceof ColumnSchemaAwareTypeValueObject) {
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

        throw new InvalidArgumentException(sprintf(
            'The `$value` argument must be an instance of `\%s`, or a string, `%s` given.',
            ColumnSchemaAwareTypeValueObject::class,
            getTypeName($value)
        ));
    }

    /**
     * @inheritDoc
     */
    public function getColumnSql(TableSchemaInterface $schema, string $column, DriverInterface $driver): ?string
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

    /**
     * @inheritDoc
     */
    public function convertColumnDefinition(array $definition, DriverInterface $driver): ?array
    {
        return [
            'type' => 'text',
            'length' => 255,
            'comment' => 'Custom schema aware type comment',
        ];
    }
}
