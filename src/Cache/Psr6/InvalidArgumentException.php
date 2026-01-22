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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Psr6;

use InvalidArgumentException as BaseInvalidArgumentException;
use Psr\Cache\InvalidArgumentException as Psr6InvalidArgumentException;

/**
 * PSR-6 InvalidArgumentException.
 *
 * Thrown when an invalid cache key is provided.
 */
class InvalidArgumentException extends BaseInvalidArgumentException implements Psr6InvalidArgumentException
{
}
