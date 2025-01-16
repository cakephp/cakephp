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
     * @var int|null
     */
    private ?int $exitCode = null;

    /**
     * @var array $out
     */
    private array $out;

    /**
     * @var array $err
     */
    private array $err;

    /**
     * Constructor
     *
     * @param int|null $exitCode Exit code
     * @param array $out stdout stream
     * @param array $err stderr stream
     */
    public function __construct(?int $exitCode, array $out, array $err)
    {
        $this->exitCode = $exitCode;
        $this->out = $out;
        $this->err = $err;
    }

    /**
     * Checks if event is in fired array
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        return $other === $this->exitCode;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('matches exit code `%s`', $this->exitCode ?? 'null');
    }

    /**
     * Returns the description of the failure.
     *
     * @param mixed $other Expected
     * @return string
     */
    public function failureDescription(mixed $other): string
    {
        return '`' . $other . '` ' . $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function additionalFailureDescription(mixed $other): string
    {
        return sprintf(
            "STDOUT\n%s\n\nSTDERR\n%s\n",
            implode("\n", $this->out),
            implode("\n", $this->err),
        );
    }
}

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\Constraint\ExitCode',
    'Cake\TestSuite\Constraint\Console\ExitCode'
);
// phpcs:enable
