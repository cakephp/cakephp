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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use ArrayAccess;

/**
 * URL Object
 *
 * Use this object whenever you need to generate an URL for your app.
 *
 * This is not thought for external URLs.
 */
class Url implements ArrayAccess
{

    /**
     * Array representation of the URL
     *
     * @var array
     */
    protected $url = [];

    /**
     * Absolute URL
     *
     * @var bool
     */
    protected $absolute;

    /**
     * Sets the action
     *
     * @param string $action Controller action name
     * @return $this
     */
    public function setAction($action)
    {
        $this->url['action'] = $action;

        return $this;
    }

    /**
     * Sets the controller
     *
     * @param string $controller Controller class name without the controller suffix
     * @return $this
     */
    public function setController($controller)
    {
        $this->url['controller'] = $controller;

        return $this;
    }

    /**
     * Set prefix
     *
     * @param string $prefix Prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->url['prefix'] = $prefix;

        return $this;
    }

    /**
     * Sets the plugin
     *
     * @param string|null $plugin Plugin name
     * @return $this
     */
    public function setPlugin($plugin)
    {
        $this->url['plugin'] = $plugin;

        return $this;
    }

    /**
     * Sets values to be passed in the URL
     *
     * @param string $pass Args to be passed to a controller action
     * @return $this
     */
    public function setPass($pass)
    {
        $this->url[] = $pass;

        return $this;
    }

    /**
     * Sets a query param
     *
     * @param string $key Query param name
     * @param string|int|float $value Value
     * @return $this
     */
    public function setQuery($key, $value)
    {
        if (!isset($this->url['?'])) {
            $this->url['?'] = [];
        }
        $this->url['?'][$key] = $value;

        return $this;
    }

    /**
     * Sets multiple query params
     *
     * @param array $params Query params as key value list
     * @return $this
     */
    public function setQueryParams(array $params)
    {
        if (!isset($this->url['?'])) {
            $this->url['?'] = [];
        }

        $this->url['?'] = array_merge($this->url['?'], $params);

        return $this;
    }

    /**
     * Absolute URL
     *
     * @param bool $absolute Generate an absolute URL or not
     * @return $this
     */
    public function absolute($absolute = true)
    {
        $this->absolute = (bool)$absolute;

        return $this;
    }

    /**
     * To string
     *
     * @return string;
     */
    public function toString()
    {
        return Router::url($this->url, $this->absolute);
    }

    /**
     * To array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->url;
    }

    /**
     * To string
     *
     * @return string String URL
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->url[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.

     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (isset($this->url[$offset])) {
            return $this->url[$offset];
        }

        return null;
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->url[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($offset);
    }
}
