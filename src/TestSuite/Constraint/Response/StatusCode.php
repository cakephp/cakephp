<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

/**
 * StatusCode
 *
 * @internal
 */
class StatusCode extends StatusCodeBase
{
    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('matches response status code `%d`', $this->response->getStatusCode());
    }

    /**
     * Failure description
     *
     * @param mixed $other Expected code
     * @return string
     */
    public function failureDescription($other): string
    {
        return '`' . $other . '` ' . $this->toString();
    }
}
