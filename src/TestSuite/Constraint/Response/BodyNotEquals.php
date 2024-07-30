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
 * BodyNotEquals
 *
 * @internal
 */
class BodyNotEquals extends BodyEquals
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected type
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    protected function matches($other): bool
    {
        return parent::matches($other) === false;
    }

    /**
     * Assertion message
     */
    public function toString(): string
    {
        return 'does not match response body';
    }
}
