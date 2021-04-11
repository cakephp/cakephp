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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Routing\Router;

/**
 * Provides interactive CLI tools for routing.
 */
class RoutesCommand extends Command
{
    /**
     * Display all routes in an application
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $header = ['Route name', 'URI template', 'Plugin', 'Prefix', 'Controller', 'Action', 'Method(s)'];
        if ($args->getOption('verbose')) {
            $header[] = 'Defaults';
        }

        $output = [];

        foreach (Router::routes() as $route) {
            $methods = $route->defaults['_method'] ?? '';

            $item = [
                $route->options['_name'] ?? $route->getName(),
                $route->template,
                $route->defaults['plugin'] ?? '',
                $route->defaults['prefix'] ?? '',
                $route->defaults['controller'] ?? '',
                $route->defaults['action'] ?? '',
                is_string($methods) ? $methods : implode(', ', $route->defaults['_method']),
            ];

            if ($args->getOption('verbose')) {
                ksort($route->defaults);
                $item[] = json_encode($route->defaults);
            }

            $output[] = $item;
        }

        if ($args->getOption('sort')) {
            usort($output, function ($a, $b) {
                return strcasecmp($a[0], $b[0]);
            });
        }

        array_unshift($output, $header);

        $io->helper('table')->output($output);
        $io->out();

        return static::CODE_SUCCESS;
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
            ->setDescription('Get the list of routes connected in this application.')
            ->addOption('sort', [
                'help' => 'Sorts alphabetically by route name A-Z',
                'short' => 's',
                'boolean' => true,
            ]);

        return $parser;
    }
}
