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

interface WhenThenExpressionInterface extends ExpressionInterface
{
    /**
     * Returns the available data for the given clause.
     *
     * ### Available clauses
     *
     * The following clause names are available:
     *
     * * `when` (`\Cake\Database\ExpressionInterface|object|scalar|null`): The `WHEN` value.
     * * `then` (`\Cake\Database\ExpressionInterface|object|scalar|null`): The `THEN` result value.
     *
     * @param string $clause The name of the clause to obtain.
     * @return \Cake\Database\ExpressionInterface|object|scalar|null
     * @throws \InvalidArgumentException In case the given clause name is invalid.
     */
    public function clause(string $clause);

    /**
     * Sets the `WHEN` value.
     *
     * @param \Cake\Database\ExpressionInterface|array|object|scalar $when The `WHEN` value. When using an array of
     *  conditions, it must be compatible with `\Cake\Database\Query::where()`. Note that this argument is _not_
     *  completely safe for use with user data, as a user supplied array would allow for raw SQL to slip in! If you
     *  plan to use user data, either pass a single type for the `$type` argument (which forces the `$when` value to be
     *  a non-array, and then always binds the data), use a conditions array where the user data is only passed on the
     *  value side of the array entries, or custom bindings!
     * @param array|string|null $type The when value type. Either an associative array when using array style
     *  conditions, or else a string. If no type is provided, the type will be tried to be inferred from the value.
     * @return $this
     * @throws \InvalidArgumentException In case the `$when` argument is neither a non-empty array, nor a scalar value,
     *  an object, or an instance of `\Cake\Database\ExpressionInterface`.
     * @throws \InvalidArgumentException In case the `$type` argument is neither an array, a string, nor null.
     * @throws \InvalidArgumentException In case the `$when` argument is an array, and the `$type` argument is neither
     * an array, nor null.
     * @throws \InvalidArgumentException In case the `$when` argument is a non-array value, and the `$type` argument is
     * neither a string, nor null.
     * @see CaseExpressionInterface::when() for a more detailed usage explanation.
     */
    public function when($when, $type = null);

    /**
     * Sets the `THEN` result value.
     *
     * @param \Cake\Database\ExpressionInterface|object|scalar|null $result The result value.
     * @param string|null $type The result type. If no type is provided, the type will be inferred from the given
     *  result value.
     * @return $this
     */
    public function then($result, ?string $type = null);

    /**
     * Returns the expression's result value type.
     *
     * @return string|null
     * @see then()
     */
    public function getResultType(): ?string;
}
