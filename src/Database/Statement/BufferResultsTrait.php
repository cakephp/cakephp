<?php
declare(strict_types=1);

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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
    public function bufferResults(bool $buffer)
    {
        $this->_bufferResults = $buffer;

        return $this;
    }
}
