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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Cache\Cache;
use Cake\Cache\Exception\InvalidArgumentException;
use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;

/**
 * Cache Clear Group command.
 */
#[AsCommand('cache clear_group', description: 'Clear all data in a single cache group.')]
#[Argument(
    'group',
    help: 'The cache group to clear. For example, `cake cache clear_group mygroup` will clear ' .
        'all cache items belonging to group "mygroup".',
    required: true,
)]
#[Argument(
    'config',
    help: 'Name of the configuration to use. Defaults to no value which clears all cache configurations.',
)]
class CacheClearGroupCommand extends Command
{
    /**
     * Clears the cache group
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $group = (string)$this->args->getArgument('group');
        try {
            $groupConfigs = Cache::groupConfigs($group);
        } catch (InvalidArgumentException) {
            $this->io->error(sprintf('Cache group "%s" not found', $group));

            return static::CODE_ERROR;
        }

        $config = $this->args->getArgument('config');
        if ($config !== null && Cache::getConfig($config) === null) {
            $this->io->error(sprintf('Cache config "%s" not found', $config));

            return static::CODE_ERROR;
        }

        foreach ($groupConfigs[$group] as $groupConfig) {
            if ($config !== null && $config !== $groupConfig) {
                continue;
            }

            if (!Cache::clearGroup($group, $groupConfig)) {
                $this->io->error(sprintf(
                    'Error encountered clearing group "%s". Was unable to clear entries for "%s".',
                    $group,
                    $groupConfig,
                ));
                $this->abort();
            } else {
                $this->io->success(sprintf('Cache "%s" was cleared.', $groupConfig));
            }
        }

        return static::CODE_SUCCESS;
    }
}
