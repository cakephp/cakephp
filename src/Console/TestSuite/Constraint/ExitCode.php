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

use PHPUnit\Framework\Constraint\Constraint;

/**
 * ExitCode constraint
 *
 * @internal
 */
class ExitCode extends Constraint
{
    /**
     * Constructor
     *
     * @param int|null $exitCode Exit code
     */
    public function __construct(private readonly ?int $exitCode)
    {
    }

    /**
     * Checks if event is in fired array
     *
     * @param mixed $other Constraint check
     */
    protected function matches(mixed $other): bool
    {
        return $other === $this->exitCode;
    }

    /**
     * Assertion message string
     */
    public function toString(): string
    {
        return sprintf('matches exit code `%s`', $this->exitCode ?? 'null');
    }

    /**
     * Returns the description of the failure.
     *
     * @param mixed $other Expected
     */
    protected function failureDescription(mixed $other): string
    {
        return '`' . $other . '` ' . $this->toString();
    }
}

// phpcs:disable
class_alias(
    \Cake\Console\TestSuite\Constraint\ExitCode::class,
    'Cake\TestSuite\Constraint\Console\ExitCode'
);
// phpcs:enable
