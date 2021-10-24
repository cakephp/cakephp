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
    public function sum($expression, $types = []): AggregateExpression
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
    public function avg($expression, $types = []): AggregateExpression
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
    public function max($expression, $types = []): AggregateExpression
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
    public function min($expression, $types = []): AggregateExpression
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
    public function count($expression, $types = []): AggregateExpression
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
     * @param string $type The SQL data type
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function cast($field, string $type = ''): FunctionExpression
    {
        if (is_array($field)) {
            deprecationWarning(
                'Build cast function by FunctionsBuilder::cast(array $args) is deprecated. ' .
                'Use FunctionsBuilder::cast($field, string $type) instead.'
            );

            return new FunctionExpression('CAST', $field);
        }

        if (empty($type)) {
            throw new InvalidArgumentException('The `$type` in a cast cannot be empty.');
        }

        $expression = new FunctionExpression('CAST', $this->toLiteralParam($field));
        $expression->setConjunction(' AS')->add([$type => 'literal']);

        return $expression;
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
    public function datePart(string $part, $expression, array $types = []): FunctionExpression
    {
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
    public function extract(string $part, $expression, array $types = []): FunctionExpression
    {
        $expression = new FunctionExpression('EXTRACT', $this->toLiteralParam($expression), $types, 'integer');
        $expression->setConjunction(' FROM')->add([$part => 'literal'], [], true);

        return $expression;
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
    public function dateAdd($expression, $value, string $unit, array $types = []): FunctionExpression
    {
        if (!is_numeric($value)) {
            $value = 0;
        }
        $interval = $value . ' ' . $unit;
        $expression = new FunctionExpression('DATE_ADD', $this->toLiteralParam($expression), $types, 'datetime');
        $expression->setConjunction(', INTERVAL')->add([$interval => 'literal']);

        return $expression;
    }

    /**
     * Returns a FunctionExpression representing a call to SQL WEEKDAY function.
     * 1 - Sunday, 2 - Monday, 3 - Tuesday...
     *
     * @param \Cake\Database\ExpressionInterface|string $expression the function argument
     * @param array $types list of types to bind to the arguments
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function dayOfWeek($expression, $types = []): FunctionExpression
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
    public function weekday($expression, $types = []): FunctionExpression
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
        if ($type === 'datetime') {
            return new FunctionExpression('NOW', [], [], 'datetime');
        }
        if ($type === 'date') {
            return new FunctionExpression('CURRENT_DATE', [], [], 'date');
        }
        if ($type === 'time') {
            return new FunctionExpression('CURRENT_TIME', [], [], 'time');
        }

        throw new InvalidArgumentException('Invalid argument for FunctionsBuilder::now(): ' . $type);
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
     * @param string $type The output type of the lag expression. Defaults to float.
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function lag($expression, int $offset, $default = null, $type = null): AggregateExpression
    {
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
     * @param string $type The output type of the lead expression. Defaults to float.
     * @return \Cake\Database\Expression\AggregateExpression
     */
    public function lead($expression, int $offset, $default = null, $type = null): AggregateExpression
    {
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
    public function aggregate(string $name, array $params = [], array $types = [], string $return = 'float')
    {
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
    protected function toLiteralParam($expression)
    {
        if (is_string($expression)) {
            return [$expression => 'literal'];
        }

        return [$expression];
    }
}
