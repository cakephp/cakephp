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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\TypeFactory;

/**
 * Offers a method to convert values to ExpressionInterface objects
 * if the type they should be converted to implements ExpressionTypeInterface
 */
trait ExpressionTypeCasterTrait
{
    /**
     * Conditionally converts the passed value to an ExpressionInterface object
     * if the type class implements the ExpressionTypeInterface. Otherwise,
     * returns the value unmodified.
     *
     * @param mixed $value The value to convert to ExpressionInterface
     * @param string|null $type The type name
     * @return mixed
     */
    protected function _castToExpression($value, ?string $type = null)
    {
        if ($type === null) {
            return $value;
        }

        $baseType = str_replace('[]', '', $type);
        $converter = TypeFactory::build($baseType);

        if (!$converter instanceof ExpressionTypeInterface) {
            return $value;
        }

        $multi = $type !== $baseType;

        if ($multi) {
            /** @psalm-var \Cake\Database\Type\ExpressionTypeInterface $converter */
            return array_map([$converter, 'toExpression'], $value);
        }

        return $converter->toExpression($value);
    }

    /**
     * Returns an array with the types that require values to
     * be casted to expressions, out of the list of type names
     * passed as parameter.
     *
     * @param array $types List of type names
     * @return array
     */
    protected function _requiresToExpressionCasting(array $types): array
    {
        $result = [];
        $types = array_filter($types);
        foreach ($types as $k => $type) {
            $object = TypeFactory::build($type);
            if ($object instanceof ExpressionTypeInterface) {
                $result[$k] = $object;
            }
        }

        return $result;
    }
}
