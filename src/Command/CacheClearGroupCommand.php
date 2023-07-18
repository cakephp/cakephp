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
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Cache Clear Group command.
 */
class CacheClearGroupCommand extends Command
{
    /**
     * Get the command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'cache clear_group';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/option-parsers.html
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Clear all data in a single cache group.');
        $parser->addArgument('group', [
            'help' => 'The cache group to clear. For example, `cake cache clear_group mygroup` will clear ' .
                'all caches belonging to group "mygroup".',
            'required' => true,
        ]);
        $parser->addArgument('config', [
            'help' => 'Name of the configuration to use. Defaults to "default".',
        ]);

        return $parser;
    }

    /**
     * Clears the cache group
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $group = (string)$args->getArgument('group');
        try {
            Cache::groupConfigs($group);
        } catch (InvalidArgumentException $e) {
            $io->error(sprintf('Cache group "%s" not found', $group));

            return static::CODE_ERROR;
        }

        $config = $args->getArgument('config');
        if ($config !== null && Cache::getConfig($config) === null) {
            $io->error(sprintf('Cache config "%s" not found', $config));

            return static::CODE_ERROR;
        }

        if (!Cache::clearGroup($group, $args->getArgument('config'))) {
            $io->error(sprintf('Error encountered clearing group "%s"', $group));

            return static::CODE_ERROR;
        }

        $io->success(sprintf('Group "%s" was cleared', $group));

        return static::CODE_SUCCESS;
    }
}
