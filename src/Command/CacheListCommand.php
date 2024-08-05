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
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * CacheList command.
 */
class CacheListCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'cache list';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/option-parsers.html
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Show a list of configured caches.');

        return $parser;
    }

    /**
     * Get the list of cache prefixes
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $engines = Cache::configured();
        foreach ($engines as $engine) {
            $io->out("- {$engine}");
        }

        return static::CODE_SUCCESS;
    }
}
