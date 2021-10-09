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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypeMap;

interface CaseExpressionInterface extends ExpressionInterface, TypedResultInterface
{
    /**
     * Returns the available data for the given clause.
     *
     * ### Available clauses
     *
     * The following clause names are available:
     *
     * * `value` (`\Cake\Database\ExpressionInterface|object|scalar|null`): The case value for a
     *   `CASE case_value WHEN ...` expression.
     * * `when (`array<\Cake\Database\Expression\WhenThenExpressionInterface>`)`: An array of self-contained
     *   `WHEN ... THEN ...` expressions.
     * * `else` (`\Cake\Database\ExpressionInterface|object|scalar|null`): The `ELSE` result value.
     *
     * @param string $clause The name of the clause to obtain.
     * @return array<\Cake\Database\Expression\WhenThenExpressionInterface>|\Cake\Database\ExpressionInterface|object|scalar|null
     * @throws \InvalidArgumentException In case the given clause name is invalid.
     */
    public function clause(string $clause);

    /**
     * Sets the value for the case expression.
     *
     * When a value is set, the syntax generated is
     * `CASE case_value WHEN when_value ... END`, where the
     * `when_value`'s are compared against the `case_value`.
     *
     * When no value is set, the syntax generated is
     * `CASE WHEN when_conditions ... END`, where the conditions
     * hold the comparisons.
     *
     * @param \Cake\Database\ExpressionInterface|object|scalar|null $value The case value.
     * @param string|null $valueType The case value type.
     * @return $this
     */
    public function value($value, ?string $valueType = null);

    /**
     * Sets the `WHEN` value for a `WHEN ... THEN ...` expression, or a
     * self-contained expression that holds both the value for `WHEN`
     * and the value for `THEN`.
     *
     * ### Order based syntax
     *
     * When passing a value other than a self-contained
     * `\Cake\Database\Expression\WhenThenExpressionInterface`,
     * instance, the `WHEN ... THEN ...` statement must be closed off with
     * a call to `then()` before invoking `when()` again or `else()`:
     *
     * ```
     * $case
     *     ->value($query->identifier('Table.column'))
     *     ->when(true)
     *     ->then('Yes')
     *     ->when(false)
     *     ->then('No')
     *     ->else('Maybe');
     * ```
     *
     * ### Self-contained expressions
     *
     * When passing an instance of `\Cake\Database\Expression\WhenThenExpressionInterface`,
     * being it directly, or via a callable, then there is no need to close
     * using `then()` on this object, instead the statement will be closed
     * on the `\Cake\Database\Expression\WhenThenExpressionInterface`
     * object using
     * `\Cake\Database\Expression\WhenThenExpressionInterface::then()`.
     *
     * Callables will receive an instance of `\Cake\Database\Expression\WhenThenExpressionInterface`,
     * and must return one, being it the same object, or a custom one:
     *
     * ```
     * $case
     *     ->when(function (\Cake\Database\Expression\WhenThenExpressionInterface $whenThen) {
     *         return $whenThen
     *             ->when(['Table.column' => true])
     *             ->then('Yes');
     *     })
     *     ->when(function (\Cake\Database\Expression\WhenThenExpressionInterface $whenThen) {
     *         return $whenThen
     *             ->when(['Table.column' => false])
     *             ->then('No');
     *     })
     *     ->else('Maybe');
     * ```
     *
     * ### Type handling
     *
     * The types provided via the `$type` argument will be merged with the
     * type map set for this expression. When using callables for `$when`,
     * the `\Cake\Database\Expression\WhenThenExpressionInterface`
     * instance received by the callables will inherit that type map, however
     * the types passed here will _not_ be merged in case of using callables,
     * instead the types must be passed in
     * `\Cake\Database\Expression\WhenThenExpressionInterface::when()`:
     *
     * ```
     * $case
     *     ->when(function (\Cake\Database\Expression\WhenThenExpressionInterface $whenThen) {
     *         return $whenThen
     *             ->when(['unmapped_column' => true], ['unmapped_column' => 'bool'])
     *             ->then('Yes');
     *     })
     *     ->when(function (\Cake\Database\Expression\WhenThenExpressionInterface $whenThen) {
     *         return $whenThen
     *             ->when(['unmapped_column' => false], ['unmapped_column' => 'bool'])
     *             ->then('No');
     *     })
     *     ->else('Maybe');
     * ```
     *
     * ### User data safety
     *
     * When passing user data, be aware that allowing a user defined array
     * to be passed, is a potential SQL injection vulnerability, as it
     * allows for raw SQL to slip in!
     *
     * The following is _unsafe_ usage that must be avoided:
     *
     * ```
     * $case
     *      ->when($userData)
     * ```
     *
     * A safe variant for the above would be to define a single type for
     * the value:
     *
     * ```
     * $case
     *      ->when($userData, 'integer')
     * ```
     *
     * This way an exception would be triggered when an array is passed for
     * the value, thus preventing raw SQL from slipping in, and all other
     * types of values would be forced to be bound as an integer.
     *
     * Another way to safely pass user data is when using a conditions
     * array, and passing user data only on the value side of the array
     * entries, which will cause them to be bound:
     *
     * ```
     * $case
     *      ->when([
     *          'Table.column' => $userData,
     *      ])
     * ```
     *
     * Lastly, data can also be bound manually:
     *
     * ```
     * $query
     *      ->select([
     *          'val' => $query->newExpr()
     *              ->case()
     *              ->when($query->newExpr(':userData'))
     *              ->then(123)
     *      ])
     *      ->bind(':userData', $userData, 'integer')
     * ```
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|object|scalar $when The `WHEN` value. When using an
     *  array of conditions, it must be compatible with `\Cake\Database\Query::where()`. Note that this argument is
     *  _not_ completely safe for use with user data, as a user supplied array would allow for raw SQL to slip in! If
     *  you plan to use user data, either pass a single type for the `$type` argument (which forces the `$when` value to
     *  be a non-array, and then always binds the data), use a conditions array where the user data is only passed on
     *  the value side of the array entries, or custom bindings!
     * @param array|string|null $type The when value type. Either an associative array when using array style
     *  conditions, or else a string. If no type is provided, the type will be tried to be inferred from the value.
     * @return $this
     * @throws \LogicException In case this a closing `then()` call is required before calling this method.
     * @throws \LogicException In case the callable doesn't return an instance of
     *  `\Cake\Database\Expression\WhenThenExpressionInterface`.
     * @see then()
     */
    public function when($when, $type = []);

