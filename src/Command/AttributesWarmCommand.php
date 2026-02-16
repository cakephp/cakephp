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

use AttributeResolver\AttributeResolver;
use Cake\Console\ConsoleOptionParser;

/**
 * Command to warm the attribute cache.
 */
class AttributesWarmCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'attributes warm';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Warm the attribute cache by discovering and caching all attributes.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser
            ->setDescription(static::getDescription())
            ->addOption('config', [
                'short' => 'c',
                'default' => 'default',
                'help' => 'The Resolver configuration to use.',
            ]);

        return $parser;
    }

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
