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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\Database\Schema\TableSchema;
use Cake\View\Form\ContextInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * Input widget class for generating a date time input widget.
 *
 * This class is usually used internally by `Cake\View\Helper\FormHelper`,
 * it but can be used to generate standalone date time inputs.
 */
class DateTimeWidget extends BasicWidget
{
    /**
     * Template instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected $defaults = [
        'name' => '',
        'val' => null,
        'type' => 'datetime-local',
        'escape' => true,
        'timezone' => null,
        'templateVars' => [],
    ];

    /**
     * Formats for various input types.
     *
     * @var array<string>
     */
    protected $formatMap = [
        'datetime-local' => 'Y-m-d\TH:i:s',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'month' => 'Y-m',
        'week' => 'Y-\WW',
    ];

    /**
     * Step size for various input types.
     *
     * If not set, defaults to browser default.
     *
     * @var array<string, mixed>
     */
    protected $defaultStep = [
        'datetime-local' => '1',
        'date' => null,
        'time' => '1',
        'month' => null,
        'week' => null,
    ];

    /**
     * Render a date / time form widget.
     *
     * Data supports the following keys:
     *
     * - `name` The name attribute.
     * - `val` The value attribute.
     * - `escape` Set to false to disable escaping on all attributes.
     * - `type` A valid HTML date/time input type. Defaults to "datetime-local".
     * - `timezone` The timezone the input value should be converted to.
     * - `step` The "step" attribute. Defaults to `1` for "time" and "datetime-local" type inputs.
     *   You can set it to `null` or `false` to prevent explicit step attribute being added in HTML.
     * - `format` A `date()` function compatible datetime format string.
     *   By default, the widget will use a suitable format based on the input type and
     *   database type for the context. If an explicit format is provided, then no
     *   default value will be set for the `step` attribute, and it needs to be
     *   explicitly set if required.
     *
     * All other keys will be converted into HTML attributes.
     *
     * @param array<string, mixed> $data The data to build a file input with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string HTML elements.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += $this->mergeDefaults($data, $context);

        if (!isset($this->formatMap[$data['type']])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid type `%s` for input tag, expected datetime-local, date, time, month or week',
                $data['type']
            ));
        }

        $data = $this->setStep($data, $context, $data['fieldName'] ?? '');

        $data['value'] = $this->formatDateTime($data['val'], $data);
        unset($data['val'], $data['timezone'], $data['format']);

        return $this->_templates->format('input', [
            'name' => $data['name'],
            'type' => $data['type'],
            'templateVars' => $data['templateVars'],
            'attrs' => $this->_templates->formatAttributes(
                $data,
                ['name', 'type']
            ),
        ]);
    }

    /**
     * Set value for "step" attribute if applicable.
     *
     * @param array<string, mixed> $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @param string $fieldName Field name.
     * @return array<string, mixed> Updated data array.
     */
    protected function setStep(array $data, ContextInterface $context, string $fieldName): array
    {
        if (array_key_exists('step', $data)) {
            return $data;
        }

        if (isset($data['format'])) {
            $data['step'] = null;
        } else {
            $data['step'] = $this->defaultStep[$data['type']];
        }

        if (empty($data['fieldName'])) {
            return $data;
        }

        $dbType = $context->type($fieldName);
        $fractionalTypes = [
            TableSchema::TYPE_DATETIME_FRACTIONAL,
            TableSchema::TYPE_TIMESTAMP_FRACTIONAL,
            TableSchema::TYPE_TIMESTAMP_TIMEZONE,
        ];

        if (in_array($dbType, $fractionalTypes, true)) {
            $data['step'] = '0.001';
        }

        return $data;
    }

    /**
     * Formats the passed date/time value into required string format.
     *
     * @param \DateTime|string|int|null $value Value to deconstruct.
     * @param array<string, mixed> $options Options for conversion.
     * @return string
     * @throws \InvalidArgumentException If invalid input type is passed.
     */
    protected function formatDateTime($value, array $options): string
    {
        if ($value === '' || $value === null) {
            return '';
        }

        try {
            if ($value instanceof DateTimeInterface) {
                $dateTime = clone $value;
            } elseif (is_string($value) && !is_numeric($value)) {
                $dateTime = new DateTime($value);
            } elseif (is_numeric($value)) {
                $dateTime = new DateTime('@' . $value);
            } else {
                $dateTime = new DateTime();
            }
        } catch (Exception $e) {
            $dateTime = new DateTime();
        }

        if (isset($options['timezone'])) {
            $timezone = $options['timezone'];
            if (!$timezone instanceof DateTimeZone) {
                $timezone = new DateTimeZone($timezone);
            }

            $dateTime = $dateTime->setTimezone($timezone);
        }

        if (isset($options['format'])) {
            $format = $options['format'];
        } else {
            $format = $this->formatMap[$options['type']];

            if (
                $options['type'] === 'datetime-local'
                && is_numeric($options['step'])
                && $options['step'] < 1
            ) {
                $format = 'Y-m-d\TH:i:s.v';
            }
        }

        return $dateTime->format($format);
    }

    /**
     * @inheritDoc
     */
    public function secureFields(array $data): array
    {
        if (!isset($data['name']) || $data['name'] === '') {
            return [];
        }

        return [$data['name']];
    }
}
