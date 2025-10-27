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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

use InvalidArgumentException;

/**
 * Check constraint value object
 *
 * Models a check constraint.
 */
class CheckConstraint extends Constraint
{
    protected string $type = self::CHECK;

    /**
     * Constructor
     *
     * @param string $name Constraint name.
     * @param string $expression The check constraint expression (e.g., "age >= 18")
     */
    public function __construct(
        protected string $name,
        protected string $expression,
    ) {
    }

    /**
     * Set the check constraint expression.
     *
     * @param string $expression The SQL expression for the check constraint
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setExpression(string $expression)
    {
        if (trim($expression) === '') {
            throw new InvalidArgumentException('Check constraint expression cannot be empty');
        }

        $this->expression = trim($expression);

        return $this;
    }

    /**
     * Get the check constraint expression.
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Converts a constraint to an array that is compatible
     * with the constructor.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'expression' => $this->expression,
        ];
    }
}
