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
namespace Cake\Console\TestSuite\Constraint;

use SebastianBergmann\Exporter\Exporter;

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
     * @param mixed $other Row
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $row = array_map(function ($cell) {
            return preg_quote($cell, '/');
        }, (array)$other);
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
    public function failureDescription(mixed $other): string
    {
        return '`' . (new Exporter())->shortenedExport($other) . '` ' . $this->toString();
    }
}

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\Constraint\ContentsContainRow',
    'Cake\TestSuite\Constraint\Console\ContentsContainRow'
);
// phpcs:enable
