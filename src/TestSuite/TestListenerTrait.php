<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use Throwable;

/**
 * Implements empty default methods for PHPUnit\Framework\TestListener.
 */
trait TestListenerTrait
{
    /**
     * @inheritDoc
     */
    public function startTestSuite(TestSuite $suite): void
    {
    }

    /**
     * @inheritDoc
     */
    public function endTestSuite(TestSuite $suite): void
    {
    }

    /**
     * @inheritDoc
     */
    public function startTest(Test $test): void
    {
    }

    /**
     * @inheritDoc
     */
    public function endTest(Test $test, float $time): void
    {
    }

    /**
     * @inheritDoc
     */
    public function addSkippedTest(Test $test, Throwable $t, float $time): void
    {
    }

    /**
     * @inheritDoc
     */
    public function addError(Test $test, Throwable $t, float $time): void
    {
    }

    /**
     * @inheritDoc
     */
    public function addWarning(Test $test, Warning $e, float $time): void
    {
    }

    /**
     * @inheritDoc
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
    }

    /**
     * @inheritDoc
     */
    public function addRiskyTest(Test $test, Throwable $t, float $time): void
    {
    }

    /**
     * @inheritDoc
     */
    public function addIncompleteTest(Test $test, Throwable $t, float $time): void
    {
    }
}
