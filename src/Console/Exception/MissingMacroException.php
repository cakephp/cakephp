<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Exception;

use Cake\Core\Exception\Exception;

/**
 * Used when a Macro cannot be found.
 *
 */
class MissingMacroException extends Exception
{

    protected $_messageTemplate = 'Macro class %s could not be found.';
}
