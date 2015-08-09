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
namespace Cake\View;

use Cake\Core\App;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\View\View;
use Cake\View\Exception\MissingViewException;


/**
 * Provides an API for iteratively building a view up.
 *
 * Once you have configured the view and established all the context
 * you can create a view instance with `build()`.
 */
class ViewBuilder
{
    /**
     * The subdirectory to the view.
     *
     * @var string
     */
    protected $viewPath;

    /**
     * The view file to render.
     *
     * @var string
     */
    protected $view;

    /**
     * The plugin name to use.
     *
     * @var string
     */
    protected $plugin;

    /**
     * The theme name to use.
     *
     * @var string
     */
    protected $theme;

    /**
     * The layout name to render.
     *
     * @var string
     */
    protected $layout;

    /**
     * Whether or not autoLayout should be enabled.
     *
     * @var bool
     */
    protected $autoLayout;

    /**
     * The layout path to build the view with.
     *
     * @var string
     */
    protected $layoutPath;

    /**
     * The view variables to use
     *
     * @var string
     */
    protected $name;

    /**
     * The view variables to use
     *
     * @var string
     */
    protected $viewClass;

    /**
     * The view variables to use
     *
     * @var array
     */
    protected $options = [];

    /**
     * Get/set path for view files.
     *
     * @param string $path Path for view files. If null returns current path.
     * @return string|$this
     */
    public function viewPath($path = null)
    {
        if ($path === null) {
            return $this->viewPath;
        }

        $this->viewPath = $path;
        return $this;
    }

    /**
     * Get/set path for layout files.
     *
     * @param string $path Path for layout files. If null returns current path.
     * @return string|void
     */
    public function layoutPath($path = null)
    {
        if ($path === null) {
            return $this->layoutPath;
        }

        $this->layoutPath = $path;
        return $this;
    }

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered views.
     *
     * @param bool $autoLayout Boolean to turn on/off. If null returns current value.
     * @return bool|$this
     */
    public function autoLayout($autoLayout = null)
    {
        if ($autoLayout === null) {
            return $this->autoLayout;
        }

        $this->autoLayout = $autoLayout;
        return $this;
    }

    /**
     * The plugin name to use
     *
     * @param string $theme Plugin name. If null returns current plugin.
     * @return string|$this
     */
    public function plugin($theme = null)
    {
    }

    /**
     * The helpers to use
     *
     * @param array $helpers Helpers to use.
     * @return array|$this
     */
    public function helpers($helpers = null)
    {
    }

    /**
     * The view theme to use.
     *
     * @param string $theme Theme name. If null returns current theme.
     * @return string|$this
     */
    public function theme($theme = null)
    {
        if ($theme === null) {
            return $this->theme;
        }

        $this->theme = $theme;
        return $this;
    }

    /**
     * Get/set the name of the view file to render. The name specified is the
     * filename in /app/Template/<SubFolder> without the .ctp extension.
     *
     * @param string $name View file name to set. If null returns current name.
     * @return string|$this
     */
    public function view($name = null)
    {
        if ($name === null) {
            return $this->view;
        }

        $this->view = $name;
        return $this;
    }

    /**
     * Get/set the name of the layout file to render the view inside of.
     * The name specified is the filename of the layout in /app/Template/Layout
     * without the .ctp extension.
     *
     * @param string $name Layout file name to set. If null returns current name.
     * @return string|$this
     */
    public function layout($name = null)
    {
        if ($name === null) {
            return $this->layout;
        }

        $this->layout = $name;
        return $this;
    }

    /**
     * Set additional options for the view.
     *
     * @param array|null $options Either an array of options or null to get current options.
     * @return array|$this
     */
    public function options($options = null)
    {
    }

    /**
     * Get/set the view name
     *
     * @return array|$this
     */
    public function name($vars = null)
    {
    }

    /**
     * Get/set the view classname
     *
     * @return array|$this
     */
    public function className($name = null)
    {
    }

    /**
     * Using the data in the builder, create a view instance.
     *
     * @param array $vars The view variables/context to use.
     * @param \Cake\Network\Request $request The request to use.
     * @param \Cake\Network\Response $response The response to use.
     * @param \Cake\Event\EventManager $events The event manager to use.
     * @return \Cake\View\View
     * @throws \Cake\View\Exception\MissingViewException
     */
    public function build($vars = [], Request $request = null, Response $response = null, EventManager $events = null)
    {
    }
}
