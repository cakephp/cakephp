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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\ConsoleException;

/**
 * Object wrapper for interacting with stdin
 */
class ConsoleInput
{
    /**
     * Input value.
     *
     * @var resource
     */
    protected $_input;

    /**
     * Can this instance use readline?
     * Two conditions must be met:
     * 1. Readline support must be enabled.
     * 2. Handle we are attached to must be stdin.
     * Allows rich editing with arrow keys and history when inputting a string.
     *
     * @var bool
     */
    protected $_canReadline;

    /**
     * Constructor
     *
     * @param string $handle The location of the stream to use as input.
     */
    public function __construct(string $handle = 'php://stdin')
    {
        $this->_canReadline = (extension_loaded('readline') && $handle === 'php://stdin');
        $this->_input = fopen($handle, 'rb');
    }

    /**
     * Read a value from the stream
     *
     * @return string|null The value of the stream. Null on EOF.
     */
    public function read(): ?string
    {
        if ($this->_canReadline) {
            $line = readline('');

            if ($line !== false && strlen($line) > 0) {
                readline_add_history($line);
            }
        } else {
            $line = fgets($this->_input);
        }

        if ($line === false) {
            return null;
        }

        return $line;
    }

    /**
     * Check if data is available on stdin
     *
     * @param int $timeout An optional time to wait for data
     * @return bool True for data available, false otherwise
     */
    public function dataAvailable(int $timeout = 0): bool
    {
        $readFds = [$this->_input];
        $writeFds = null;
        $errorFds = null;

        /** @var string|null $error */
        $error = null;
        set_error_handler(function (int $code, string $message) use (&$error) {
            $error = "stream_select failed with code={$code} message={$message}.";
        });
        $readyFds = stream_select($readFds, $writeFds, $errorFds, $timeout);
        restore_error_handler();
        if ($error !== null) {
            throw new ConsoleException($error);
        }

        return $readyFds > 0;
    }
}
