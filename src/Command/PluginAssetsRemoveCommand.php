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

use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;

/**
 * Command for removing plugin assets from app's webroot.
 */
#[AsCommand('plugin assets remove', description: "Remove plugin assets from app's webroot.")]
#[Argument('name', help: 'A specific plugin you want to remove.', required: false)]
class PluginAssetsRemoveCommand extends Command
{
    use PluginAssetsTrait;

    /**
     * Execute the command
     *
     * Remove plugin assets from app's webroot.
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $name = $this->args->getArgument('name');
        $plugins = $this->list($name);

        foreach ($plugins as $plugin => $config) {
            $this->io->out();
            $this->io->out('For plugin: ' . $plugin);
            $this->io->hr();

            $this->remove($config);
        }

        $this->io->out();
        $this->io->out('Done');

        return static::CODE_SUCCESS;
    }
}
