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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Cache\Cache;
use Cake\Console\Attribute\AsCommand;

/**
 * CacheClearall command.
 */
#[AsCommand('cache clear_all', description: 'Clear all data in all configured cache engines.')]
class CacheClearallCommand extends Command
{
    /**
     * Implement this method with your command's logic.
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        foreach (Cache::configured() as $engine) {
            $this->executeCommand(CacheClearCommand::class, [$engine]);
        }

        return static::CODE_SUCCESS;
    }
}
