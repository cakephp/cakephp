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
namespace Cake\View\Exception;

use Cake\Core\Exception\Exception;

/**
 * Used when an element file cannot be found.
 */
class MissingElementException extends Exception
{

    /**
     * Message template
     *
     * @var string
     */
    protected $_messageTemplate = 'Element file "%s" is missing.';
}
