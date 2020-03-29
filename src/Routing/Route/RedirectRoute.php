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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Route;

use Cake\Http\Exception\RedirectException;
use Cake\Routing\Router;

/**
 * Redirect route will perform an immediate redirect. Redirect routes
 * are useful when you want to have Routing layer redirects occur in your
 * application, for when URLs move.
 *
 * Redirection is signalled by an exception that halts route matching and
 * defines the redirect URL and status code.
 */
class RedirectRoute extends Route
{
    /**
     * The location to redirect to.
     *
     * @var array
     */
    public $redirect;

    /**
     * Constructor
     *
     * @param string $template Template string with parameter placeholders
     * @param array $defaults Defaults for the route. Either a redirect=>value array or a CakePHP array URL.
     * @param array $options Array of additional options for the Route
     */
    public function __construct(string $template, array $defaults = [], array $options = [])
    {
        parent::__construct($template, $defaults, $options);
        if (isset($defaults['redirect'])) {
            $defaults = (array)$defaults['redirect'];
        }
        $this->redirect = $defaults;
    }

    /**
     * Parses a string URL into an array. Parsed URLs will result in an automatic
     * redirection.
     *
     * @param string $url The URL to parse.
     * @param string $method The HTTP method being used.
     * @return array|null Null on failure. An exception is raised on a successful match. Array return type is unused.
     * @throws \Cake\Http\Exception\RedirectException An exception is raised on successful match.
     *   This is used to halt route matching and signal to the middleware that a redirect should happen.
     */
    public function parse(string $url, string $method = ''): ?array
    {
        $params = parent::parse($url, $method);
        if (!$params) {
            return null;
        }
        $redirect = $this->redirect;
        if ($this->redirect && count($this->redirect) === 1 && !isset($this->redirect['controller'])) {
            $redirect = $this->redirect[0];
        }
        if (isset($this->options['persist']) && is_array($redirect)) {
            $redirect += ['pass' => $params['pass'], 'url' => []];
            if (is_array($this->options['persist'])) {
                foreach ($this->options['persist'] as $elem) {
                    if (isset($params[$elem])) {
                        $redirect[$elem] = $params[$elem];
                    }
                }
            }
            $redirect = Router::reverseToArray($redirect);
        }
        $status = 301;
        if (isset($this->options['status']) && ($this->options['status'] >= 300 && $this->options['status'] < 400)) {
            $status = $this->options['status'];
        }
        throw new RedirectException(Router::url($redirect, true), $status);
    }

    /**
     * There is no reverse routing redirection routes.
     *
     * @param array $url Array of parameters to convert to a string.
     * @param array $context Array of request context parameters.
     * @return string|null Always null, string return result unused.
     */
    public function match(array $url, array $context = []): ?string
    {
        return null;
    }

    /**
     * Sets the HTTP status
     *
     * @param int $status The status code for this route
     * @return $this
     */
    public function setStatus(int $status)
    {
        $this->options['status'] = $status;

        return $this;
    }
}
