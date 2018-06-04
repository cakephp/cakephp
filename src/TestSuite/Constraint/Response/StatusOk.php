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
 * StatusOk
 *
 * @internal
 */
class StatusOk extends StatusCodeBase
{

    protected $code = [200, 204];

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('%d is between 200 and 204', $this->response->getStatusCode());
    }
}
