<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Statement;

/**
 * Contains a setter for marking a Statement as buffered
 *
 * @internal
 */
trait BufferResultsTrait
{

    /**
     * Whether or not to buffer results in php
     *
     * @var bool
     */
    protected $_bufferResults = true;

    /**
     * Whether or not to buffer results in php
     *
     * @param bool $buffer Toggle buffering
     * @return $this
     */
    public function bufferResults($buffer)
    {
        $this->_bufferResults = (bool)$buffer;
        return $this;
    }
}
