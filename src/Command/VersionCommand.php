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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;

/**
 * Print out the version of CakePHP in use.
 */
class VersionCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Show the CakePHP version.';
    }

    /**
     * Print out the version of CakePHP in use.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $version = Configure::version();
        $io->out($version);

        if ($args->getOption('verbose')) {
            $this->outputVerbose($io, $version);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Output verbose version information.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param string $version The CakePHP version
     * @return void
     */
    protected function outputVerbose(ConsoleIo $io, string $version): void
    {
        $io->out();

        // Show release link for stable and RC versions, but not dev
        if (!str_contains($version, '-dev')) {
            $io->out(sprintf(
                '<info>Release:</info> https://github.com/cakephp/cakephp/releases/tag/%s',
                $version,
            ));
        }

        $io->out(sprintf('<info>PHP:</info> %s (%s)', PHP_VERSION, PHP_SAPI));
    }
}
