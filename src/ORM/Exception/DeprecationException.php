<?php
declare(strict_types=1);

namespace Cake\ORM\Exception;

use Cake\Core\Exception\CakeException;

class DeprecationException extends CakeException
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'Deprecated: %s';
}
