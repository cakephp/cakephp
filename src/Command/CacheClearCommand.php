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
use Cake\Cache\Engine\ApcuEngine;
use Cake\Cache\Engine\WincacheEngine;
use Cake\Cache\InvalidArgumentException;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * CacheClear command.
 */
class CacheClearCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'cache clear';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser
            ->setDescription('Clear all data in a single cache engine')
            ->addArgument('engine', [
                'help' => 'The cache engine to clear.' .
                    'For example, `cake cache clear _cake_model_` will clear the model cache' .
                    'Use `cake cache list_engines` to list available engines',
                'required' => true,
            ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $name = (string)$args->getArgument('engine');
        try {
            $io->out("Clearing {$name}");

            $engine = Cache::pool($name);
            Cache::clear($name);
            if ($engine instanceof ApcuEngine) {
                $io->warning("ApcuEngine detected: Cleared {$name} CLI cache successfully " .
                    "but {$name} web cache must be cleared separately.");
            } elseif ($engine instanceof WincacheEngine) {
                $io->warning("WincacheEngine detected: Cleared {$name} CLI cache successfully " .
                    "but {$name} web cache must be cleared separately.");
            } else {
                $io->out("<success>Cleared {$name} cache</success>");
            }
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            $this->abort();
        }

        return static::CODE_SUCCESS;
    }
}
