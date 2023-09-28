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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Route;

use Cake\Utility\Inflector;

/**
 * This route class will transparently inflect the controller, action and plugin
 * routing parameters, so that requesting `/my-plugin/my-controller/my-action`
 * is parsed as `['plugin' => 'MyPlugin', 'controller' => 'MyController', 'action' => 'myAction']`
 */
class DashedRoute extends Route
{
    /**
     * Flag for tracking whether the defaults have been inflected.
     *
     * Default values need to be inflected so that they match the inflections that
     * match() will create.
     *
     * @var array|null
     */
    protected $_inflectedDefaults;

    /**
     * Camelizes the previously dashed plugin route taking into account plugin vendors
     *
     * @param string $plugin Plugin name
     * @return string
     */
    protected function _camelizePlugin(string $plugin): string
    {
        $plugin = str_replace('-', '_', $plugin);
        if (strpos($plugin, '/') === false) {
            return Inflector::camelize($plugin);
        }
        [$vendor, $plugin] = explode('/', $plugin, 2);

        return Inflector::camelize($vendor) . '/' . Inflector::camelize($plugin);
    }

    /**
     * Parses a string URL into an array. If it matches, it will convert the
     * controller and plugin keys to their CamelCased form and action key to
     * camelBacked form.
     *
     * @param string $url The URL to parse
     * @param string $method The HTTP method.
     * @return array|null An array of request parameters, or null on failure.
     */
    public function parse(string $url, string $method = ''): ?array
    {
        $params = parent::parse($url, $method);
        if (!$params) {
            return null;
        }
        if (!empty($params['controller'])) {
            $params['controller'] = Inflector::camelize($params['controller'], '-');
        }
        if (!empty($params['plugin'])) {
            $params['plugin'] = $this->_camelizePlugin($params['plugin']);
        }
        if (!empty($params['action'])) {
            $params['action'] = Inflector::variable(str_replace(
                '-',
                '_',
                $params['action']
            ));
        }

        return $params;
    }

    /**
     * Dasherizes the controller, action and plugin params before passing them on
     * to the parent class.
     *
     * @param array $url Array of parameters to convert to a string.
     * @param array $context An array of the current request context.
     *   Contains information such as the current host, scheme, port, and base
     *   directory.
     * @return string|null Either a string URL or null.
     */
    public function match(array $url, array $context = []): ?string
    {
        $url = $this->_dasherize($url);
        if ($this->_inflectedDefaults === null) {
            $this->compile();
            $this->_inflectedDefaults = $this->_dasherize($this->defaults);
        }
        $restore = $this->defaults;
        try {
            $this->defaults = $this->_inflectedDefaults;

            return parent::match($url, $context);
        } finally {
            $this->defaults = $restore;
        }
    }

    /**
     * Helper method for dasherizing keys in a URL array.
     *
     * @param array $url An array of URL keys.
     * @return array
     */
    protected function _dasherize(array $url): array
    {
        foreach (['controller', 'plugin', 'action'] as $element) {
            if (!empty($url[$element])) {
                $url[$element] = Inflector::dasherize($url[$element]);
            }
        }

        return $url;
    }
}
