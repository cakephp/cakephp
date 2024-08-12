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
namespace Cake\View;

use BadMethodCallException;
use Cake\Cache\Cache;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingCellTemplateException;
use Cake\View\Exception\MissingTemplateException;
use Error;
use Exception;
use ReflectionException;
use ReflectionMethod;
use Stringable;

/**
 * Cell base.
 *
 * @implements \Cake\Event\EventDispatcherInterface<\Cake\View\View>
 */
abstract class Cell implements EventDispatcherInterface, Stringable
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\Cake\View\View>
     */
    use EventDispatcherTrait;
    use LocatorAwareTrait;
    use ViewVarsTrait;

    /**
     * Constant for folder name containing cell templates.
     *
     * @var string
     */
    public const TEMPLATE_FOLDER = 'cell';

    /**
     * Instance of the View created during rendering. Won't be set until after
     * Cell::__toString()/render() is called.
     *
     * @var \Cake\View\View
     */
    protected View $View;

    /**
     * An instance of a Cake\Http\ServerRequest object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * @var \Cake\Http\ServerRequest
     */
    protected ServerRequest $request;

    /**
     * An instance of a Response object that contains information about the impending response
     *
     * @var \Cake\Http\Response
     */
    protected Response $response;

    /**
     * The cell's action to invoke.
     *
     * @var string
     */
    protected string $action;

    /**
     * Arguments to pass to cell's action.
     *
     * @var array
     */
    protected array $args = [];

    /**
     * List of valid options (constructor's fourth arguments)
     * Override this property in subclasses to allow
     * which options you want set as properties in your Cell.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = [];

    /**
     * Caching setup.
     *
     * @var array|bool
     */
    protected array|bool $_cache = false;

    /**
     * Constructor.
     *
     * @param \Cake\Http\ServerRequest $request The request to use in the cell.
     * @param \Cake\Http\Response $response The response to use in the cell.
     * @param \Cake\Event\EventManagerInterface|null $eventManager The eventManager to bind events to.
     * @param array<string, mixed> $cellOptions Cell options to apply.
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        ?EventManagerInterface $eventManager = null,
        array $cellOptions = []
    ) {
        if ($eventManager !== null) {
            $this->setEventManager($eventManager);
        }
        $this->request = $request;
        $this->response = $response;

        $this->_validCellOptions = array_merge(['action', 'args'], $this->_validCellOptions);
        foreach ($this->_validCellOptions as $var) {
            if (isset($cellOptions[$var])) {
                $this->{$var} = $cellOptions[$var];
            }
        }
        if (!empty($cellOptions['cache'])) {
            $this->_cache = $cellOptions['cache'];
        }

        $this->initialize();
    }

    /**
     * Initialization hook method.
     *
     * Implement this method to avoid having to overwrite
     * the constructor and calling parent::__construct().
     *
     * @return void
     */
    public function initialize(): void
    {
    }

    /**
     * Render the cell.
     *
     * @param string|null $template Custom template name to render. If not provided (null), the last
     * value will be used. This value is automatically set by `CellTrait::cell()`.
     * @return string The rendered cell.
     * @throws \Cake\View\Exception\MissingCellTemplateException|\BadMethodCallException
     */
    public function render(?string $template = null): string
    {
        $cache = [];
        if ($this->_cache) {
            $cache = $this->_cacheConfig($this->action, $template);
        }

        $render = function () use ($template): string {
            try {
                $this->dispatchEvent('Cell.beforeAction', [$this, $this->action, $this->args]);
                $reflect = new ReflectionMethod($this, $this->action);
                $reflect->invokeArgs($this, $this->args);
                $this->dispatchEvent('Cell.afterAction', [$this, $this->action, $this->args]);
            } catch (ReflectionException $e) {
                throw new BadMethodCallException(sprintf(
                    'Class `%s` does not have a `%s` method.',
                    static::class,
                    $this->action
                ));
            }

            $builder = $this->viewBuilder();

            if ($template !== null) {
                $builder->setTemplate($template);
            }

            $className = static::class;
            $namePrefix = '\View\Cell\\';
            /** @psalm-suppress PossiblyFalseOperand */
            $name = substr($className, strpos($className, $namePrefix) + strlen($namePrefix));
            $name = substr($name, 0, -4);
            if (!$builder->getTemplatePath()) {
                $builder->setTemplatePath(
                    static::TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name)
                );
            }
            $template = $builder->getTemplate();

            $view = $this->createView();
            try {
                return $view->render($template, false);
            } catch (MissingTemplateException $e) {
                $attributes = $e->getAttributes();
                throw new MissingCellTemplateException(
                    $name,
                    $attributes['file'],
                    $attributes['paths'],
                    null,
                    $e
                );
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
    protected function _cacheConfig(string $action, ?string $template = null): array
    {
        if (!$this->_cache) {
            return [];
        }
        $template = $template ?: 'default';
        $key = 'cell_' . Inflector::underscore(static::class) . '_' . $action . '_' . $template;
        $key = str_replace('\\', '_', $key);
        $default = [
            'config' => 'default',
            'key' => $key,
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
    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            trigger_error(sprintf(
                'Could not render cell - %s [%s, line %d]',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), E_USER_WARNING);

            return '';
        /** @phpstan-ignore-next-line */
        } catch (Error $e) {
            throw new Error(sprintf(
                'Could not render cell - %s [%s, line %d]',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), 0, $e);
        }
    }

    /**
     * Debug info.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'action' => $this->action,
            'args' => $this->args,
            'request' => $this->request,
            'response' => $this->response,
            'viewBuilder' => $this->viewBuilder(),
        ];
    }
}
