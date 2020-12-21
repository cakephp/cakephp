<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Exception raised when requested page number does not exist.
 */
class PageOutOfBoundsException extends CakeException
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'Page number %s could not be found.';
}
