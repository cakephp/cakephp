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
namespace Cake\TestSuite\Constraint\View;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * TemplateFileEquals
 *
 * @internal
 */
class TemplateFileEquals extends Constraint
{
    /**
     * Constructor
     *
     * @param string $filename Template file name
     */
    public function __construct(protected string $filename)
    {
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected filename
     */
    protected function matches(mixed $other): bool
    {
        return str_contains($this->filename, (string)$other);
    }

    /**
     * Assertion message
     */
    public function toString(): string
    {
        return sprintf('equals template file `%s`', $this->filename);
    }
}
