<?php
declare(strict_types=1);

/**
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakefoundation.org CakePHP(tm) Project
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

use ArrayObject;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\Formatter\AbstractFormatter;
use Cake\Log\Formatter\DefaultFormatter;
use InvalidArgumentException;
use JsonSerializable;
use Psr\Log\AbstractLogger;
use Serializable;
use Stringable;

/**
 * Base log engine class.
 */
abstract class BaseLog extends AbstractLogger
{
    use InstanceConfigTrait;

    /**
     * Default config for this class
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'levels' => [],
        'scopes' => [],
        'formatter' => DefaultFormatter::class,
    ];

    /**
     * @var \Cake\Log\Formatter\AbstractFormatter
     */
    protected $formatter;

    /**
     * __construct method
     *
     * @param array<string, mixed> $config Configuration array
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        assert(
            $this->_config['scopes'] !== false,
            new InvalidArgumentException('Use `null` instead of `false` to disable scopes.')
        );

        if ($this->_config['scopes'] !== null) {
            $this->_config['scopes'] = (array)$this->_config['scopes'];
        }

        $this->_config['levels'] = (array)$this->_config['levels'];

        if (!empty($this->_config['types']) && empty($this->_config['levels'])) {
            $this->_config['levels'] = (array)$this->_config['types'];
        }

        $formatter = $this->_config['formatter'] ?? DefaultFormatter::class;
        if (!is_object($formatter)) {
            if (is_array($formatter)) {
                $class = $formatter['className'];
                $options = $formatter;
            } else {
                $class = $formatter;
                $options = [];
            }
            /** @var class-string<\Cake\Log\Formatter\AbstractFormatter> $class */
            $formatter = new $class($options);
        }

        if (!$formatter instanceof AbstractFormatter) {
            throw new InvalidArgumentException(sprintf(
                'Formatter must extend `%s`, got `%s` instead',
                AbstractFormatter::class,
                get_class($formatter)
            ));
        }
        $this->formatter = $formatter;
    }

    /**
     * Get the levels this logger is interested in.
     *
     * @return array
     */
    public function levels(): array
    {
        return $this->_config['levels'];
    }

    /**
     * Get the scopes this logger is interested in.
     *
     * @return array|null
     */
    public function scopes(): ?array
    {
        return $this->_config['scopes'];
    }

    /**
     * Resolves interpolation expressions in message string.
     *
     * @param \Stringable|string $message Interpolated message
     * @param array $context Interpolation expression values
     * @return string
     */
    protected function resolve(Stringable|string $message, array $context = []): string
    {
        $message = (string)$message;

        if (strpos($message, '{') === false && strpos($message, '}') === false) {
            return $message;
        }

        preg_match_all(
            '/(?<!' . preg_quote('\\', '/') . ')\{([a-z0-9-_]+)\}/i',
            $message,
            $matches
        );
        if (empty($matches)) {
            return $message;
        }

        $placeholders = array_intersect($matches[1], array_keys($context));
        $replacements = [];

        foreach ($placeholders as $key) {
            $value = $context[$key];

            if (is_scalar($value)) {
                $replacements['{' . $key . '}'] = (string)$value;
                continue;
            }

            if (is_array($value)) {
                $replacements['{' . $key . '}'] = json_encode($value, JSON_UNESCAPED_UNICODE);
                continue;
            }

            if ($value instanceof JsonSerializable) {
                $replacements['{' . $key . '}'] = json_encode($value, JSON_UNESCAPED_UNICODE);
                continue;
            }

            if ($value instanceof ArrayObject) {
                $replacements['{' . $key . '}'] = json_encode($value->getArrayCopy(), JSON_UNESCAPED_UNICODE);
                continue;
            }

            if ($value instanceof Serializable) {
                $replacements['{' . $key . '}'] = $value->serialize();
                continue;
            }

            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $replacements['{' . $key . '}'] = json_encode($value->toArray(), JSON_UNESCAPED_UNICODE);
                    continue;
                }

                if (method_exists($value, '__serialize')) {
                    $replacements['{' . $key . '}'] = serialize($value);
                    continue;
                }

                if ($value instanceof Stringable) {
                    $replacements['{' . $key . '}'] = (string)$value;
                    continue;
                }

                if (method_exists($value, '__debugInfo')) {
                    $replacements['{' . $key . '}'] = json_encode($value->__debugInfo(), JSON_UNESCAPED_UNICODE);
                    continue;
                }
            }

            $replacements['{' . $key . '}'] = sprintf('[unhandled value of type %s]', get_debug_type($value));
        }

        /** @psalm-suppress InvalidArgument */
        return str_replace(array_keys($replacements), $replacements, $message);
    }
}
