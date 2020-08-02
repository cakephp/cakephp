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
use DateTimeImmutable;
use JsonSerializable;
use Psr\Log\AbstractLogger;
use Serializable;

/**
 * Base log engine class.
 */
abstract class BaseLog extends AbstractLogger
{
    use InstanceConfigTrait;

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'levels' => [],
        'scopes' => [],
        'dateFormat' => 'Y-m-d H:i:s',
    ];

    /**
     * __construct method
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        if (!is_array($this->_config['scopes']) && $this->_config['scopes'] !== false) {
            $this->_config['scopes'] = (array)$this->_config['scopes'];
        }

        if (!is_array($this->_config['levels'])) {
            $this->_config['levels'] = (array)$this->_config['levels'];
        }

        if (!empty($this->_config['types']) && empty($this->_config['levels'])) {
            $this->_config['levels'] = (array)$this->_config['types'];
        }
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
     * @return array|false
     */
    public function scopes()
    {
        return $this->_config['scopes'];
    }

    /**
     * Formats the message to be logged.
     *
     * The context can optionally be used by log engines to interpolate variables
     * or add additional info to the logged message.
     *
     * @param string $message The message to be formatted.
     * @param array $context Additional logging information for the message.
     * @return string
     */
    protected function _format(string $message, array $context = []): string
    {
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
                if (method_exists($value, '__toString')) {
                    $replacements['{' . $key . '}'] = (string)$value;
                    continue;
                }

                if (method_exists($value, 'toArray')) {
                    $replacements['{' . $key . '}'] = json_encode($value->toArray(), JSON_UNESCAPED_UNICODE);
                    continue;
                }

                if (method_exists($value, '__debugInfo')) {
                    $replacements['{' . $key . '}'] = json_encode($value->__debugInfo(), JSON_UNESCAPED_UNICODE);
                    continue;
                }
            }

            $replacements['{' . $key . '}'] = sprintf('[unhandled value of type %s]', getTypeName($value));
        }

        /** @psalm-suppress InvalidArgument */
        return str_replace(array_keys($replacements), $replacements, $message);
    }

    /**
     * Returns date formatted according to given `dateFormat` option format.
     *
     * This function affects `FileLog` or` ConsoleLog` datetime information format.
     *
     * @return string
     */
    protected function _getFormattedDate(): string
    {
        return (new DateTimeImmutable())->format($this->_config['dateFormat']);
    }
}
