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
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Check a URL string against the routes.';
    }

    /**
     * Display all routes in an application
     *
     * @return int|null The exit code or null for success
     * @throws \JsonException
     */
    public function execute(): ?int
    {
        $url = $this->args->getArgument('url');
        try {
            $parsed = Router::parseRequest(new ServerRequest(['url' => $url]));
            $name = $parsed['_name'] ?? $parsed['_route']->getName();

            unset($parsed['_route'], $parsed['_matchedRoute']);
            ksort($parsed);

            $output = [
                ['Route name', 'URI template', 'Defaults'],
                [$name, $url, json_encode($parsed, JSON_THROW_ON_ERROR)],
            ];
            $this->io->helper('table')->output($output);
            $this->io->out();
        } catch (RedirectException $e) {
            $output = [
                ['URI template', 'Redirect'],
                [$url, $e->getMessage()],
            ];
            $this->io->helper('table')->output($output);
            $this->io->out();
        } catch (MissingRouteException) {
            $this->io->warning("'{$url}' did not match any routes.");
            $this->io->out();

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
        $parser->setDescription([
            static::getDescription(),
            'Will output the routing parameters the route resolves to.',
        ])
        ->addArgument('url', [
            'help' => 'The URL to check.',
            'required' => true,
        ]);

        return $parser;
    }
}
