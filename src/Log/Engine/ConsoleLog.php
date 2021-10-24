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

use Cake\Console\ConsoleOutput;
use Cake\Log\Formatter\DefaultFormatter;
use InvalidArgumentException;

/**
 * Console logging. Writes logs to console output.
 */
class ConsoleLog extends BaseLog
{
    /**
     * Default config for this class
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'stream' => 'php://stderr',
        'levels' => null,
        'scopes' => [],
        'outputAs' => null,
        'formatter' => [
            'className' => DefaultFormatter::class,
            'includeTags' => true,
        ],
    ];

    /**
     * Output stream
     *
     * @var \Cake\Console\ConsoleOutput
     */
    protected $_output;

    /**
     * Constructs a new Console Logger.
     *
     * Config
     *
     * - `levels` string or array, levels the engine is interested in
     * - `scopes` string or array, scopes the engine is interested in
     * - `stream` the path to save logs on.
     * - `outputAs` integer or ConsoleOutput::[RAW|PLAIN|COLOR]
     * - `dateFormat` PHP date() format.
     *
     * @param array<string, mixed> $config Options for the FileLog, see above.
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $config = $this->_config;
        if ($config['stream'] instanceof ConsoleOutput) {
            $this->_output = $config['stream'];
        } elseif (is_string($config['stream'])) {
            $this->_output = new ConsoleOutput($config['stream']);
        } else {
            throw new InvalidArgumentException('`stream` not a ConsoleOutput nor string');
        }

        if (isset($config['outputAs'])) {
            $this->_output->setOutputAs($config['outputAs']);
        }

        if (isset($this->_config['dateFormat'])) {
            deprecationWarning('`dateFormat` option should now be set in the formatter options.', 0);
            $this->formatter->setConfig('dateFormat', $this->_config['dateFormat']);
        }
    }

    /**
     * Implements writing to console.
     *
     * @param mixed $level The severity level of log you are making.
     * @param string $message The message you want to log.
     * @param array $context Additional information about the logged message
     * @return void success of write.
     * @see \Cake\Log\Log::$_levels
     */
    public function log($level, $message, array $context = [])
    {
        $message = $this->_format($message, $context);
        $this->_output->write($this->formatter->format($level, $message, $context));
    }
}
