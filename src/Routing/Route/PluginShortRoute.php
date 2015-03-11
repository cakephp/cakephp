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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Route;

use Cake\Routing\Route\InflectedRoute;

/**
 * Plugin short route, that copies the plugin param to the controller parameters
 * It is used for supporting /:plugin routes.
 *
 */
class PluginShortRoute extends InflectedRoute
{

    /**
     * Parses a string URL into an array. If a plugin key is found, it will be copied to the
     * controller parameter
     *
     * @param string $url The URL to parse
     * @return mixed false on failure, or an array of request parameters
     */
    public function parse($url)
    {
        $params = parent::parse($url);
        if (!$params) {
            return false;
        }
        $params['controller'] = $params['plugin'];
        return $params;
    }

    /**
     * Reverse route plugin shortcut URLs. If the plugin and controller
     * are not the same the match is an auto fail.
     *
     * @param array $url Array of parameters to convert to a string.
     * @param array $context An array of the current request context.
     *   Contains information such as the current host, scheme, port, and base
     *   directory.
     * @return mixed either false or a string URL.
     */
    public function match(array $url, array $context = [])
    {
        if (isset($url['controller']) && isset($url['plugin']) && $url['plugin'] !== $url['controller']) {
            return false;
        }
        $this->defaults['controller'] = $url['controller'];
        $result = parent::match($url, $context);
        unset($this->defaults['controller']);
        return $result;
    }
}
