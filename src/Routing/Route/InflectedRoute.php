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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Route;

use Cake\Utility\Inflector;

/**
 * This route class will transparently inflect the controller and plugin routing
 * parameters, so that requesting `/my_controller` is parsed as `['controller' => 'MyController']`
 */
class InflectedRoute extends Route
{

    /**
     * Flag for tracking whether or not the defaults have been inflected.
     *
     * Default values need to be inflected so that they match the inflections that match()
     * will create.
     *
     * @var bool
     */
    protected $_inflectedDefaults = false;

    /**
     * Parses a string URL into an array. If it matches, it will convert the prefix, controller and
     * plugin keys to their camelized form.
     *
     * @param string $url The URL to parse
     * @return array|false An array of request parameters, or false on failure.
     */
    public function parse($url)
    {
        $params = parent::parse($url);
        if (!$params) {
            return false;
        }
        if (!empty($params['controller'])) {
            $params['controller'] = Inflector::camelize($params['controller']);
        }
        if (!empty($params['plugin'])) {
            if (strpos($params['plugin'], '/') === false) {
                $params['plugin'] = Inflector::camelize($params['plugin']);
            } else {
                list($vendor, $plugin) = explode('/', $params['plugin'], 2);
                $params['plugin'] = Inflector::camelize($vendor) . '/' . Inflector::camelize($plugin);
            }
        }

        return $params;
    }

    /**
     * Underscores the prefix, controller and plugin params before passing them on to the
     * parent class
     *
     * @param array $url Array of parameters to convert to a string.
     * @param array $context An array of the current request context.
     *   Contains information such as the current host, scheme, port, and base
     *   directory.
     * @return string|false Either a string URL for the parameters if they match or false.
     */
    public function match(array $url, array $context = [])
    {
        $url = $this->_underscore($url);
        if (!$this->_inflectedDefaults) {
            $this->_inflectedDefaults = true;
            $this->defaults = $this->_underscore($this->defaults);
        }

        return parent::match($url, $context);
    }

    /**
     * Helper method for underscoring keys in a URL array.
     *
     * @param array $url An array of URL keys.
     * @return array
     */
    protected function _underscore($url)
    {
        if (!empty($url['controller'])) {
            $url['controller'] = Inflector::underscore($url['controller']);
        }
        if (!empty($url['plugin'])) {
            $url['plugin'] = Inflector::underscore($url['plugin']);
        }

        return $url;
    }
}
