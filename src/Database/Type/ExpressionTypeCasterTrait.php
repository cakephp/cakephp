<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\Type;
use Cake\Database\Type\ExpressionTypeInterface;

/**
 * Offers a method to convert values to ExpressionInterface objects
 * if the type they should be converted to implements ExpressionTypeInterface
 *
 */
trait ExpressionTypeCasterTrait
{

    /**
     * Conditionally converts the passed value to an ExpressionInterface object
     * if the type class implementes the ExpressionTypeInterface. Otherwise,
     * returns the value unmodified.
     *
     * @param mixed $value The value to converto to ExpressionInterface
     * @param string $type The type name
     * @return mixed
     */
    protected function _castToExpression($value, $type)
    {
        if (empty($type)) {
            return $value;
        }

        $baseType = str_replace('[]', '', $type);
        $converter = Type::build($baseType);

        if (!$converter instanceof ExpressionTypeInterface) {
            return $value;
        }

        $multi = $type !== $baseType;

        if ($multi) {
            return array_map([$converter, 'toExpression'], $value);
        }

        return $converter->toExpression($value);
    }
}
