<?php
declare(strict_types=1);

namespace Cake\Error;

use Cake\Core\InstanceConfigTrait;
use Cake\Error\ErrorRendererInterface;
use Cake\Error\Renderer\HtmlRenderer;
use Cake\Error\Renderer\TextRenderer;
use Closure;
use InvalidArgumentException;
use LogicException;

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
    use InstanceConfigTrait;

    /**
     * @var array
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
     * @var bool
     */
    protected $registered = false;

    /**
     * Constructor
     *
     * @param array $options An options array.
     */
    public function __construct(array $options = [])
    {
        $this->setConfig($options);
        $this->setErrorRenderer($this->chooseErrorRenderer());
        $this->setLogger($this->getConfig('logger'));
        $this->setLevel($this->getConfig('errorLevel'));
    }

    /**
     * Choose an error renderer based on config or the SAPI
     *
     * @return class-string<\Cake\Error\ErrorLoggerInterface>
     */
    protected function chooseErrorRenderer(): string
    {
        $config = $this->getConfig('errorRenderer');
        if ($config !== null) {
            return $config;
        }

        return PHP_SAPI === 'cli' ? TextRenderer::class : HtmlRenderer::class;
    }

    /**
     * Attach this ErrorTrap to PHP's default error handler.
     *
     * This will replace the existing error handler, and the
     * previous error handler will be discarded.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Get an instance of the renderer.
     *
     * @return \Cake\Error\ErrorRendererInterface
     */
    public function renderer(): ErrorRendererInterface
    {
        $class = $this->getConfig('errorRenderer');

        return new $class($this->_config);
    }

    /**
     * Get an instance of the logger.
     *
     * @return \Cake\Error\ErrorLoggerInterface
     */
    public function logger(): ErrorLoggerInterface
    {
        $class = $this->getConfig('logger');

        return new $class($this->_config);
    }

    /**
     * Change the error renderer
     *
     * @param class-string<\Cake\Error\ErrorRendererInterface> $class The class to use as a renderer.
     * @return $this
     */
    public function setErrorRenderer(string $class)
    {
        if (!in_array(ErrorRendererInterface::class, class_implements($class))) {
            throw new InvalidArgumentException(
                "Cannot use {$class} as an error renderer. It must implement \Cake\Error\ErrorRendererInterface."
            );
        }
        $this->setConfig('errorRenderer', $class);

        return $this;
    }

    /**
     * Set the PHP error reporting level
     *
     * @param int $level The PHP error reporting value to use.
     * @return $this
     */
    public function setLevel(int $level)
    {
        if ($this->registered) {
            throw new LogicException('Cannot change level after an ErrorTrap has been registered.');
        }
        $this->setConfig('level', $level);

        return $this;
    }

    /**
     * Set the Error logging implementation
     *
     * When the logger is constructed it will be passed
     * the current options array.
     *
     * @param int $level The PHP error reporting value to use.
     * @return $this
     */
    public function setLogger(string $class)
    {
        if (!in_array(ErrorLoggerInterface::class, class_implements($class))) {
            throw new InvalidArgumentException(
                "Cannot use {$class} as an error renderer. It must implement \Cake\Error\ErrorLoggerInterface."
            );
        }
        $this->setConfig('logger', $class);

        return $this;
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
        return $this;
    }
}
