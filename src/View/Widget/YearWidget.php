<?php
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
namespace Cake\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use DateTime;
use RuntimeException;

class YearWidget implements WidgetInterface
{

    /**
     * Select box widget.
     *
     * @var \Cake\View\Widget\SelectBoxWidget
     */
    protected $_select;

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
     * Renders a year select box.
     *
     * @param array $data Data to render with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string A generated select box.
     * @throws \RuntimeException When option data is invalid.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += [
            'name' => '',
            'val' => null,
            'start' => date('Y', strtotime('-5 years')),
            'end' => date('Y', strtotime('+5 years')),
            'order' => 'desc',
            'templateVars' => [],
        ];

        if (!empty($data['min'])) {
            $data['start'] = $data['min'];
        }

        if (!empty($data['max'])) {
            $data['end'] = $data['max'];
        }

        if (!empty($data['val'])) {
            $data['start'] = min($data['val'], $data['start']);
            $data['end'] = max($data['val'], $data['end']);
        }

        if ($data['end'] < $data['start']) {
            throw new RuntimeException('Max year cannot be less than min year');
        }

        $data['options'] = $this->_generateNumbers((int)$data['start'], (int)$data['end']);
        if ($data['order'] === 'desc') {
            $data['options'] = array_reverse($data['options'], true);
        }
        unset($data['start'], $data['end'], $data['order'], $data['min'], $data['max']);

        return $this->_select->render($data, $context);
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
    protected function _generateNumbers(int $start, int $end, array $options = []): array
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
            $numbers[$key] = $value;
            $i += $options['interval'];
        }

        return $numbers;
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
