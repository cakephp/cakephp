<?php
declare(strict_types=1);

namespace Cake\Error;

use Cake\Core\InstanceConfigTrait;
use Cake\Error\Renderer\ConsoleExceptionRenderer;
use Cake\Event\EventDispatcherTrait;
use Cake\Routing\Router;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Entry point to CakePHP's exception handling.
 *
 * Using the `register()` method you can attach an ExceptionTrap to PHP's default exception handler and register
 * a shutdown handler to handle fatal errors.
 *
 * When exceptions are trapped the `Exception.handled` event is triggered.
 * Then exceptions are logged (if enabled) and finally 'rendered' using the defined renderer.
 *
 * If undefined, an ExceptionRenderer will be selected based on the current SAPI (CLI or Web).
 */
class ExceptionTrap
{
    use EventDispatcherTrait;
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
        'exceptionRenderer' => null,
        'logger' => ErrorLogger::class,
        'stderr' => null,
        'log' => true,
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
     * The currently registered global exception handler
     *
     * This is best effort as we can't know if/when another
     * exception handler is registered.
     *
     * @var \Cake\Error\ExceptionTrap|null
     */
    protected static $registeredTrap = null;

    /**
     * Track if this trap was removed from the global handler.
     *
     * @var bool
     */
    protected $disabled = false;

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
     * Get an instance of the renderer.
     *
     * @param \Throwable $exception Exception to render
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The request if possible.
     * @return \Cake\Error\ExceptionRendererInterface
     */
    public function renderer(Throwable $exception, $request = null)
    {
        $request = $request ?? Router::getRequest();

        /** @var class-string|callable $class */
        $class = $this->_getConfig('exceptionRenderer');
        if (!$class) {
            $class = $this->chooseRenderer();
        }

        if (is_string($class)) {
            /** @var class-string $class */
            if (!(method_exists($class, 'render') && method_exists($class, 'write'))) {
                throw new InvalidArgumentException(
                    "Cannot use {$class} as an `exceptionRenderer`. " .
                    'It must implement render() and write() methods.'
                );
            }

            /** @var \Cake\Error\ExceptionRendererInterface $instance */
            $instance = new $class($exception, $request, $this->_config);

            return $instance;
        }

        return $class($exception, $request);
    }

    /**
     * Choose an exception renderer based on config or the SAPI
     *
     * @return class-string<\Cake\Error\ExceptionRendererInterface>
     */
    protected function chooseRenderer(): string
    {
        /** @var class-string<\Cake\Error\ExceptionRendererInterface> */
        return PHP_SAPI === 'cli' ? ConsoleExceptionRenderer::class : ExceptionRenderer::class;
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
                "Cannot use {$class} as an exception logger. " .
                "It must implement \Cake\Error\ErrorLoggerInterface."
            );
        }

        /** @var \Cake\Error\ErrorLoggerInterface $instance */
        $instance = new $class($this->_config);

        return $instance;
    }

    /**
     * Attach this ExceptionTrap to PHP's default exception handler.
     *
     * This will replace the existing exception handler, and the
     * previous exception handler will be discarded.
     *
     * @return void
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        static::$registeredTrap = $this;
    }

    /**
     * Remove this instance from the singleton
     *
     * If this instance is not currently the registered singleton
     * nothing happens.
     *
     * @return void
     */
    public function unregister(): void
    {
        if (static::$registeredTrap == $this) {
            $this->disabled = true;
            static::$registeredTrap = null;
        }
    }

    /**
     * Get the registered global instance if set.
     *
     * Keep in mind that the global state contained here
     * is mutable and the object returned by this method
     * could be a stale value.
     *
     * @return \Cake\Error\ExceptionTrap|null The global instance or null.
     */
    public static function instance(): ?self
    {
        return static::$registeredTrap;
    }

    /**
     * Handle uncaught exceptions.
     *
     * Uses a template method provided by subclasses to display errors in an
     * environment appropriate way.
     *
     * @param \Throwable $exception Exception instance.
     * @return void
     * @throws \Exception When renderer class not found
     * @see https://secure.php.net/manual/en/function.set-exception-handler.php
     */
    public function handleException(Throwable $exception): void
    {
        if ($this->disabled) {
            return;
        }
        $request = Router::getRequest();

        $this->logException($exception, $request);

        try {
            $renderer = $this->renderer($exception);
            $renderer->write($renderer->render());
        } catch (Throwable $exception) {
            $this->logInternalError($exception);
        }
    }

    /**
     * Shutdown handler
     *
     * Convert fatal errors into exceptions that we can render.
     *
     * @return void
     */
    public function handleShutdown(): void
    {
        if ($this->disabled) {
            return;
        }
        $megabytes = $this->_config['extraFatalErrorMemory'] ?? 4;
        if ($megabytes > 0) {
            $this->increaseMemoryLimit($megabytes * 1024);
        }
        $error = error_get_last();
        if (!is_array($error)) {
            return;
        }
        $fatals = [
            E_USER_ERROR,
            E_ERROR,
            E_PARSE,
        ];
        if (!in_array($error['type'], $fatals, true)) {
            return;
        }
        $this->handleFatalError(
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );
    }

    /**
     * Increases the PHP "memory_limit" ini setting by the specified amount
     * in kilobytes
     *
     * @param int $additionalKb Number in kilobytes
     * @return void
     */
    public function increaseMemoryLimit(int $additionalKb): void
    {
        $limit = ini_get('memory_limit');
        if ($limit === false || $limit === '' || $limit === '-1') {
            return;
        }
        $limit = trim($limit);
        $units = strtoupper(substr($limit, -1));
        $current = (int)substr($limit, 0, -1);
        if ($units === 'M') {
            $current *= 1024;
            $units = 'K';
        }
        if ($units === 'G') {
            $current = $current * 1024 * 1024;
            $units = 'K';
        }

        if ($units === 'K') {
            ini_set('memory_limit', ceil($current + $additionalKb) . 'K');
        }
    }

    /**
     * Display/Log a fatal error.
     *
     * @param int $code Code of error
     * @param string $description Error description
     * @param string $file File on which error occurred
     * @param int $line Line that triggered the error
     * @return void
     */
    public function handleFatalError(int $code, string $description, string $file, int $line): void
    {
        $this->handleException(new FatalErrorException('Fatal Error: ' . $description, 500, $file, $line));
    }

    /**
     * Log an exception.
     *
     * Primarily a public function to ensure consistency between global exception handling
     * and the ErrorHandlerMiddleware.
     *
     * After logging, the `Exception.handled` event is triggered.
     *
     * @param \Throwable $exception The exception to log
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The optional request
     * @return void
     */
    public function logException(Throwable $exception, ?ServerRequestInterface $request = null): void
    {
        $logger = $this->logger();
        $logger->log($exception, $request);
        $this->dispatchEvent('Exception.handled', ['exception' => $exception]);
    }

    /**
     * Trigger an error that occurred during rendering an exception.
     *
     * By triggering an E_USER_ERROR we can end up in the default
     * exception handling which will log the rendering failure,
     * and hopefully render an error page.
     *
     * @param \Throwable $exception Exception to log
     * @return void
     */
    public function logInternalError(Throwable $exception): void
    {
        // Disable trace for internal errors.
        $this->_config['trace'] = false;
        $message = sprintf(
            "[%s] %s (%s:%s)\n%s", // Keeping same message format
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        trigger_error($message, E_USER_ERROR);
    }
}
