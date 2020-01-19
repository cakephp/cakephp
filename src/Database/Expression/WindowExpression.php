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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;

/**
 * This represents a SQL window expression used by aggregate and window functions.
 */
class WindowExpression implements ExpressionInterface
{
    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        return 'OVER ()';
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $visitor)
    {
        return $this;
    }
}
