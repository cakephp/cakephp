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
     * Camelizes the previously dashed plugin route taking into account plugin vendors
     *
     * @param string $plugin Plugin name
     * @return string
     */
    protected function _camelizePlugin(string $plugin): string
    {
        $plugin = str_replace('-', '_', $plugin);
        if (!str_contains($plugin, '/')) {
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
     * @inheritDoc
     */
    protected function _writeUrl(array $params, array $pass = [], array $query = []): string
    {
        return parent::_writeUrl($this->_dasherize($params), $pass, $query);
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