    /**
     * Sets the `THEN` result value for the last `WHEN ... THEN ...`
     * statement that was opened using `when()`.
     *
     * ### Order based syntax
     *
     * This method can only be invoked in case `when()` was previously
     * used with a value other than a closure or an instance of
     * `\Cake\Database\Expression\WhenThenExpressionInterface`:
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->then('Yes')
     *     ->when(['Table.column' => false])
     *     ->then('No')
     *     ->else('Maybe');
     * ```
     *
     * The following would all fail with an exception:
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->when(['Table.column' => false])
     *     // ...
     * ```
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->else('Maybe')
     *     // ...
     * ```
     *
     * ```
     * $case
     *     ->then('Yes')
     *     // ...
     * ```
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->then('Yes')
     *     ->then('No')
     *     // ...
     * ```
     *
     * @param \Cake\Database\ExpressionInterface|object|scalar|null $result The result value.
     * @param string|null $type The result type. If no type is provided, the type will be tried to be inferred from the
     *  value.
     * @return $this
     * @throws \LogicException In case `when()` wasn't previously called with a value other than a closure or an
     *  instance of `\Cake\Database\Expression\WhenThenExpressionInterface`.
     * @see when()
     */
    public function then($result, ?string $type = null);

    /**
     * Sets the `ELSE` result value.
     *
     * @param \Cake\Database\ExpressionInterface|object|scalar|null $result The result value.
     * @param string|null $type The result type. If no type is provided, the type will be tried to be inferred from the
     *  value.
     * @return $this
     * @throws \LogicException In case a closing `then()` call is required before calling this method.
     * @throws \InvalidArgumentException In case the `$result` argument is neither a scalar value, nor an object, an
     *  instance of `\Cake\Database\ExpressionInterface`, or `null`.
     * @see then()
     */
    public function else($result, ?string $type = null);

    /**
     * Returns the abstract type that this expression will return.
     *
     * If no type has been explicitly set via `setReturnType()`, this
     * method will try to obtain the type from the result types of the
     * `then()` and `else() `calls. All types must be identical in order
     * for this to work, otherwise the type will default to `string`.
     *
     * @return string
     * @see setReturnType()
     */
    public function getReturnType(): string;

    /**
     * Sets the abstract type that this expression will return.
     *
     * If no type is being explicitly set via this method, then the
     * `getReturnType()` method will try to infer the type from the
     * result types of the `then()` and `else() `calls.
     *
     * @param string $type The type name to use.
     * @return $this
     * @see getReturnType()
     */
    public function setReturnType(string $type);

    /**
     * Sets the type map to use when using an array of conditions
     * for the `WHEN` value.
     *
     * @param array|\Cake\Database\TypeMap $typeMap Either an array that is used to create a new
     *  `\Cake\Database\TypeMap` instance, or an instance of `\Cake\Database\TypeMap`.
     * @return $this
     */
    public function setTypeMap($typeMap);

    /**
     * Returns the type map.
     *
     * @return \Cake\Database\TypeMap
     */
    public function getTypeMap(): TypeMap;
}
