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
 * @since         4.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Preload;
use LogicException;

/**
 * Generates a preload file
 *
 * Example Regex Patterns:
 *
 * @example bin/cake preload -r "/^(((?!\/tests\/).)+\.php$)*$/"
 */
class PreloadCommand extends Command
{
    /**
     * Generates a preload file
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $options = [
            'path' => ROOT . DS . $args->getOption('name'),
            'regex' => $args->getOption('regex'),
            'src' => $args->getOption('src'),
        ];

        if (is_string($args->getOption('plugins'))) {
            $options['plugins'] = explode(',', $args->getOption('plugins'));
        }

        $preload = new Preload($options);

        // $files is a flat array of absolute file paths
        $result = $preload->write(function ($files) use ($preload) {
            return $preload->filter($files);
        });

        if ($result) {
            $io->hr();
            $io->success('Preload written to ' . $options['path']);
            $io->hr();
            $this->status($io);

            return static::CODE_SUCCESS;
        }

        $io->err('Error encountered writing to ' . $options['path']);

        return static::CODE_ERROR;
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Generate a preload file')
            ->addOption('name', [
                'help' => 'The file name of the preloader',
                'short' => 'n',
                'default' => 'preload.php',
            ])
            ->addOption('regex', [
                'help' => 'Regex pattern for files to match on',
                'short' => 'r',
                'default' => '/^(((?!\/tests\/).)+\.php$)*$/i',
            ])
            ->addOption('src', [
                'help' => 'Add your applications src directory into preload',
                'short' => 's',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('plugins', [
                'help' => 'Comma separated list of plugins (e.g. MyPlugin,Cake/TwigView) to add to the preload',
                'short' => 'p',
            ]);

        return $parser;
    }

    /**
     * Display opcache statistics if any exist
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function status(ConsoleIo $io): void
    {
        if (!function_exists('opcache_get_status')) {
            throw new LogicException('OPcache must be enabled');
        }

        $status = opcache_get_status();
        if (!is_array($status)) {
            return;
        }

        $io->info('OPcache Status');

        $memory = $status['memory_usage'];
        $stats = $status['opcache_statistics'];
        $wastedMem = $memory['wasted_memory'];

        $io->helper('table')->output([
            ['Item', 'Value'],
            ['Free Memory', round($memory['free_memory'] / 1024 / 1024) . 'MB'],
            ['Used Memory', round($memory['used_memory'] / 1024 / 1024) . 'MB'],
            ['Wasted Memory', round($wastedMem > 0 ? $wastedMem / 1024 / 1024 : 0) . 'MB'],
            ['Scripts', $stats['num_cached_scripts'] ?? ''],
            ['Keys', $stats['num_cached_keys'] ?? ''],
            ['Hits', $stats['hits'] ?? ''],
            ['Misses', $stats['misses'] ?? ''],
        ]);
    }
}
