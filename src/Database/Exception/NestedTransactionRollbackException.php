<?php
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
 * @since         3.4.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Exception;

use Cake\Core\Exception\Exception;

class NestedTransactionRollbackException extends Exception
{

    /**
     * Constructor
     *
     * @param string|null $message If no message is given a default meesage will be used.
     * @param int $code Status code, defaults to 500.
     * @param \Exception|null $previous the previous exception.
     */
    public function __construct($message = null, $code = 500, $previous = null)
    {
        if ($message === null) {
            $message = 'Cannot commit transaction - rollback() has been already called in the nested transaction';
        }
        parent::__construct($message, $code, $previous);
    }
}
