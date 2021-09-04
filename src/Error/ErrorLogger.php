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
namespace Cake\Error;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Log errors and unhandled exceptions to `Cake\Log\Log`
 */
class ErrorLogger implements ErrorLoggerInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration values.
     *
     * - `skipLog` List of exceptions to skip logging. Exceptions that
     *   extend one of the listed exceptions will also not be logged.
     * - `trace` Should error logs include stack traces?
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'skipLog' => [],
        'trace' => false,
    ];

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Config array.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @inheritDoc
     */
    public function logMessage($level, string $message, array $context = []): bool
    {
        if (!empty($context['request'])) {
            $message .= $this->getRequestContext($context['request']);
        }
        if (!empty($context['trace'])) {
            $message .= "\nTrace:\n" . $context['trace'] . "\n";
        }

        return Log::write($level, $message);
    }

    /**
     * @inheritDoc
     */
    public function log(Throwable $exception, ?ServerRequestInterface $request = null): bool
    {
        foreach ($this->getConfig('skipLog') as $class) {
            if ($exception instanceof $class) {
                return false;
            }
        }

        $message = $this->getMessage($exception);

        if ($request !== null) {
            $message .= $this->getRequestContext($request);
        }

        $message .= "\n\n";

        return Log::error($message);
    }

    /**
     * Generate the message for the exception
     *
     * @param \Throwable $exception The exception to log a message for.
     * @param bool $isPrevious False for original exception, true for previous
     * @return string Error message
     */
    protected function getMessage(Throwable $exception, bool $isPrevious = false): string
    {
        $message = sprintf(
            '%s[%s] %s in %s on line %s',
            $isPrevious ? "\nCaused by: " : '',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        $debug = Configure::read('debug');

        if ($debug && $exception instanceof CakeException) {
            $attributes = $exception->getAttributes();
            if ($attributes) {
                $message .= "\nException Attributes: " . var_export($exception->getAttributes(), true);
            }
        }

        if ($this->getConfig('trace')) {
            /** @var array $trace */
            $trace = Debugger::formatTrace($exception, ['format' => 'points']);
            $message .= "\nStack Trace:\n";
            foreach ($trace as $line) {
                if (is_string($line)) {
                    $message .= '- ' . $line;
                } else {
                    $message .= "- {$line['file']}:{$line['line']}\n";
                }
            }
        }

        $previous = $exception->getPrevious();
        if ($previous) {
            $message .= $this->getMessage($previous, true);
        }

        return $message;
    }

    /**
     * Get the request context for an error/exception trace.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to read from.
     * @return string
     */
    public function getRequestContext(ServerRequestInterface $request): string
    {
        $message = "\nRequest URL: " . $request->getRequestTarget();

        $referer = $request->getHeaderLine('Referer');
        if ($referer) {
            $message .= "\nReferer URL: " . $referer;
        }

        if (method_exists($request, 'clientIp')) {
            $clientIp = $request->clientIp();
            if ($clientIp && $clientIp !== '::1') {
                $message .= "\nClient IP: " . $clientIp;
            }
        }

        return $message;
    }
}
