<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * @param string $message The message to write.
     * @param int $newlines Unused.
     * @return void
     */
    public function write($message, $newlines = 1)
    {
        $this->_out[] = $message;
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
