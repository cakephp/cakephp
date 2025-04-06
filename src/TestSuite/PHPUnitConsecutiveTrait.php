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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;

trait PHPUnitConsecutiveTrait
{
    /**
     * @param array $firstCallArguments The call arguments
     * @param array ...$consecutiveCallsArguments Additional arguments
     * @return iterable
     */
    public static function withConsecutive(array $firstCallArguments, array ...$consecutiveCallsArguments): iterable
    {
        $allConsecutiveCallsArguments = [$firstCallArguments, ...$consecutiveCallsArguments];

        $numberOfArguments = count($firstCallArguments);
        $argumentList = [];
        for ($argumentPosition = 0; $argumentPosition < $numberOfArguments; $argumentPosition++) {
            $argumentList[$argumentPosition] = array_column($allConsecutiveCallsArguments, $argumentPosition);
        }

        $mockedMethodCall = 0;
        $callbackCall = 0;
        foreach ($argumentList as $index => $argument) {
            yield new Callback(
                static function (mixed $actualArgument) use (
                    $argumentList,
                    &$mockedMethodCall,
                    &$callbackCall,
                    $index,
                    $numberOfArguments,
                ): bool {
                    $expected = $argumentList[$index][$mockedMethodCall] ?? null;

                    $callbackCall++;
                    $mockedMethodCall = (int)($callbackCall / $numberOfArguments);

                    if ($expected instanceof Constraint) {
                        self::assertThat($actualArgument, $expected);
                    } else {
                        self::assertEquals($expected, $actualArgument);
                    }

                    return true;
                },
            );
        }
    }
}
