<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Exception;

use Cake\Core\Exception\Exception;

/**
 * Exception class for Xml. This exception will be thrown from Xml when it
 * encounters an error.
 */
class XmlException extends Exception
{

    /**
     * {@inheritDoc}
     */
    protected $_defaultCode = 0;
}
