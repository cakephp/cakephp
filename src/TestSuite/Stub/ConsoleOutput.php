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
 */
class ConsoleOutput extends ConsoleOutputBase
{
    protected $_out = [];

    public function write($message, $newlines = 1)
    {
        $this->_out[] = $message;
    }

    public function messages()
    {
        return $this->_out;
    }
}
