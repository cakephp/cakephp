<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;

/**
 * Provides interactive CLI tools for routing.
 *
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
        $output = [];
        foreach (Router::routes() as $route) {
            $output[] = [$route->getName(), $route->template, json_encode($route->defaults)];
        }

        $this->_outWithColumns($output);
    }

    /**
     * Checks a url for the route that will be applied.
     *
     * @param string $url The URL to parse
     * @return null|false
     */
    public function check($url)
    {
        try {
            $route = Router::parse($url);
            $this->_outWithColumns(['', $url, json_encode($route)]);
        } catch (MissingRouteException $e) {
            $this->err("<warning>'$url' did not match any routes.</warning>");
            return false;
        }
    }

    /**
     * Generate a URL based on a set of parameters
     *
     * Takes variadic arguments of key/value pairs.
     * @return null|false
     */
    public function generate()
    {
        try {
            $args = $this->_splitArgs($this->args);
            $url = Router::url($args);
            $this->out("> $url");
        } catch (MissingRouteException $e) {
            $this->err("<warning>The provided parameters do not match any routes.</warning>");
            return false;
        }
    }

    /**
     * Get the option parser.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->description(
            'Get the list of routes connected in this application. ' .
            'This tool also lets you test URL generation and URL parsing.'
        )->addSubcommand('check', [
            'help' => 'Check a URL string against the routes. ' .
                'Will output the routing parameters the route resolves to.'
        ])->addSubcommand('generate', [
            'help' => 'Check a routing array agains the routes. ' .
                "Will output the URL if there is a match.\n\n" .
                "Routing parameters should be supplied in a key:value format. " .
                "For example `controller:Articles action:view 2`"
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
                $out[$key] = $value;
            } else {
                $out[] = $arg;
            }
        }
        return $out;
    }

    /**
     * Takes an array to represent rows, of arrays to represent columns.
     * Will pad strings to the maximum character length of each column.
     *
     * @param array $rows The rows to print
     * @return void
     */
    protected function _outWithColumns($rows)
    {
        if (!is_array($rows[0])) {
            $rows = [$rows];
        }
        $maxCharacterLength = [];
        array_unshift($rows, ['Route name', 'URI template', 'Defaults']);

        foreach ($rows as $line) {
            for ($i = 0, $len = count($line); $i < $len; $i++) {
                $elementLength = strlen($line[$i]);
                if ($elementLength > (isset($maxCharacterLength[$i]) ? $maxCharacterLength[$i] : 0)) {
                    $maxCharacterLength[$i] = $elementLength;
                }
            }
        }

        foreach ($rows as $line) {
            for ($i = 0, $len = count($line); $i < $len; $i++) {
                $line[$i] = str_pad($line[$i], $maxCharacterLength[$i], " ", STR_PAD_RIGHT);
            }
            $this->out(implode('    ', $line));
        }

        $this->out();
    }
}
