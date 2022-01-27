<?php
declare(strict_types=1);

namespace Cake\Error;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Error\Renderer\ConsoleErrorRenderer;
use Cake\Error\Renderer\HtmlErrorRenderer;
use Closure;
use Exception;
use InvalidArgumentException;

/**
 * Entry point to CakePHP's error handling.
 *
 * Using the `register()` method you can attach an ErrorTrap
 * to PHP's default error handler. When errors are trapped
 * they are 'rendered' using the defined renderers and logged
 * if logging is enabled.
 */
class ErrorTrap
{
    use InstanceConfigTrait {
        getConfig as private _getConfig;
    }

    /**
     * See the `Error` key in you `config/app.php`
     * for details on the keys and their values.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'errorLevel' => E_ALL,
        'ignoredDeprecationPaths' => [],
        'log' => true,
        'logger' => ErrorLogger::class,
        'errorRenderer' => null,
        'extraFatalErrorMemory' => 4 * 1024,
        'trace' => false,
    ];

    /**
     * A list of handling callbacks.
     *
     * Callbacks are invoked for each error that is handled.
     * Callbacks are invoked in the order they are attached.
     *
     * @var array<\Closure>
     */
    protected $callbacks = [];

    /**
     * Constructor
     *
     * @param array<string, mixed> $options An options array. See $_defaultConfig.
     */
    public function __construct(array $options = [])
    {
        $this->setConfig($options);
        if ($this->_getConfig('errorRenderer') === null) {
            $this->setConfig('errorRenderer', $this->chooseErrorRenderer());
        }
    }

    /**
     * Choose an error renderer based on config or the SAPI
     *
     * @return class-string<\Cake\Error\ErrorRendererInterface>
     */
    protected function chooseErrorRenderer(): string
    {
        $config = $this->_getConfig('errorRenderer');
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
        set_error_handler([$this, 'handleError'], $level);
    }

    /**
     * Handle an error from PHP set_error_handler
     *
     * Will use the configured renderer to generate output
     * and output it.
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
        ?int $line = null
    ): bool {
        if (!(error_reporting() & $code)) {
            return false;
        }
        $debug = Configure::read('debug');
        /** @var array $trace */
        $trace = Debugger::trace(['start' => 1, 'format' => 'points']);
        $error = new PhpError($code, $description, $file, $line, $trace);

        $renderer = $this->renderer();
        $logger = $this->logger();

        try {
            // Log first incase rendering or callbacks fail.
            $logger->logMessage($error->getLabel(), $error->getMessage());

            foreach ($this->callbacks as $callback) {
                $callback($error);
            }
            $renderer->write($renderer->render($error, $debug));
        } catch (Exception $e) {
            $logger->logMessage('error', 'Could not render error. Got: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Get an instance of the renderer.
     *
     * @return \Cake\Error\ErrorRendererInterface
     */
    public function renderer(): ErrorRendererInterface
    {
        $class = $this->_getConfig('errorRenderer');
        if (!$class) {
            $class = $this->chooseErrorRenderer();
        }
        if (!in_array(ErrorRendererInterface::class, class_implements($class))) {
            throw new InvalidArgumentException(
                "Cannot use {$class} as an error renderer. It must implement \Cake\Error\ErrorRendererInterface."
            );
        }

        /** @var \Cake\Error\ErrorRendererInterface $instance */
        $instance = new $class($this->_config);

        return $instance;
    }

    /**
     * Get an instance of the logger.
     *
     * @return \Cake\Error\ErrorLoggerInterface
     */
    public function logger(): ErrorLoggerInterface
    {
        $class = $this->_getConfig('logger');
        if (!$class) {
            $class = $this->_defaultConfig['logger'];
        }
        if (!in_array(ErrorLoggerInterface::class, class_implements($class))) {
            throw new InvalidArgumentException(
                "Cannot use {$class} as an error logger. It must implement \Cake\Error\ErrorLoggerInterface."
            );
        }

        /** @var \Cake\Error\ErrorLoggerInterface $instance */
        $instance = new $class($this->_config);

        return $instance;
    }

    /**
     * Add a callback to be invoked when an error is handled.
     *
     * Your callback should habe the following signature:
     *
     * ```
     * function (\Cake\Error\PhpError $error): void
     * ```
     *
     * @param \Closure $closure The Closure to be invoked when an error is handledd.
     * @return $this
     */
    public function addCallback(Closure $closure)
    {
        $this->callbacks[] = $closure;

        return $this;
    }
}
