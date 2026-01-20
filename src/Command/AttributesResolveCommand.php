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

use Cake\Attribute\Resolver;
use Cake\Console\ConsoleOptionParser;

/**
 * Command to resolve and cache attributes.
 */
class AttributesResolveCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'attributes resolve';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Resolve attributes and manage attribute cache.';
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
            ->addOption('no-clear', [
                'boolean' => true,
                'default' => false,
                'help' => 'Skip clearing cache before resolving.',
            ])
            ->addOption('clear-only', [
                'boolean' => true,
                'default' => false,
                'help' => 'Only clear cache without resolving.',
            ])
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

        if (!Resolver::getConfig($configName)) {
            $this->io->error(sprintf('Configuration "%s" does not exist.', $configName));

            return static::CODE_ERROR;
        }

        $config = Resolver::getConfig($configName);
        $cacheConfig = $config['cache'] ?? null;

        // Check if cache is enabled and warn if not
        if ($cacheConfig === false) {
            $this->io->warning('Cache is disabled. Attributes will be re-discovered on every request.');
        }

        // Clear cache by default unless --no-clear is set
        if (!$this->args->getOption('no-clear')) {
            $this->io->out('<info>Clearing attribute cache...</info>');
            Resolver::clear($configName);
        }

        // Only resolve if --clear-only is not set
        if (!$this->args->getOption('clear-only')) {
            $this->io->out('<info>Resolving attributes...</info>');

            $startTime = microtime(true);
            $collection = Resolver::collection($configName);
            $elapsed = round(microtime(true) - $startTime, 3);

            $count = $collection->count();
            $this->io->success(sprintf(
                'Resolved %d attributes in %ss',
                $count,
                $elapsed,
            ));
        }

        return static::CODE_SUCCESS;
    }
}
