<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\I18n\Time;
use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\Widget\SelectBoxWidget;
use Cake\View\Widget\WidgetInterface;
use RuntimeException;

/**
 * Input widget class for generating a date time input widget.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class DateTimeWidget implements WidgetInterface
{

    /**
     * Select box widget.
     *
     * @var \Cake\View\Widget\SelectBoxWidget
     */
    protected $_select;

    /**
     * List of inputs that can be rendered
     *
     * @var array
     */
    protected $_selects = [
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
        'meridian',
        'localization',
    ];

    /**
     * Template instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * Constructor
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     * @param \Cake\View\Widget\SelectBoxWidget $selectBox Selectbox widget instance.
     */
    public function __construct(StringTemplate $templates, SelectBoxWidget $selectBox)
    {
        $this->_select = $selectBox;
        $this->_templates = $templates;
    }

    /**
     * Renders a date time widget
     *
     * - `name` - Set the input name.
     * - `disabled` - Either true or an array of options to disable.
     * - `val` - A date time string, integer or DateTime object
     * - `empty` - Set to true to add an empty option at the top of the
     *   option elements. Set to a string to define the display value of the
     *   empty option.
     *
     * In addition to the above options, the following options allow you to control
     * which input elements are generated. By setting any option to false you can disable
     * that input picker. In addition each picker allows you to set additional options
     * that are set as HTML properties on the picker.
     *
     * - `year` - Array of options for the year select box.
     * - `month` - Array of options for the month select box.
     * - `day` - Array of options for the day select box.
     * - `hour` - Array of options for the hour select box.
     * - `minute` - Array of options for the minute select box.
     * - `second` - Set to true to enable the seconds input. Defaults to false.
     * - `meridian` - Set to true to enable the meridian input. Defaults to false.
     *   The meridian will be enabled automatically if you choose a 12 hour format.
     *
     * The `year` option accepts the `start` and `end` options. These let you control
     * the year range that is generated. It defaults to +-5 years from today.
     *
     * The `month` option accepts the `name` option which allows you to get month
     * names instead of month numbers.
     *
     * The `hour` option allows you to set the following options:
     *
     * - `format` option which accepts 12 or 24, allowing
     *   you to indicate which hour format you want.
     * - `start` The hour to start the options at.
     * - `end` The hour to stop the options at.
     *
     * The start and end options are dependent on the format used. If the
     * value is out of the start/end range it will not be included.
     *
     * The `minute` option allows you to define the following options:
     *
     * - `interval` The interval to round options to.
     * - `round` Accepts `up` or `down`. Defines which direction the current value
     *   should be rounded to match the select options.
     *
     * @param array $data Data to render with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string A generated select box.
     * @throws \RuntimeException When option data is invalid.
     */
    public function render(array $data, ContextInterface $context)
    {
        $data = $this->_normalizeData($data);

        $selected = $this->_deconstructDate($data['val'], $data);
        $templateOptions = [];
        foreach ($this->_selects as $select) {
            if ($data[$select] === false || $data[$select] === null) {
                $templateOptions[$select] = '';
                unset($data[$select]);
                continue;
            }
            if (!is_array($data[$select])) {
                throw new RuntimeException(sprintf(
                    'Options for "%s" must be an array|false|null',
                    $select
                ));
            }
            $method = "_{$select}Select";
            $data[$select]['name'] = $data['name'] . "[" . $select . "]";
            $data[$select]['val'] = $selected[$select];
            $data[$select]['localization'] = $data['localization'];
            $data[$select]['localization']['alias'] = $data['name'];

            if (!isset($data[$select]['empty'])) {
                $data[$select]['empty'] = $data['empty'];
            }
            if (!isset($data[$select]['disabled'])) {
                $data[$select]['disabled'] = $data['disabled'];
            }
            $templateOptions[$select] = $this->{$method}($data[$select], $context);
            unset($data[$select]);
        }
        unset($data['name'], $data['empty'], $data['disabled'], $data['val']);
        $templateOptions['attrs'] = $this->_templates->formatAttributes($data);
        return $this->_templates->format('dateWidget', $templateOptions);
    }

    /**
     * Normalize data.
     *
     * @param array $data Data to normalize.
     * @return array Normalized data.
     */
    protected function _normalizeData($data)
    {
        $data += [
            'name' => '',
            'empty' => false,
            'disabled' => null,
            'val' => null,
            'year' => [],
            'month' => [],
            'day' => [],
            'hour' => [],
            'minute' => [],
            'second' => [],
            'meridian' => null,
            'localization' => [ 'baseLocale' => null],
            'locale' => null,
            'timezone' => null
        ];

        $timeFormat = isset($data['hour']['format']) ? $data['hour']['format'] : null;
        if ($timeFormat === 12 && !isset($data['meridian'])) {
            $data['meridian'] = [];
        }
        if ($timeFormat === 24) {
            $data['meridian'] = false;
        }
        
        $data['localization']['timezone'] = $data['timezone'];
        $data['locale'] = Time::getDefaultLocale($data['locale'], true);
        $data['localization']['locale'] = $data['localization']['baseLocale'] = $data['locale'];

        if ($data['locale'] !== null) {
            $data['locale'] = preg_split('/@calendar=/', $data['locale']);
            $locale = (isset($data['locale'][1]))? '@calendar=' . $data['locale'][1] : '';
            $data['localization']['baseLocale'] = 'en_' . locale_get_region($data['locale'][0]) . $locale;
        } else {
            $data['localization']['baseLocale'] = 'en';
        }

        unset($locale, $data['locale'], $data['timezone']);
        return $data;
    }

    /**
     * Deconstructs the passed date value into all time units
     *
     * @param string|int|array|\DateTime|null $value Value to deconstruct.
     * @param array $options Options for conversion.
     * @return array
     */
    protected function _deconstructDate($value, $options)
    {
        if ($value === '' || $value === null) {
            return [
                'year' => '', 'month' => '', 'day' => '',
                'hour' => '', 'minute' => '', 'second' => '',
                'meridian' => '', 'localization' => '',
            ];
        }
        try {
            if (is_string($value)) {
                $date = new Time($value);
            } elseif (is_bool($value)) {
                $date = new Time();
            } elseif (is_int($value)) {
                $date = new Time('@' . $value);
            } elseif (is_array($value)) {
                $dateArray = [
                    'year' => '', 'month' => '', 'day' => '',
                    'hour' => '', 'minute' => '', 'second' => '',
                    'meridian' => 'pm', 'localization' => '',
                ];
                $validDate = false;
                foreach ($dateArray as $key => $dateValue) {
                    $exists = isset($value[$key]);
                    if ($exists) {
                        $validDate = true;
                    }
                    if ($exists && $value[$key] !== '') {
                        $dateArray[$key] = str_pad($value[$key], 2, '0', STR_PAD_LEFT);
                    }
                }
                if ($validDate) {
                    if (!isset($dateArray['second'])) {
                        $dateArray['second'] = 0;
                    }
                    if (isset($value['meridian'])) {
                        $isAm = strtolower($dateArray['meridian']) === 'am';
                        $dateArray['hour'] = $isAm ? $dateArray['hour'] : $dateArray['hour'] + 12;
                    }
                    if (!empty($dateArray['minute']) && isset($options['minute']['interval'])) {
                        $dateArray['minute'] += $this->_adjustValue($dateArray['minute'], $options['minute']);
                        $dateArray['minute'] = str_pad(strval($dateArray['minute']), 2, '0', STR_PAD_LEFT);
                    }

                    return $dateArray;
                }

                $date = new Time();
            } else {
                $date = clone $value;
            }
        } catch (\Exception $e) {
            $date = new Time();
        }
        $locale = $options['localization']['baseLocale'];
        $tz = $options['localization']['timezone'];

        if (isset($options['minute']['interval'])) {
            $change = $this->_adjustValue($date->I18nFormat('mm', $tz, $locale), $options['minute']);
            $date->modify($change > 0 ? "+$change minutes" : "$change minutes");
        }

        return [
            'year' => $date->I18nFormat('yyyy', $tz, $locale),
            'month' => $date->I18nFormat('MM', $tz, $locale),
            'day' => $date->I18nFormat('dd', $tz, $locale),
            'hour' => $date->I18nFormat('HH', $tz, $locale),
            'minute' => $date->I18nFormat('mm', $tz, $locale),
            'second' => $date->I18nFormat('ss', $tz, $locale),
            'meridian' => strtolower($date->I18nFormat('a', $tz, $locale)),
            'localization' => null
        ];
    }

    /**
     * Adjust $value based on rounding settings.
     *
     * @param int $value The value to adjust.
     * @param array $options The options containing interval and possibly round.
     * @return int The amount to adjust $value by.
     */
    protected function _adjustValue($value, $options)
    {
        $options += ['interval' => 1, 'round' => null];
        $changeValue = $value * (1 / $options['interval']);
        switch ($options['round']) {
            case 'up':
                $changeValue = ceil($changeValue);
                break;
            case 'down':
                $changeValue = floor($changeValue);
                break;
            default:
                $changeValue = round($changeValue);
        }
        return ($changeValue * $options['interval']) - $value;
    }

    /**
     * Generates a year select
     *
     * @param array $options Options list.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _yearSelect($options, $context)
    {
        $date = new Time(null, $options['localization']['timezone']);

        if (is_string($options['val'] && !empty($options['val']))) {
            $date->year($options['val']);
        }

        $options += [
            'name' => '',
            'val' => null,
            'start' => $date->subYears(5)->I18nFormat("yyyy", null, $options['localization']['baseLocale']),
            'end' => $date->addYears(10)->I18nFormat("yyyy", null, $options['localization']['baseLocale']),
            'order' => 'desc',
            'options' => []
        ];

        if (!empty($options['val'])) {
            $options['start'] = min($options['val'], $options['start']);
            $options['end'] = max($options['val'], $options['end']);
        }
        if (empty($options['options'])) {
            $options['options'] = $this->_generateNumbers($options['start'], $options['end'], $options);
        }
        if ($options['order'] === 'desc') {
            $options['options'] = array_reverse($options['options'], true);
        }
        
        unset(
            $options['start'], $options['end'],
            $options['order'], $options['localization']
        );
        
        return $this->_select->render($options, $context);
    }

    /**
     * Generates a month select
     *
     * @param array $options The options to build the month select with
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _monthSelect($options, $context)
    {
        $options += [
            'name' => '',
            'names' => false,
            'val' => null,
            'leadingZeroKey' => true,
            'leadingZeroValue' => false
        ];

        if (empty($options['options'])) {
            if ($options['names'] === true) {
                $options['options'] = $this->_getMonthNames($options['leadingZeroKey'], $options);
            } elseif (is_array($options['names'])) {
                $options['options'] = $options['names'];
            } else {
                $options['options'] = $this->_generateNumbers(1, 12, $options);
            }
        }

        unset(
            $options['leadingZeroKey'], $options['leadingZeroValue'],
            $options['names'], $options['localization']
        );
        return $this->_select->render($options, $context);
    }

    /**
     * Generates a day select
     *
     * @param array $options The options to generate a day select with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _daySelect($options, $context)
    {
        $options += [
            'name' => '',
            'val' => null,
            'leadingZeroKey' => true,
            'leadingZeroValue' => false,
        ];
        $options['options'] = $this->_generateNumbers(1, 31, $options);

        unset(
            $options['names'], $options['leadingZeroKey'],
            $options['leadingZeroValue'], $options['localization']
        );
        return $this->_select->render($options, $context);
    }

    /**
     * Generates a hour select
     *
     * @param array $options The options to generate an hour select with
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _hourSelect($options, $context)
    {
        $options += [
            'name' => '',
            'val' => null,
            'format' => 24,
            'start' => null,
            'end' => null,
            'leadingZeroKey' => true,
            'leadingZeroValue' => false,
        ];
        $is24 = $options['format'] == 24;

        $defaultStart = $is24 ? 0 : 1;
        $defaultEnd = $is24 ? 23 : 12;
        $options['start'] = max($defaultStart, $options['start']);

        $options['end'] = min($defaultEnd, $options['end']);
        if ($options['end'] === null) {
            $options['end'] = $defaultEnd;
        }

        if (!$is24 && $options['val'] > 12) {
            $options['val'] = sprintf('%02d', $options['val'] - 12);
        }
        if (!$is24 && in_array($options['val'], ['00', '0', 0], true)) {
            $options['val'] = 12;
        }

        if (empty($options['options'])) {
            $options['options'] = $this->_generateNumbers(
                $options['start'],
                $options['end'],
                $options
            );
        }

        unset(
            $options['end'], $options['start'],
            $options['format'], $options['leadingZeroKey'],
            $options['leadingZeroValue'], $options['localization']
        );
        return $this->_select->render($options, $context);
    }

    /**
     * Generates a minute select
     *
     * @param array $options The options to generate a minute select with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _minuteSelect($options, $context)
    {
        $options += [
            'name' => '',
            'val' => null,
            'interval' => 1,
            'round' => 'up',
            'leadingZeroKey' => true,
            'leadingZeroValue' => true,
        ];
        $options['interval'] = max($options['interval'], 1);
        if (empty($options['options'])) {
            $options['options'] = $this->_generateNumbers(0, 59, $options);
        }

        unset(
            $options['leadingZeroKey'], $options['leadingZeroValue'],
            $options['interval'], $options['round'],
            $options['localization']
        );
        return $this->_select->render($options, $context);
    }

    /**
     * Generates a second select
     *
     * @param array $options The options to generate a second select with
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _secondSelect($options, $context)
    {
        $options += [
            'name' => '',
            'val' => null,
            'leadingZeroKey' => true,
            'leadingZeroValue' => true,
            'options' => $this->_generateNumbers(0, 59, $options)
        ];

        unset(
            $options['leadingZeroKey'], $options['leadingZeroValue'],
            $options['localization']
        );
        return $this->_select->render($options, $context);
    }

    /**
     * Generates a meridian select
     *
     * @param array $options The options to generate a meridian select with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _meridianSelect($options, $context)
    {
        $date = new Time('09:00:00', $options['localization']['timezone']);
        $options += [
            'name' => '',
            'val' => null,
            'options' => [
                'am' => $date->i18nFormat('a'),
                'pm' => $date->addHours(12)->i18nFormat('a')
            ]
        ];

        unset($options['localization']);
        return $this->_select->render($options, $context);
    }

    /**
     * Generates a localization hidden input
     *
     * @param array $options The options to generate a hidden input
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    protected function _localizationSelect($options, $context)
    {
        $out = '';
        $template = '<input name="%s[%s]" value="%s" type="hidden">';
        
        if ($options['localization']['locale'] !== null) {
            $out = sprintf($template, $options['localization']['alias'], 'locale', $options['localization']['locale']);
        }
        
        if ($options['localization']['timezone'] !== null) {
            $out = $out . sprintf($template, $options['localization']['alias'], 'timezone', $options['localization']['timezone']);
        }

        return $out;
    }

    /**
     * Returns a translated list of month names
     *
     * @param bool $leadingZero Whether to generate month keys with leading zero.
     * @param array $options The options to generate a month names.
     * @return array
     */
    protected function _getMonthNames($leadingZero = false, $options = null)
    {
        $date = new Time(null, $options['localization']['timezone']);
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $date->month($i);
            $key = $date->i18nFormat("MM", null, $options['localization']['baseLocale']);
            $val = $date->i18nFormat("MMMM", null, $options['localization']['locale']);
            $months[$key] = trim($val);
        }
        
        unset($date);
        ksort($months);

        if ($leadingZero === false) {
            $i = 1;
            foreach ($months as $key => $name) {
                unset($months[$key]);
                $months[$i++] = $name;
            }
        }

        return $months;
    }

    /**
     * Generates a range of numbers
     *
     * ### Options
     *
     * - leadingZeroKey - Set to true to add a leading 0 to single digit keys.
     * - leadingZeroValue - Set to true to add a leading 0 to single digit values.
     * - interval - The interval to generate numbers for. Defaults to 1.
     *
     * @param int $start Start of the range of numbers to generate
     * @param int $end End of the range of numbers to generate
     * @param array $options Options list.
     * @return array
     */
    protected function _generateNumbers($start, $end, $options = [])
    {
        $options += [
            'leadingZeroKey' => true,
            'leadingZeroValue' => true,
            'interval' => 1
        ];

        $numbers = [];
        $i = $start;
        while ($i <= $end) {
            $key = (string)$i;
            $value = (string)$i;
            if ($options['leadingZeroKey'] === true) {
                $key = sprintf('%02d', $key);
            }
            if ($options['leadingZeroValue'] === true) {
                $value = sprintf('%02d', $value);
            }
            
            if ($options['localization']['locale'] !== null) {
                $value = \Cake\I18n\Number::format($value, ['locale' => $options['localization']['locale'], 'pattern' => '####']);
            }
            $numbers[$key] = $value;
            $i += $options['interval'];
        }

        return $numbers;
    }

    /**
     * Returns a list of fields that need to be secured for this widget.
     *
     * When the hour picker is in 24hr mode (null or format=24) the meridian
     * picker will be omitted.
     *
     * @param array $data The data to render.
     * @return array Array of fields to secure.
     */
    public function secureFields(array $data)
    {
        $data = $this->_normalizeData($data);

        $fields = [];
        foreach ($this->_selects as $select) {
            if ($data[$select] === false || $data[$select] === null) {
                continue;
            }

            $fields[] = $data['name'] . '[' . $select . ']';
        }
        return $fields;
    }
}
