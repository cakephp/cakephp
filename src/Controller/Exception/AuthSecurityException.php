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
namespace Cake\Controller\Exception;

/**
 * Auth Security exception - used when SecurityComponent detects any issue with the current request
 */
class AuthSecurityException extends SecurityException
{
    /**
     * Security Exception type
     *
     * @var string
     */
    protected $_type = 'auth';
}
