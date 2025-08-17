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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Chronos\ChronosDatePeriod;

/**
 * DatePeriod wrapper that returns DateTime instances.
 *
 * @template TKey int
 * @template TValue \Cake\I18n\DateTime
 * @template-extends \Cake\Chronos\ChronosDatePeriod<int, \Cake\I18n\Date>
 */
class DatePeriod extends ChronosDatePeriod
{
    /**
     * @return \Cake\I18n\Date
     */
    public function current(): Date
    {
        return new Date($this->iterator->current());
    }
}
