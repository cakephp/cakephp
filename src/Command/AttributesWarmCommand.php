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
namespace Cake\Command;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;

/**
 * Command to warm the attribute cache.
 */
#[AsCommand('attributes warm', description: 'Warm the attribute cache by discovering and caching all attributes.')]
#[Option('config', short: 'c', help: 'The Resolver configuration to use.', default: 'default')]
class AttributesWarmCommand extends Command
{
    /**
     * Implement this method with your command's logic.
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $configName = (string)$this->args->getOption('config');

        $config = AttributeResolver::getConfig($configName);
        if ($config === null) {
            $this->io->error(sprintf('Configuration "%s" does not exist.', $configName));

            return static::CODE_ERROR;
        }
        $cacheConfig = $config['cache'] ?? null;

        // Check if cache is enabled and warn if not
        if ($cacheConfig === false) {
            $this->io->warning('Cache is disabled. Attributes will be re-discovered on every request.');
            $this->io->out('To enable caching, configure a cache in your Resolver configuration.');

            return static::CODE_ERROR;
        }

        // Clear existing cache before warming
        AttributeResolver::clear($configName);

        $this->io->out('<info>Warming attribute cache...</info>');

        $startTime = microtime(true);
        $collection = AttributeResolver::collection($configName);
        $elapsed = round(microtime(true) - $startTime, 3);

        $count = $collection->count();
        $this->io->success(sprintf(
            'Cached %d attributes in %ss',
            $count,
            $elapsed,
        ));

        return static::CODE_SUCCESS;
    }
}
