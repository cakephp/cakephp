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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use UnexpectedValueException;

/**
 * TODO: Add documentation
 */
class DateInterval extends \DateInterval
{
    /**
     * @var array
     */
    private $intervalAry = [];

    /**
     * Microseconds in a second.
     *
     * @var int
     */
    private const MICROSECONDS_IN_SECOND = 1000000;

    /**
     * SQL unit values for array conversion.
     *
     * @var string[]
     */
    private const KEYS_TRANSFORM = [
        'y' => 'YEAR',
        'm' => 'MONTH',
        'd' => 'DAY',
        'h' => 'HOUR',
        'i' => 'MINUTE',
        's' => 'SECOND',
        'f' => 'MICROSECOND',
    ];

    /**
     * DateInterval keys that indicate the interval is special or relative.
     *
     * @var string[]
     */
    private const SPECIAL_KEYS = ['have_special_relative', 'have_weekday_relative', 'special_type'];

    /**
     * Constructor that takes an interval spec and an optional clone object. If the clone object is provided,
     * the properties from the clone object are applied to the new object.
     *
     * @param string $interval_spec A string containing an interval specification.
     * @param \DateInterval $clone Copy values from another DateInterval object.
     * @throws \Exception
     */
    public function __construct($interval_spec, ?\DateInterval $clone = null)
    {
        parent::__construct($interval_spec);
        if ($clone !== null) {
            if (static::isSpecial($clone)) {
                throw new UnexpectedValueException('Cloned interval object cannot be a special or relative format.');
            }
            foreach ((array)$clone as $key => $value) {
                $this->$key = $value;
            }
        }
        $this->parse();
    }

    /**
     * Converts a \DateInterval object to a \Cake\Database\DateInterval object.
     *
     * @param \DateInterval $di Interval object to convert to a database interval object.
     * @return \Cake\Database\DateInterval The converted interval object.
     * @throws \Exception
     */
    public static function convertFromDateInterval(\DateInterval $di): DateInterval
    {
        if ($di instanceof DateInterval) {
            return $di;
        }

        return new DateInterval('P0Y', $di);
    }

    /**
     * Returns true if the interval object is special or relative.
     *
     * @param \DateInterval $di The interval object to check.
     * @return bool
     */
    private static function isSpecial(\DateInterval $di): bool
    {
        return array_sum(array_intersect_key((array)$di, array_flip(static::SPECIAL_KEYS))) == true;
    }

    /**
     * Method accepts an interval object and converts to an array and applies
     * the SQL transform values. For instance, the 'y' key becomes 'YEAR', 'm'
     * becomes 'MONTH', 'h' becomes 'HOUR', etc.
     *
     * @throws \UnexpectedValueException
     * @return $this
     */
    private function parse()
    {
        $diAry = (array)$this;
        $newDiAry = [];
        $sum = 0.0;
        foreach (static::KEYS_TRANSFORM as $key => $newKey) {
            if (!isset($diAry[$key])) {
                $newDiAry[$newKey] = 0;
                continue;
            }
            $newDiAry[$newKey] = ($diAry['invert'] ? -1 : 1) * $diAry[$key];
            $sum += $newDiAry[$newKey];
        }
        $newDiAry['MICROSECOND'] *= static::MICROSECONDS_IN_SECOND;
        if ($sum === 0.0) {
            throw new UnexpectedValueException(
                'Interval needs to be greater than zero. Relative intervals are not supported.'
            );
        }
        $this->intervalAry = $newDiAry;

        return $this;
    }

    /**
     * Returns the parsed interval object as an array and allows the caller to
     * determine if the microseconds are combined.
     *
     * @param bool $combineMicro Indicates whether or not to combine the microseconds with the seconds.
     * @return array
     */
    public function getParsed($combineMicro = false): array
    {
        $intervalAry = $this->intervalAry;
        if ($combineMicro) {
            $intervalAry['SECOND'] += $intervalAry['MICROSECOND'] / static::MICROSECONDS_IN_SECOND;
            unset($intervalAry['MICROSECOND']);
        }

        return $intervalAry;
    }
}
