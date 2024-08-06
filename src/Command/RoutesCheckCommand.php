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
use Cake\Http\Exception\RedirectException;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;

/**
 * Provides interactive CLI tool for testing routes.
 */
class RoutesCheckCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'routes check';
    }

    /**
     * Display all routes in an application
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     * @throws \JsonException
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $url = $args->getArgument('url');
        try {
            $request = new ServerRequest(['url' => $url]);
            $route = Router::parseRequest($request);
            $name = null;
            foreach (Router::routes() as $r) {
                if ($r->match($route)) {
                    $name = $r->options['_name'] ?? $r->getName();
                    break;
                }
            }

            unset($route['_route'], $route['_matchedRoute']);
            ksort($route);

            $output = [
                ['Route name', 'URI template', 'Defaults'],
                [$name, $url, json_encode($route, JSON_THROW_ON_ERROR)],
            ];
            $io->helper('table')->output($output);
            $io->out();
        } catch (RedirectException $e) {
            $output = [
                ['URI template', 'Redirect'],
                [$url, $e->getMessage()],
            ];
            $io->helper('table')->output($output);
            $io->out();
        } catch (MissingRouteException) {
            $io->warning("'{$url}' did not match any routes.");
            $io->out();

            return static::CODE_ERROR;
        }

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
        $parser->setDescription(
            'Check a URL string against the routes. ' .
            'Will output the routing parameters the route resolves to.'
        )
        ->addArgument('url', [
            'help' => 'The URL to check.',
            'required' => true,
        ]);

        return $parser;
    }
}
