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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Error;

use Cake\Core\InstanceConfigTrait;
use Cake\Error\ErrorLoggerInterface;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Test stub that only implements the interface as declared.
 * Necessary to test the deprecated logging paths in ErrorTrap and ExceptionTrap
 */
class LegacyErrorLogger implements ErrorLoggerInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration values.
     *
     * - `trace` Should error logs include stack traces?
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
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
     * @param string|int $level The logging level
     * @param string $message The message to be logged.
     * @param array $context Context.
     * @return bool
     */
    public function logMessage($level, string $message, array $context = []): bool
    {
        if (!empty($context['request'])) {
            $message .= ' URL=' . $context['request']->getUri();
        }
        if (!empty($context['trace'])) {
            $message .= ' IncludeTrace';
        }
        $logMap = [
            'strict' => LOG_NOTICE,
            'deprecated' => LOG_NOTICE,
        ];
        $level = $logMap[$level] ?? $level;

        return Log::write($level, $message);
    }

    /**
     * @param \Throwable $exception The exception to log a message for.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The current request if available.
     * @return bool
     */
    public function log(Throwable $exception, ?ServerRequestInterface $request = null): bool
    {
        $message = $exception->getMessage();
        if ($request !== null) {
            $message .= 'URL=' . $request->getUri();
        }
        if ($this->getConfig('trace')) {
            $message .= 'IncludeTrace';
        }

        return Log::error($message);
    }
}
