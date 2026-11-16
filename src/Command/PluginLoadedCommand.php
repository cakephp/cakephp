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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Attribute\AsCommand;
use Cake\Core\Plugin;

/**
 * Displays all currently loaded plugins.
 */
#[AsCommand('plugin loaded', description: 'Displays all currently loaded plugins.')]
class PluginLoadedCommand extends Command
{
    /**
     * Displays all currently loaded plugins.
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $loaded = Plugin::loaded();
        $this->io->out($loaded);

        return static::CODE_SUCCESS;
    }
}
