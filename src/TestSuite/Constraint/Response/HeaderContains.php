<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

/**
 * HeaderContains
 *
 * @internal
 */
class HeaderContains extends HeaderEquals
{

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other)
    {
        return mb_strpos($this->response->getHeaderLine($this->headerName), $other) !== false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('is in header \'%s\'', $this->headerName);
    }
}
