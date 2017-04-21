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
namespace Cake\View;

use BadMethodCallException;
use Cake\Cache\Cache;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingCellViewException;
use Cake\View\Exception\MissingTemplateException;
use Error;
use Exception;
use ReflectionException;
use ReflectionMethod;

/**
 * Cell base.
 */
abstract class Cell
{

    use EventDispatcherTrait;
    use LocatorAwareTrait;
    use ModelAwareTrait;
    use ViewVarsTrait;

    /**
     * Instance of the View created during rendering. Won't be set until after
     * Cell::__toString() is called.
     *
     * @var \Cake\View\View
     * @deprecated 3.1.0 Use createView() instead.
     */
    public $View;

    /**
     * Name of the template that will be rendered.
     * This property is inflected from the action name that was invoked.
     *
     * @var string
     */
    public $template;

    /**
     * Automatically set to the name of a plugin.
     *
     * @var string
     */
    public $plugin;

    /**
     * An instance of a Cake\Http\ServerRequest object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * @var \Cake\Http\ServerRequest
     */
    public $request;

    /**
     * An instance of a Response object that contains information about the impending response
     *
     * @var \Cake\Http\Response
     */
    public $response;

    /**
     * The helpers this cell uses.
     *
     * This property is copied automatically when using the CellTrait
     *
     * @var array
     */
    public $helpers = [];

    /**
     * The cell's action to invoke.
     *
     * @var string
     */
    public $action;

    /**
     * Arguments to pass to cell's action.
     *
     * @var array
     */
    public $args = [];

    /**
     * These properties can be set directly on Cell and passed to the View as options.
     *
     * @var array
     * @see \Cake\View\View
     */
    protected $_validViewOptions = [
        'viewPath'
    ];

    /**
     * List of valid options (constructor's fourth arguments)
     * Override this property in subclasses to whitelist
     * which options you want set as properties in your Cell.
     *
     * @var array
     */
    protected $_validCellOptions = [];

    /**
     * Caching setup.
     *
     * @var array|bool
     */
    protected $_cache = false;

    /**
     * Constructor.
     *
     * @param \Cake\Http\ServerRequest|null $request The request to use in the cell.
     * @param \Cake\Http\Response|null $response The response to use in the cell.
     * @param \Cake\Event\EventManager|null $eventManager The eventManager to bind events to.
     * @param array $cellOptions Cell options to apply.
     */
    public function __construct(
        ServerRequest $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $cellOptions = []
    ) {
        $this->eventManager($eventManager);
        $this->request = $request;
        $this->response = $response;
        $this->modelFactory('Table', [$this->tableLocator(), 'get']);

        $this->_validCellOptions = array_merge(['action', 'args'], $this->_validCellOptions);
        foreach ($this->_validCellOptions as $var) {
            if (isset($cellOptions[$var])) {
                $this->{$var} = $cellOptions[$var];
            }
        }
        if (!empty($cellOptions['cache'])) {
            $this->_cache = $cellOptions['cache'];
        }
    }

    /**
     * Render the cell.
     *
     * @param string|null $template Custom template name to render. If not provided (null), the last
     * value will be used. This value is automatically set by `CellTrait::cell()`.
     * @return string The rendered cell.
     * @throws \Cake\View\Exception\MissingCellViewException When a MissingTemplateException is raised during rendering.
     */
    public function render($template = null)
    {
        $cache = [];
        if ($this->_cache) {
            $cache = $this->_cacheConfig($this->action, $template);
        }

        $render = function () use ($template) {
            try {
                $reflect = new ReflectionMethod($this, $this->action);
                $reflect->invokeArgs($this, $this->args);
            } catch (ReflectionException $e) {
                throw new BadMethodCallException(sprintf(
                    'Class %s does not have a "%s" method.',
                    get_class($this),
                    $this->action
                ));
            }

            $builder = $this->viewBuilder();

            if ($template !== null &&
                strpos($template, '/') === false &&
                strpos($template, '.') === false
            ) {
                $template = Inflector::underscore($template);
            }
            if ($template === null) {
                $template = $builder->getTemplate() ?: $this->template;
            }
            $builder->setLayout(false)
                ->setTemplate($template);

            $className = get_class($this);
            $namePrefix = '\View\Cell\\';
            $name = substr($className, strpos($className, $namePrefix) + strlen($namePrefix));
            $name = substr($name, 0, -4);
            if (!$builder->getTemplatePath()) {
                $builder->setTemplatePath('Cell' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name));
            }

            $this->View = $this->createView();
            try {
                return $this->View->render($template);
            } catch (MissingTemplateException $e) {
                throw new MissingCellViewException(['file' => $template, 'name' => $name]);
            }
        };

        if ($cache) {
            return Cache::remember($cache['key'], $render, $cache['config']);
        }

        return $render();
    }

    /**
     * Generate the cache key to use for this cell.
     *
     * If the key is undefined, the cell class and action name will be used.
     *
     * @param string $action The action invoked.
     * @param string|null $template The name of the template to be rendered.
     * @return array The cache configuration.
     */
    protected function _cacheConfig($action, $template = null)
    {
        if (empty($this->_cache)) {
            return [];
        }
        $template = $template ?: 'default';
        $key = 'cell_' . Inflector::underscore(get_class($this)) . '_' . $action . '_' . $template;
        $key = str_replace('\\', '_', $key);
        $default = [
            'config' => 'default',
            'key' => $key
        ];
        if ($this->_cache === true) {
            return $default;
        }

        return $this->_cache + $default;
    }

    /**
     * Magic method.
     *
     * Starts the rendering process when Cell is echoed.
     *
     * *Note* This method will trigger an error when view rendering has a problem.
     * This is because PHP will not allow a __toString() method to throw an exception.
     *
     * @return string Rendered cell
     * @throws \Error Include error details for PHP 7 fatal errors.
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            trigger_error(sprintf('Could not render cell - %s [%s, line %d]', $e->getMessage(), $e->getFile(), $e->getLine()), E_USER_WARNING);

            return '';
        } catch (Error $e) {
            throw new Error(sprintf('Could not render cell - %s [%s, line %d]', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * Debug info.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'plugin' => $this->plugin,
            'action' => $this->action,
            'args' => $this->args,
            'template' => $this->template,
            'viewClass' => $this->viewClass,
            'request' => $this->request,
            'response' => $this->response,
        ];
    }
}
