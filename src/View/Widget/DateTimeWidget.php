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
use Cake\View\StringTemplate;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * Input widget class for generating a date time input widget.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class DateTimeWidget implements WidgetInterface
{
    /**
     * Template instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * Formats for various input types.
     *
     * @var string[]
     */
    protected $formatMap = [
        'datetime-local' => 'Y-m-d\TH:i:s.v',
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
     * @var array
     */
    protected $defaultStep = [
        'datetime-local' => '1',
        'date' => null,
        'time' => '1',
        'month' => null,
        'week' => null,
    ];

    /**
     * Constructor
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     */
    public function __construct(StringTemplate $templates)
    {
        $this->_templates = $templates;
    }

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
     *
     * All other keys will be converted into HTML attributes.
     *
     * @param array $data The data to build a file input with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string HTML elements.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += [
            'name' => '',
            'val' => null,
            'type' => 'datetime-local',
            'escape' => true,
            'step' => null,
            'timezone' => null,
            'templateVars' => [],
        ];

        if (!isset($this->formatMap[$data['type']])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid type "%s" for input tag',
                $data['type']
            ));
        }

        if (!isset($data['step'])) {
            $step = $this->defaultStep[$data['type']];

            if (isset($data['fieldName'])) {
                $fractionalTypes = [
                    TableSchema::TYPE_DATETIME_FRACTIONAL,
                    TableSchema::TYPE_TIMESTAMP_FRACTIONAL,
                ];

                $schemaType = $context->type($data['fieldName']);
                if (in_array($schemaType, $fractionalTypes, true)) {
                    $step = '0.001';
                }
            }

            $data['step'] = $step;
        }

        $data['value'] = $this->formatDateTime($data['val'], $data);
        unset($data['val'], $data['timezone'], $data['fieldName']);

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
     * Formats the passed date/time value into required string format.
     *
     * @param string|\DateTime|null $value Value to deconstruct.
     * @param array $options Options for conversion.
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
            } elseif (is_int($value) || is_numeric($value)) {
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

        return $dateTime->format($this->formatMap[$options['type']]);
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
