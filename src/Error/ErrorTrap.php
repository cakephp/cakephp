<?php
declare(strict_types=1);

namespace Cake\Error;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Error\Renderer\ConsoleErrorRenderer;
use Cake\Error\Renderer\HtmlErrorRenderer;
use Cake\Event\EventDispatcherTrait;
use Cake\Routing\Router;
use Exception;

/**
 * Entry point to CakePHP's error handling.
 *
 * Using the `register()` method you can attach an ErrorTrap to PHP's default error handler.
 *
 * When errors are trapped, errors are logged (if logging is enabled). Then the `Error.beforeRender` event is triggered.
 * Finally, errors are 'rendered' using the defined renderer. If no error renderer is defined in configuration
 * one of the default implementations will be chosen based on the PHP SAPI.
 */
class ErrorTrap
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\Cake\Error\ErrorTrap>
     */
    use EventDispatcherTrait;
    use InstanceConfigTrait;

    /**
     * Configuration options. Generally these are defined in config/app.php
     *
     * - `errorLevel` - int - The level of errors you are interested in capturing.
     * - `errorRenderer` - string - The class name of render errors with. Defaults
     *   to choosing between Html and Console based on the SAPI.
     * - `log` - boolean - Whether you want errors logged.
     * - `logger` - string - The class name of the error logger to use.
     * - `trace` - boolean - Whether backtraces should be included in
     *   logged errors.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'errorLevel' => E_ALL,
        'errorRenderer' => null,
        'log' => true,
        'logger' => ErrorLogger::class,
        'trace' => false,
    ];

    /**
     * Constructor
     *
     * @param array<string, mixed> $options An options array. See $_defaultConfig.
     */
    public function __construct(array $options = [])
    {
        $this->setConfig($options);
    }

    /**
     * Choose an error renderer based on config or the SAPI
     *
     * @return class-string<\Cake\Error\ErrorRendererInterface>
     */
    protected function chooseErrorRenderer(): string
    {
        $config = $this->getConfig('errorRenderer');
        if ($config !== null) {
            return $config;
        }

        /** @var class-string<\Cake\Error\ErrorRendererInterface> */
        return PHP_SAPI === 'cli' ? ConsoleErrorRenderer::class : HtmlErrorRenderer::class;
    }

    /**
     * Attach this ErrorTrap to PHP's default error handler.
     *
     * This will replace the existing error handler, and the
     * previous error handler will be discarded.
     *
     * This method will also set the global error level
     * via error_reporting().
     *
     * @return void
     */
    public function register(): void
    {
        $level = $this->_config['errorLevel'] ?? -1;
        error_reporting($level);
        set_error_handler($this->handleError(...), $level);
    }

    /**
     * Handle an error from PHP set_error_handler
     *
     * Will use the configured renderer to generate output
     * and output it.
     *
     * This method will dispatch the `Error.beforeRender` event which can be listened
     * to on the global event manager.
     *
     * @param int $code Code of error
     * @param string $description Error description
     * @param string|null $file File on which error occurred
     * @param int|null $line Line that triggered the error
     * @return bool True if error was handled
     */
    public function handleError(
        int $code,
        string $description,
        ?string $file = null,
        ?int $line = null,
    ): bool {
        if (!(error_reporting() & $code)) {
            return false;
        }
        if ($code === E_USER_ERROR || $code === E_ERROR || $code === E_PARSE) {
            throw new FatalErrorException($description, $code, $file, $line);
        }

        $trace = (array)Debugger::trace(['start' => 0, 'format' => 'points']);
        $error = new PhpError($code, $description, $file, $line, $trace);

        $ignoredPaths = (array)Configure::read('Error.ignoredDeprecationPaths');
        if ($code === E_USER_DEPRECATED && $ignoredPaths) {
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr((string)$file, strlen(ROOT) + 1));
            foreach ($ignoredPaths as $pattern) {
                $pattern = str_replace(DIRECTORY_SEPARATOR, '/', $pattern);
                if (fnmatch($pattern, $relativePath)) {
                    return true;
                }
            }
        }

        $debug = Configure::read('debug');
        $renderer = $this->renderer();

        try {
            // Log first in case rendering or event listeners fail
            $this->logError($error);
            $event = $this->dispatchEvent('Error.beforeRender', ['error' => $error]);
            if ($event->isStopped()) {
                return true;
            }
            $renderer->write($event->getResult() ?: $renderer->render($error, $debug));
        } catch (Exception $e) {
            // Fatal errors always log.
            $this->logger()->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Logging helper method.
     *
     * @param \Cake\Error\PhpError $error The error object to log.
     * @return void
     */
    protected function logError(PhpError $error): void
    {
        if (!$this->_config['log']) {
            return;
        }
        $this->logger()->logError($error, Router::getRequest(), $this->_config['trace']);
    }

    /**
     * Get an instance of the renderer.
     *
     * @return \Cake\Error\ErrorRendererInterface
     */
    public function renderer(): ErrorRendererInterface
    {
        /** @var class-string<\Cake\Error\ErrorRendererInterface> $class */
        $class = $this->getConfig('errorRenderer') ?: $this->chooseErrorRenderer();

        return new $class($this->_config);
    }

    /**
     * Get an instance of the logger.
     *
     * @return \Cake\Error\ErrorLoggerInterface
     */
    public function logger(): ErrorLoggerInterface
    {
        /** @var class-string<\Cake\Error\ErrorLoggerInterface> $class */
        $class = $this->getConfig('logger', $this->_defaultConfig['logger']);

        return new $class($this->_config);
    }
}
