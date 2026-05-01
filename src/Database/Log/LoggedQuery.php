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
namespace Cake\Database\Log;

use Cake\Database\Driver;
use Cake\Database\Driver\Sqlserver;
use Closure;
use Exception;
use JsonSerializable;
use ReflectionProperty;
use Stringable;
use Throwable;

/**
 * Contains a query string, the params used to executed it, time taken to do it
 * and the number of rows found or affected by its execution.
 *
 * The static {@see LoggedQuery::setRedactor()} hook is the one supported
 * extension point; instantiation and subclassing are not.
 *
 * @internal
 */
class LoggedQuery implements JsonSerializable, Stringable
{
    /**
     * Driver executing the query
     *
     * @var \Cake\Database\Driver|null
     */
    protected ?Driver $driver = null;

    /**
     * Query string that was executed
     *
     * @var string
     */
    protected string $query = '';

    /**
     * Number of milliseconds this query took to complete
     *
     * @var float
     */
    protected float $took = 0;

    /**
     * Associative array with the params bound to the query string
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Number of rows affected or returned by the query execution
     *
     * @var int
     */
    protected int $numRows = 0;

    /**
     * The exception that was thrown by the execution of this query
     *
     * @var \Exception|null
     */
    protected ?Exception $error = null;

    /**
     * Optional redactor invoked before the stored query string or bound
     * parameters are exposed via {@see __toString()}, {@see getContext()},
     * or {@see jsonSerialize()}. Receives `(string $query, array $params)`
     * and must return a 2-element list `[string $query, array $params]`
     * with sensitive substrings replaced.
     *
     * Used to keep secrets bound as parameters (cryptographic keys,
     * passwords, tokens) out of file logs, structured loggers, and
     * remote breadcrumb sinks. Set once during application bootstrap;
     * applies to every LoggedQuery instance from then on.
     *
     * @var \Closure|null
     */
    protected static ?Closure $redactor = null;

    /**
     * Register a global redactor applied to every LoggedQuery before its
     * query string or params are exposed for logging.
     *
     * The redactor receives the raw `(string $query, array $params)` and
     * must return a 2-element list `[string $query, array $params]` with
     * the sensitive substrings replaced. Returning a malformed value
     * causes the redactor to be ignored for that call (the raw query and
     * params are used instead) so a faulty redactor cannot break logging.
     *
     * Pass `null` to clear a previously-registered redactor.
     *
     * @param \Closure|null $redactor `fn(string, array): array{0: string, 1: array}`
     * @return void
     */
    public static function setRedactor(?Closure $redactor): void
    {
        self::$redactor = $redactor;
    }

    /**
     * Apply the configured redactor (if any) to the stored query+params
     * and return the sanitized tuple.
     *
     * Both a malformed return value and an exception thrown by the
     * redactor are caught here; in either case the raw query and params
     * are used as a safe fallback so a faulty redactor cannot break
     * logging.
     *
     * @return array{0: string, 1: array} [sanitized query, sanitized params]
     */
    protected function redacted(): array
    {
        if (self::$redactor === null) {
            return [$this->query, $this->params];
        }

        try {
            $result = (self::$redactor)($this->query, $this->params);
        } catch (Throwable) {
            return [$this->query, $this->params];
        }

        if (!is_array($result) || !isset($result[0], $result[1]) || !is_string($result[0]) || !is_array($result[1])) {
            return [$this->query, $this->params];
        }

        return [$result[0], $result[1]];
    }

    /**
     * Helper function used to replace query placeholders by the real
     * params used to execute the query
     *
     * @return string
     */
    protected function interpolate(): string
    {
        [$query, $rawParams] = $this->redacted();

        $params = array_map(function ($p) {
            if ($p === null) {
                return 'NULL';
            }

            if (is_bool($p)) {
                if ($this->driver instanceof Sqlserver) {
                    return $p ? '1' : '0';
                }

                return $p ? 'TRUE' : 'FALSE';
            }

            if (is_string($p)) {
                // Likely binary data like a blob or binary uuid.
                // pattern matches ascii control chars.
                if (preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $p) !== $p) {
                    $p = bin2hex($p);
                }

                $replacements = [
                    '$' => '\\$',
                    '\\' => '\\\\\\\\',
                    "'" => "''",
                ];

                $p = strtr($p, $replacements);

                return "'{$p}'";
            }

            return $p;
        }, $rawParams);

        $keys = [];
        $limit = is_int(key($params)) ? 1 : -1;
        foreach ($params as $key => $param) {
            $keys[] = is_string($key) ? "/:{$key}\b/" : '/[?]/';
        }

        return (string)preg_replace($keys, $params, $query, $limit);
    }

    /**
     * Get the logging context data for a query.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        [$query] = $this->redacted();

        $context = [
            'query' => $query,
            'numRows' => $this->numRows,
            'took' => $this->took,
            'role' => $this->driver ? $this->driver->getRole() : '',
        ];

        $connectionName = $this->getConnectionName();
        if ($connectionName !== '') {
            $context['connection'] = $connectionName;
        }

        return $context;
    }

    /**
     * Get the connection name from the driver config.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        if ($this->driver === null) {
            return '';
        }

        return $this->driver->config()['name'] ?? '';
    }

    /**
     * Set logging context for this query.
     *
     * @param array<string, mixed> $context Context data.
     * @return void
     */
    public function setContext(array $context): void
    {
        foreach ($context as $key => $val) {
            if (!property_exists($this, $key)) {
                continue;
            }
            // Skip static properties (e.g. `redactor`); writing to them via
            // `$this->{$key} = $val` would create a dynamic instance
            // property and trip PHP 8.2+ deprecation notices instead of
            // updating the static.
            if ((new ReflectionProperty($this, $key))->isStatic()) {
                continue;
            }
            $this->{$key} = $val;
        }
    }

    /**
     * Returns data that will be serialized as JSON
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        [$query, $params] = $this->redacted();

        $error = $this->error;
        if ($error !== null) {
            $error = [
                'class' => $error::class,
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
            ];
        }

        return [
            'query' => $query,
            'numRows' => $this->numRows,
            'params' => $params,
            'took' => $this->took,
            'error' => $error,
        ];
    }

    /**
     * Returns the string representation of this logged query
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->params) {
            return $this->interpolate();
        }

        [$query] = $this->redacted();

        return $query;
    }
}
