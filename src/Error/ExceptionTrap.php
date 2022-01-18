<?php
declare(strict_types=1);

namespace Cake\Error;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Closure;
use InvalidArgumentException;
use Throwable;

/**
 * Entry point to CakePHP's exception handling.
 *
 * Using the `register()` method you can attach an ExceptionTrap
 * to PHP's default exception handler and register a shutdown
 * handler to handle fatal errors. When exceptions are trapped
 * they are 'rendered' using the defined renderers and logged
 * if logging is enabled.
 */
class ExceptionTrap
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
        'exceptionRenderer' => ExceptionRenderer::class,
        'logger' => ErrorLogger::class,
        // Used by ConsoleExceptionRenderer (coming soon)
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
     * @return \Cake\Error\ExceptionRendererInterface
     */
    public function renderer(Throwable $exception)
    {
        // The return of this method is not defined because
        // the desired interface has bad types that will be changing in 5.x
        $request = Router::getRequest();
        $class = $this->_getConfig('exceptionRenderer');

        if (is_string($class)) {
            if (!(method_exists($class, 'render') && method_exists($class, 'write'))) {
                throw new InvalidArgumentException(
                    "Cannot use {$class} as an `exceptionRenderer`. " .
                    'It must implement render() and write() methods.'
                );
            }

            /** @var \Cake\Error\ExceptionRendererInterface $instance */
            $instance = new $class($exception, $request);

            return $instance;
        }

        /** @var callable $factory */
        $factory = $class;

        return $factory($exception, $request);
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
     * Add a callback to be invoked when an error is handled.
     *
     * Your callback should habe the following signature:
     *
     * ```
     * function (\Throwable $error): void
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
        $request = Router::getRequest();

        $this->logException($exception, $request);
        foreach ($this->callbacks as $callback) {
            $callback($exception);
        }

        try {
            $renderer = $this->renderer($exception);
            $renderer->write($renderer->render());
        } catch (Throwable $exception) {
            $this->logInternalError($exception);
        }
    }

    /**
     * Log an exception.
     *
     * Primarily a public function to ensure consistency between global exception handling
     * and the ErrorHandlerMiddleware
     *
     * @param \Throwable $exception The exception to log
     * @param \Cake\Http\ServerRequest|null $request The optional request
     * @return void
     */
    public function logException(Throwable $exception, ?ServerRequest $request = null): void
    {
        $logger = $this->logger();
        $logger->log($exception, $request);
    }

    /**
     * Trigger an error that occurred during rendering an exception.
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
