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
    private $exitCode;

    /**
     * Constructor
     *
     * @param int|null $exitCode Exit code
     */
    public function __construct(?int $exitCode)
    {
        $this->exitCode = $exitCode;
    }

    /**
     * Checks if event is in fired array
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other): bool
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
        return sprintf('matches exit code %s', $this->exitCode ?? 'null');
    }
}
