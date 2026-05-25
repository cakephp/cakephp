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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Exception thrown when an event listener attribute declaration is invalid.
 *
 * This is raised during the attribute-based listener connection phase
 * when a declared listener cannot be resolved (e.g. a referenced method
 * does not exist or is not publicly accessible).
 */
class EventAttributeException extends CakeException
{
}
