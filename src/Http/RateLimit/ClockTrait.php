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
 * @since         5.4.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\RateLimit;

use Cake\I18n\DateTime;

/**
 * Reads the current time for rate limiting.
 *
 * With the `cakephp/i18n` package installed the time comes from
 * `DateTime::now()`, so `DateTime::setTestNow()` applies. Without it
 * the system clock is used.
 *
 * @internal
 */
trait ClockTrait
{
    /**
     * @return float Unix timestamp with microseconds.
     */
    protected function currentTime(): float
    {
        return class_exists(DateTime::class)
            ? (float)DateTime::now()->format('U.u')
            : microtime(true);
    }
}
