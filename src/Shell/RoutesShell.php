<?php
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
namespace Cake\Shell;

use Cake\Console\Shell;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;

/**
 * Provides interactive CLI tools for routing.
 */
class RoutesShell extends Shell
{

    /**
     * Override main() to handle action
     * Displays all routes in an application.
     *
     * @return void
     */
    public function main()
    {
        $output = [
            ['Route name', 'URI template', 'Defaults']
        ];
        foreach (Router::routes() as $route) {
            $name = isset($route->options['_name']) ? $route->options['_name'] : $route->getName();
            ksort($route->defaults);
            $output[] = [$name, $route->template, json_encode($route->defaults)];
        }
        $this->helper('table')->output($output);
        $this->out();
    }

    /**
     * Checks a url for the route that will be applied.
     *
     * @param string $url The URL to parse
     * @return bool Success
     */
    public function check($url)
    {
        try {
            $request = new ServerRequest(['url' => $url]);
            $route = Router::parseRequest($request);
            $name = null;
            foreach (Router::routes() as $r) {
                if ($r->match($route)) {
                    $name = isset($r->options['_name']) ? $r->options['_name'] : $r->getName();
                    break;
                }
            }

            unset($route['_matchedRoute']);
            ksort($route);

            $output = [
                ['Route name', 'URI template', 'Defaults'],
                [$name, $url, json_encode($route)]
            ];
            $this->helper('table')->output($output);
            $this->out();
        } catch (MissingRouteException $e) {
            $this->warn("'$url' did not match any routes.");
            $this->out();

            return false;
        }

        return true;
    }

    /**
     * Generate a URL based on a set of parameters
     *
     * Takes variadic arguments of key/value pairs.
     * @return bool Success
     */
    public function generate()
    {
        try {
            $args = $this->_splitArgs($this->args);
            $url = Router::url($args);
            $this->out("> $url");
            $this->out();
        } catch (MissingRouteException $e) {
            $this->err('<warning>The provided parameters do not match any routes.</warning>');
            $this->out();

            return false;
        }

        return true;
    }

    /**
     * Get the option parser.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->setDescription(
            'Get the list of routes connected in this application. ' .
            'This tool also lets you test URL generation and URL parsing.'
        )->addSubcommand('check', [
            'help' => 'Check a URL string against the routes. ' .
                'Will output the routing parameters the route resolves to.'
        ])->addSubcommand('generate', [
            'help' => 'Check a routing array against the routes. ' .
                "Will output the URL if there is a match.\n\n" .
                'Routing parameters should be supplied in a key:value format. ' .
                'For example `controller:Articles action:view 2`'
        ]);

        return $parser;
    }

    /**
     * Split the CLI arguments into a hash.
     *
     * @param array $args The arguments to split.
     * @return array
     */
    protected function _splitArgs($args)
    {
        $out = [];
        foreach ($args as $arg) {
            if (strpos($arg, ':') !== false) {
                list($key, $value) = explode(':', $arg);
                if (in_array($value, ['true', 'false'])) {
                    $value = $value === 'true';
                }
                $out[$key] = $value;
            } else {
                $out[] = $arg;
            }
        }

        return $out;
    }
}
