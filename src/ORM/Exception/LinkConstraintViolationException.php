<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Exception;

use Cake\Core\Exception\Exception;

class LinkConstraintViolationException extends Exception
{
    /**
     * {@inheritDoc}
     */
    protected $_messageTemplate =
        'Cannot modify row: a constraint for the `%s` repositories `%s` association fails';

    /**
     * Gets the affected association.
     *
     * @return string|null
     */
    public function getAssociation()
    {
        if (isset($this->_attributes['association'])) {
            return $this->_attributes['association'];
        }
        return null;
    }

    /**
     * Gets the affected repository.
     *
     * @return string|null
     */
    public function getRepository()
    {
        if (isset($this->_attributes['repository'])) {
            return $this->_attributes['repository'];
        }
        return null;
    }
}
