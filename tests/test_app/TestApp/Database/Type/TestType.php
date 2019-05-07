<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Type\ExpressionTypeInterface;
use Cake\Database\Type\StringType;

class TestType extends StringType implements ExpressionTypeInterface
{
    public function toExpression($value): ExpressionInterface
    {
        return new FunctionExpression('CONCAT', [$value, ' - foo']);
    }
}
