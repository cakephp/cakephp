<?php
declare(strict_types=1);

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
namespace Cake\Console\TestSuite\Constraint;

/**
 * ContentsContainRow
 *
 * @internal
 */
class ContentsContainRow extends ContentsRegExp
{
    /**
     * Checks if contents contain expected
     *
     * @param array $other Row
     * @return bool
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function matches($other): bool
    {
        $row = array_map(function ($cell) {
            return preg_quote($cell, '/');
        }, $other);
        $cells = implode('\s+\|\s+', $row);
        $pattern = '/' . $cells . '/';

        return preg_match($pattern, $this->contents) > 0;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('row was in %s', $this->output);
    }

    /**
     * @param mixed $other Expected content
     * @return string
     */
    public function failureDescription($other): string
    {
        return '`' . $this->exporter()->shortenedExport($other) . '` ' . $this->toString();
    }
}
