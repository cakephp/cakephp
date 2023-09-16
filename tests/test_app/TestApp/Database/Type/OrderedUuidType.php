<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Type\BaseType;
use Cake\Database\Type\ExpressionTypeInterface;

/**
 * Custom type class that maps between value objects, and SQL expressions.
 */
class OrderedUuidType extends BaseType implements ExpressionTypeInterface
{
    /**
     * @inheritDoc
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        return new UuidValue($value);
    }

    /**
     * @inheritDoc
     */
    public function toExpression(mixed $value): ExpressionInterface
    {
        if ($value instanceof UuidValue) {
            $value = $value->value;
        }
        $substr = function ($start, $length = null) use ($value) {
            return new FunctionExpression(
                'SUBSTR',
                $length === null ? [$value, $start] : [$value, $start, $length],
                ['string', 'integer', 'integer']
            );
        };

        return new FunctionExpression(
            'CONCAT',
            [$substr(15, 4), $substr(10, 4), $substr(1, 8), $substr(20, 4), $substr(25)]
        );
    }

    /**
     * @inheritDoc
     */
    public function marshal(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        return $value;
    }
}
