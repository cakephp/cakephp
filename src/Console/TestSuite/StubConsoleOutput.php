<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\TestSuite;

use Cake\Console\ConsoleOutput;

/**
 * StubOutput makes testing shell commands/shell helpers easier.
 *
 * You can use this class by injecting it into a ConsoleIo instance
 * that your command/task/helper uses:
 *
 * ```
 * use Cake\Console\ConsoleIo;
 * use Cake\Console\TestSuite\StubConsoleOutput;
 *
 * $output = new StubConsoleOutput();
 * $io = new ConsoleIo($output);
 * ```
 */
class StubConsoleOutput extends ConsoleOutput
{
    /**
     * Buffered messages.
     *
     * @var array<string>
     */
    protected $_out = [];

    /**
     * Write output to the buffer.
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int
     */
    public function write($message, int $newlines = 1): int
    {
        foreach ((array)$message as $line) {
            $this->_out[] = $line;
        }

        $newlines--;
        while ($newlines > 0) {
            $this->_out[] = '';
            $newlines--;
        }

        return 0;
    }

    /**
     * Get the buffered output.
     *
     * @return array<string>
     */
    public function messages(): array
    {
        return $this->_out;
    }

    /**
     * Get the output as a string
     *
     * @return string
     */
    public function output(): string
    {
        return implode("\n", $this->_out);
    }
}

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\StubConsoleOutput',
    'Cake\TestSuite\Stub\ConsoleOutput'
);
// phpcs:enable
