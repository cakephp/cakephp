<?php
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
namespace Cake\TestSuite\Stub;

use Cake\Console\ConsoleOutput as ConsoleOutputBase;

/**
 * StubOutput makes testing shell commands/shell helpers easier.
 *
 * You can use this class by injecting it into a ConsoleIo instance
 * that your command/task/helper uses:
 *
 * ```
 * use Cake\Console\ConsoleIo;
 * use Cake\TestSuite\Stub\ConsoleOutput;
 *
 * $output = new ConsoleOutput();
 * $io = new ConsoleIo($output);
 * ```
 */
class ConsoleOutput extends ConsoleOutputBase
{
    /**
     * Buffered messages.
     *
     * @var array
     */
    protected $_out = [];

    /**
     * Write output to the buffer.
     *
     * @param string|string[] $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return void
     */
    public function write($message, $newlines = 1)
    {
        foreach ((array)$message as $line) {
            $this->_out[] = $line;
        }

        $newlines--;
        while ($newlines > 0) {
            $this->_out[] = '';
            $newlines--;
        }
    }

    /**
     * Get the buffered output.
     *
     * @return array
     */
    public function messages()
    {
        return $this->_out;
    }
}
