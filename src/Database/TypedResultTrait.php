<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Implements the TypedResultInterface
 */
trait TypedResultTrait
{

    /**
     * The type name this expression will return when executed
     *
     * @var string
     */
    protected $_returnType = 'string';

    /**
     * Gets the type of the value this object will generate.
     *
     * @return string
     */
    public function getReturnType()
    {
        return $this->_returnType;
    }

    /**
     * Sets the type of the value this object will generate.
     *
     * @param string $type The name of the type that is to be returned
     * @return $this
     */
    public function setReturnType($type)
    {
        $this->_returnType = $type;

        return $this;
    }

    /**
     * Sets the type of the value this object will generate.
     * If called without arguments, returns the current known type
     *
     * @deprecated 3.5.0 Use getReturnType()/setReturnType() instead.
     * @param string|null $type The name of the type that is to be returned
     * @return string|$this
     */
    public function returnType($type = null)
    {
        if ($type !== null) {
            $this->_returnType = $type;

            return $this;
        }

        return $this->_returnType;
    }
}
