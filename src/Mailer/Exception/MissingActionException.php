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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer\Exception;

use Cake\Core\Exception\Exception;

/**
 * Missing Action exception - used when a mailer action cannot be found.
 */
class MissingActionException extends Exception
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'Mail %s::%s() could not be found, or is not accessible.';

    /**
     * @inheritDoc
     */
    protected $_defaultCode = 404;
}
