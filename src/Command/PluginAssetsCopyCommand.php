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
use Cake\Console\Attribute\Option;

/**
 * Command for copying plugin assets to app's webroot.
 */
#[AsCommand('plugin assets copy', description: "Copy plugin assets to app's webroot.")]
#[Argument('name', help: 'A specific plugin you want to copy assets for.', required: false)]
#[Option('overwrite', help: 'Overwrite existing symlink / folder / files.', boolean: true, default: false)]
class PluginAssetsCopyCommand extends Command
{
    use PluginAssetsTrait;

    /**
     * Execute the command
     *
     * Copying plugin assets to app's webroot. For vendor namespaced plugin,
     * parent folder for vendor name are created if required.
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $name = $this->args->getArgument('name');
        $overwrite = (bool)$this->args->getOption('overwrite');
        $this->process($this->list($name), true, $overwrite);

        return static::CODE_SUCCESS;
    }
}
