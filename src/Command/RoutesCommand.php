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

use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use Cake\Routing\Router;

/**
 * Provides interactive CLI tools for routing.
 */
#[AsCommand('routes', description: 'Get the list of routes connected in this application.')]
#[Option('sort', short: 's', help: 'Sorts alphabetically by route name A-Z', boolean: true)]
#[Option('with-middlewares', short: 'm', help: 'Show route specific middlewares', boolean: true)]
class RoutesCommand extends Command
{
    /**
     * Display all routes in an application
     *
     * @return int|null The exit code or null for success
     * @throws \JsonException
     */
    public function execute(): ?int
    {
        $header = ['Route name', 'URI template', 'Plugin', 'Prefix', 'Controller', 'Action', 'Method(s)'];
        if ($this->args->getOption('with-middlewares') || $this->args->getOption('verbose')) {
            $header[] = 'Middlewares';
        }
        if ($this->args->getOption('verbose')) {
            $header[] = 'Defaults';
        }

        $availableRoutes = Router::routes();
        $output = [];
        /** @var array<string, array<string, int>> $duplicateRoutesCounter */
        $duplicateRoutesCounter = [];

        foreach ($availableRoutes as $route) {
            /** @var array<string> $methods */
            $methods = isset($route->defaults['_method']) ? (array)$route->defaults['_method'] : [''];

            $item = [
                $route->options['_name'] ?? $route->getName(),
                $route->template,
                $route->defaults['plugin'] ?? '',
                $route->defaults['prefix'] ?? '',
                $route->defaults['controller'] ?? '',
                $route->defaults['action'] ?? '',
                implode(', ', $methods),
            ];

            if ($this->args->getOption('with-middlewares') || $this->args->getOption('verbose')) {
                $item[] = implode(', ', $route->getMiddleware());
            }
            if ($this->args->getOption('verbose')) {
                ksort($route->defaults);
                $item[] = json_encode($route->defaults, JSON_THROW_ON_ERROR);
            }

            $output[] = $item;

            foreach ($methods as $method) {
                $duplicateRoutesCounter[$route->template][$method] ??= 0;
                $duplicateRoutesCounter[$route->template][$method]++;
            }
        }

        if ($this->args->getOption('sort')) {
            usort($output, function (array $a, array $b) {
                return strcasecmp($a[0], $b[0]);
            });
        }

        array_unshift($output, $header);

        $this->io->helper('table')->output($output);
        $this->io->out();

        $duplicateRoutes = [];

        foreach ($availableRoutes as $route) {
            /** @var array<string> $methods */
            $methods = isset($route->defaults['_method']) ? (array)$route->defaults['_method'] : [''];

            foreach ($methods as $method) {
                if (
                    $duplicateRoutesCounter[$route->template][$method] > 1 ||
                    ($method === '' && count($duplicateRoutesCounter[$route->template]) > 1) ||
                    ($method !== '' && isset($duplicateRoutesCounter[$route->template]['']))
                ) {
                    $duplicateRoutes[] = [
                        $route->options['_name'] ?? $route->getName(),
                        $route->template,
                        $route->defaults['plugin'] ?? '',
                        $route->defaults['prefix'] ?? '',
                        $route->defaults['controller'] ?? '',
                        $route->defaults['action'] ?? '',
                        implode(', ', $methods),
                    ];

                    break;
                }
            }
        }

        if ($duplicateRoutes) {
            array_unshift($duplicateRoutes, $header);
            $this->io->warning('The following possible route collisions were detected.');
            $this->io->helper('table')->output($duplicateRoutes);
            $this->io->out();
        }

        return static::CODE_SUCCESS;
    }
}
