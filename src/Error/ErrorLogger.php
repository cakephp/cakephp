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
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use Throwable;

/**
 * Log errors and unhandled exceptions to `Cake\Log\Log`
 */
class ErrorLogger implements ErrorLoggerInterface
{
    use InstanceConfigTrait;
    use LoggerTrait;

    /**
     * Default configuration values.
     *
     * - `trace` Should error logs include stack traces?
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
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
    public function log($level, Stringable|string $message, array $context = []): void
    {
        Log::write($level, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function logError(PhpError $error, ?ServerRequestInterface $request = null, bool $includeTrace = false): void
    {
        $message = $error->getMessage();
        if ($request) {
            $message .= $this->getRequestContext($request);
        }
        if ($includeTrace) {
            $message .= "\nTrace:\n" . $error->getTraceAsString() . "\n";
        }
        $label = $error->getLabel();
        $level = match ($label) {
            'strict' => LOG_NOTICE,
            'deprecated' => LOG_DEBUG,
            default => $label,
        };

        $this->log($level, $message);
    }

    /**
     * @inheritDoc
     */
    public function logException(
        Throwable $exception,
        ?ServerRequestInterface $request = null,
        bool $includeTrace = false
    ): void {
        $message = $this->getMessage($exception, false, $includeTrace);

        if ($request !== null) {
            $message .= $this->getRequestContext($request);
        }
        $this->error($message);
    }

    /**
     * Generate the message for the exception
     *
     * @param \Throwable $exception The exception to log a message for.
     * @param bool $isPrevious False for original exception, true for previous
     * @param bool $includeTrace Whether or not to include a stack trace.
     * @return string Error message
     */
    protected function getMessage(Throwable $exception, bool $isPrevious = false, bool $includeTrace = false): string
    {
        $message = sprintf(
            '%s[%s] %s in %s on line %s',
            $isPrevious ? "\nCaused by: " : '',
            $exception::class,
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

        if ($includeTrace) {
            $trace = Debugger::formatTrace($exception, ['format' => 'shortPoints']);
            assert(is_array($trace));
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
            $message .= $this->getMessage($previous, true, $includeTrace);
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

        if ($request instanceof ServerRequest) {
            $clientIp = $request->clientIp();
            if ($clientIp && $clientIp !== '::1') {
                $message .= "\nClient IP: " . $clientIp;
            }
        }

        return $message;
    }
}
