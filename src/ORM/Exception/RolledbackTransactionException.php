<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.2.13
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Exception;

use Cake\Core\Exception\Exception;

/**
 * Used when a transaction was rolled back from a callback event.
 */
class RolledbackTransactionException extends Exception
{
    protected $_messageTemplate = 'The afterSave event in "%s" is aborting the transaction before the save process is done.';
}
