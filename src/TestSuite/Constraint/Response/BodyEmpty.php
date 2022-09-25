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
 * BodyEmpty
 *
 * @internal
 */
class BodyEmpty extends ResponseBase
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected type
     * @return bool
     */
    public function matches($other): bool
    {
        return empty($this->_getBodyAsString());
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return 'response body is empty';
    }

    /**
     * Overwrites the descriptions so we can remove the automatic "expected" message
     *
     * @param mixed $other Value
     * @return string
     */
    protected function failureDescription($other): string
    {
        return $this->toString();
    }
}
