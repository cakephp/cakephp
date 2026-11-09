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
namespace TestApp\Console\Attribute;

use Cake\Command\Command;
use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;

/**
 * Abstract declarations contribute parser settings without being registered.
 */
#[AsCommand('abstract export')]
#[Argument('source', required: true)]
#[Option('mode', short: 'm', default: 'old')]
abstract class AbstractExport extends Command
{
}
