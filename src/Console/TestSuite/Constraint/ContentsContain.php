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

/**
 * ContentsContain
 *
 * @internal
 */
class ContentsContain extends ContentsBase
{
    /**
     * Checks if contents contain expected
     *
     * @param mixed $other Expected
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        return mb_strpos($this->contents, $other) !== false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('is in %s.', $this->output);
    }

    /**
     * @inheritDoc
     */
    protected function additionalFailureDescription(mixed $other): string
    {
        return sprintf("actual result:\n%s", $this->contents);
    }
}

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\Constraint\ContentsContain',
    'Cake\TestSuite\Constraint\Console\ContentsContain'
);
// phpcs:enable
