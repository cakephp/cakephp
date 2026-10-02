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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Chronos\ChronosDate;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypeMap;
use DateTimeInterface;
use Stringable;

/**
 * Shared database type inference helpers.
 *
 * @internal
 */
trait TypeInferenceTrait
{
    /**
     * Infers the abstract type for the given value.
     *
     * @param mixed $value The value for which to infer the type.
     * @param \Cake\Database\TypeMap|null $typeMap Used to resolve the type of identifier expressions.
     * @return string|null The abstract type, or `null` if it could not be inferred.
     */
    protected function inferType(mixed $value, ?TypeMap $typeMap = null): ?string
    {
        $type = null;

        if (is_string($value)) {
            $type = 'string';
        } elseif (is_int($value)) {
            $type = 'integer';
        } elseif (is_float($value)) {
            $type = 'float';
        } elseif (is_bool($value)) {
            $type = 'boolean';
        } elseif ($value instanceof ChronosDate) {
            $type = 'date';
        } elseif ($value instanceof DateTimeInterface) {
            $type = 'datetime';
        } elseif ($value instanceof Stringable) {
            $type = 'string';
        } elseif (
            $typeMap !== null &&
            $value instanceof IdentifierExpression
        ) {
            $type = $typeMap->type($value->getIdentifier());
        } elseif ($value instanceof TypedResultInterface) {
            $type = $value->getReturnType();
        }

        return $type;
    }
}
