<?php
declare(strict_types=1);
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
namespace Cake\Routing\Exception;

use Cake\Core\Exception\Exception;
use Throwable;

/**
 * Missing Controller exception - used when a controller
 * cannot be found.
 */
class MissingControllerException extends Exception
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'Controller class %s could not be found.';

    /**
     * @inheritDoc
     */
    public function __construct($message, ?int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
