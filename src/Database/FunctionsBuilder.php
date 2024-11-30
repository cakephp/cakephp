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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Expression\AggregateExpression;
use Cake\Database\Expression\FunctionExpression;
use InvalidArgumentException;

/**
 * Contains methods related to generating FunctionExpression objects
 * with most commonly used SQL functions.
 * This acts as a factory for FunctionExpression objects.
 */
class FunctionsBuilder
{
    /**
     * Returns a FunctionExpression representing a call to SQL RAND function.
     *
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function rand(): FunctionExpression
    {
        return new FunctionExpression('RAND', [], [], 'float');
    }

    /**
     * Returns a AggregateExpression representing a call to SQL SUM function.
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function sum(ExpressionInterface|string $expression, array $types = []): AggregateExpression
    {
        $returnType = 'float';
        if (current($types) === 'integer') {
            $returnType = 'integer';
        }

        return $this->aggregate('SUM', $this->toLiteralParam($expression), $types, $returnType);
    }

    /**
     * Returns a AggregateExpression representing a call to SQL AVG function.
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function avg(ExpressionInterface|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('AVG', $this->toLiteralParam($expression), $types, 'float');
    }

    /**
     * Returns a AggregateExpression representing a call to SQL MAX function.
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function max(ExpressionInterface|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('MAX', $this->toLiteralParam($expression), $types, current($types) ?: 'float');
    }

    /**
     * Returns a AggregateExpression representing a call to SQL MIN function.
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function min(ExpressionInterface|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('MIN', $this->toLiteralParam($expression), $types, current($types) ?: 'float');
    }

    /**
     * Returns a AggregateExpression representing a call to SQL COUNT function.
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function count(ExpressionInterface|string $expression, array $types = []): AggregateExpression
    {
        return $this->aggregate('COUNT', $this->toLiteralParam($expression), $types, 'integer');
    }

    /**
     * Returns a FunctionExpression representing a string concatenation
     *
     * @param array $args List of strings or expressions to concatenate
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function concat(array $args, array $types = []): FunctionExpression
    {
        return new FunctionExpression('CONCAT', $args, $types, 'string');
    }

    /**
     * Returns a FunctionExpression representing a call to SQL COALESCE function.
     *
     * @param array $args List of expressions to evaluate as function parameters
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function coalesce(array $args, array $types = []): FunctionExpression
    {
        return new FunctionExpression('COALESCE', $args, $types, current($types) ?: 'string');
    }

    /**
     * Returns a FunctionExpression representing a SQL CAST.
     *
     * The `$type` parameter is a SQL type. The return type for the returned expression
     * is the default type name. Use `setReturnType()` to update it.
     *
     * @param \Cake\Database\ExpressionInterface|string $field Field or expression to cast.
     * @param string $dataType The SQL data type
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function cast(ExpressionInterface|string $field, string $dataType): FunctionExpression
    {
        $expression = new FunctionExpression('CAST', $this->toLiteralParam($field));

        return $expression->setConjunction(' AS')->add([$dataType => 'literal']);
    }

    /**
     * Returns a FunctionExpression representing the difference in days between
     * two dates.
     *
     * @param array $args List of expressions to obtain the difference in days.
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function dateDiff(array $args, array $types = []): FunctionExpression
    {
        return new FunctionExpression('DATEDIFF', $args, $types, 'integer');
    }

    /**
     * Returns the specified date part from the SQL expression.
     *
     * @param string $part Part of the date to return.
     * @param \Cake\Database\ExpressionInterface|string $expression Expression to obtain the date part from.
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function datePart(
        string $part,
        ExpressionInterface|string $expression,
        array $types = [],
    ): FunctionExpression {
        return $this->extract($part, $expression, $types);
    }

    /**
     * Returns the specified date part from the SQL expression.
     *
     * @param string $part Part of the date to return.
     * @param \Cake\Database\ExpressionInterface|string $expression Expression to obtain the date part from.
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function extract(string $part, ExpressionInterface|string $expression, array $types = []): FunctionExpression
    {
        $expression = new FunctionExpression('EXTRACT', $this->toLiteralParam($expression), $types, 'integer');

        return $expression->setConjunction(' FROM')->add([$part => 'literal'], [], true);
    }

    /**
     * Add the time unit to the date expression
     *
     * @param \Cake\Database\ExpressionInterface|string $expression Expression to obtain the date part from.
     * @param string|int $value Value to be added. Use negative to subtract.
     * @param string $unit Unit of the value e.g. hour or day.
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function dateAdd(
        ExpressionInterface|string $expression,
        string|int $value,
        string $unit,
        array $types = [],
    ): FunctionExpression {
        if (!is_numeric($value)) {
            $value = 0;
        }
        $interval = $value . ' ' . $unit;
        $expression = new FunctionExpression('DATE_ADD', $this->toLiteralParam($expression), $types, 'datetime');

        return $expression->setConjunction(', INTERVAL')->add([$interval => 'literal']);
    }

    /**
     * Returns a FunctionExpression representing a call to SQL WEEKDAY function.
     * 1 - Sunday, 2 - Monday, 3 - Tuesday...
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function dayOfWeek(ExpressionInterface|string $expression, array $types = []): FunctionExpression
    {
        return new FunctionExpression('DAYOFWEEK', $this->toLiteralParam($expression), $types, 'integer');
    }

    /**
     * Returns a FunctionExpression representing a call to SQL WEEKDAY function.
     * 1 - Sunday, 2 - Monday, 3 - Tuesday...
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function weekday(ExpressionInterface|string $expression, array $types = []): FunctionExpression
    {
        return $this->dayOfWeek($expression, $types);
    }

    /**
     * Returns a FunctionExpression representing a call that will return the current
     * date and time. By default it returns both date and time, but you can also
     * make it generate only the date or only the time.
     *
     * @param string $type (datetime|date|time)
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function now(string $type = 'datetime'): FunctionExpression
    {
        return match ($type) {
            'datetime' => new FunctionExpression('NOW', [], [], 'datetime'),
            'date' => new FunctionExpression('CURRENT_DATE', [], [], 'date'),
            'time' => new FunctionExpression('CURRENT_TIME', [], [], 'time'),
            default => throw new InvalidArgumentException('Invalid argument for FunctionsBuilder::now(): ' . $type),
        };
    }

    /**
     * Returns an AggregateExpression representing call to SQL ROW_NUMBER().
     *
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function rowNumber(): AggregateExpression
    {
        return (new AggregateExpression('ROW_NUMBER', [], [], 'integer'))->over();
    }

    /**
     * Returns an AggregateExpression representing call to SQL LAG().
     *
     * @param \Cake\Database\ExpressionInterface|string $expression The value evaluated at offset
     * @param int $offset The row offset
     * @param mixed $default The default value if offset doesn't exist
     * @param string|null $type The output type of the lag expression. Defaults to float.
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function lag(
        ExpressionInterface|string $expression,
        int $offset,
        mixed $default = null,
        ?string $type = null,
    ): AggregateExpression {
        $params = $this->toLiteralParam($expression) + [$offset => 'literal'];
        if ($default !== null) {
            $params[] = $default;
        }

        $types = [];
        if ($type !== null) {
            $types = [$type, 'integer', $type];
        }

        return (new AggregateExpression('LAG', $params, $types, $type ?? 'float'))->over();
    }

    /**
     * Returns an AggregateExpression representing call to SQL LEAD().
     *
     * @param \Cake\Database\ExpressionInterface|string $expression The value evaluated at offset
     * @param int $offset The row offset
     * @param mixed $default The default value if offset doesn't exist
     * @param string|null $type The output type of the lead expression. Defaults to float.
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function lead(
        ExpressionInterface|string $expression,
        int $offset,
        mixed $default = null,
        ?string $type = null,
    ): AggregateExpression {
        $params = $this->toLiteralParam($expression) + [$offset => 'literal'];
        if ($default !== null) {
            $params[] = $default;
        }

        $types = [];
        if ($type !== null) {
            $types = [$type, 'integer', $type];
        }

        return (new AggregateExpression('LEAD', $params, $types, $type ?? 'float'))->over();
    }

    /**
     * Returns a FunctionExpression representing the Json Value
     *
     * @param \Cake\Database\ExpressionInterface|string $expression The Json value or json field
     * @param string $jsonPath A valid JSON PATH Query
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function jsonValue(
        ExpressionInterface|string $expression,
        string $jsonPath,
        array $types = [],
    ): FunctionExpression {
        $params = $this->toLiteralParam($expression) + [$jsonPath];

        return new FunctionExpression('JSON_VALUE', $params, $types);
    }

    /**
     * Helper method to create arbitrary SQL aggregate function calls.
     *
     * @param string $name The SQL aggregate function name
     * @param array $params Array of arguments to be passed to the function.
     *     Can be an associative array with the literal value or identifier:
     *     `['value' => 'literal']` or `['value' => 'identifier']
     * @param array $types Array of types that match the names used in `$params`:
     *     `['name' => 'type']`
     * @param string $return Return type of the entire expression. Defaults to float.
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function aggregate(
        string $name,
        array $params = [],
        array $types = [],
        string $return = 'float',
    ): AggregateExpression {
        return new AggregateExpression($name, $params, $types, $return);
    }

    /**
     * Magic method dispatcher to create custom SQL function calls
     *
     * @param string $name the SQL function name to construct
     * @param array $args list with up to 3 arguments, first one being an array with
     * parameters for the SQL function, the second one a list of types to bind to those
     * params, and the third one the return type of the function
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function __call(string $name, array $args): FunctionExpression
    {
        return new FunctionExpression($name, ...$args);
    }

    /**
     * Creates function parameter array from expression or string literal.
     *
     * @param \Cake\Database\ExpressionInterface|string $expression function argument
     * @return array<\Cake\Database\ExpressionInterface|string>
     */
    protected function toLiteralParam(ExpressionInterface|string $expression): array
    {
        if (is_string($expression)) {
            return [$expression => 'literal'];
        }

        return [$expression];
    }
}
