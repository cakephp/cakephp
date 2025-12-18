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
namespace Cake\Console;

/**
 * Marker interface for commands that should be hidden from the help output
 * and shell completion while remaining fully executable.
 *
 * ### Example
 *
 * ```php
 * use Cake\Command\Command;
 * use Cake\Console\CommandHiddenInterface;
 *
 * class InternalMaintenanceCommand extends Command implements CommandHiddenInterface
 * {
 *     // Command implementation
 * }
 * ```
 */
interface CommandHiddenInterface
{
}
