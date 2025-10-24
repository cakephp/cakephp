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
namespace Cake\View\Widget;

use Cake\Chronos\ChronosDate;
use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Input widget class for generating a calendar year select box.
 *
 * This class is usually used internally by `Cake\View\Helper\FormHelper`,
 * it but can be used to generate standalone calendar year select boxes.
 */
class YearWidget extends BasicWidget
{
    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'name' => '',
        'val' => null,
        'min' => null,
        'max' => null,
        'order' => 'desc',
        'templateVars' => [],
    ];

    /**
     * Select box widget.
     *
     * @var \Cake\View\Widget\SelectBoxWidget
     */
    protected SelectBoxWidget $_select;

    /**
     * Constructor
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     * @param \Cake\View\Widget\SelectBoxWidget $selectBox Selectbox widget instance.
     */
    public function __construct(StringTemplate $templates, SelectBoxWidget $selectBox)
    {
        parent::__construct($templates);

        $this->_select = $selectBox;
    }

    /**
     * Renders a year select box.
     *
     * @param array<string, mixed> $data Data to render with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string A generated select box.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += $this->mergeDefaults($data, $context);

        if (empty($data['min'])) {
            $data['min'] = date('Y', strtotime('-5 years'));
        }

        if (empty($data['max'])) {
            $data['max'] = date('Y', strtotime('+5 years'));
        }

        $data['min'] = (int)$data['min'];
        $data['max'] = (int)$data['max'];

        if (
            $data['val'] instanceof ChronosDate ||
            $data['val'] instanceof DateTimeInterface
        ) {
            $data['val'] = $data['val']->format('Y');
        }

        if (!empty($data['val'])) {
            $data['min'] = min((int)$data['val'], $data['min']);
            $data['max'] = max((int)$data['val'], $data['max']);
        }

        if ($data['max'] < $data['min']) {
            throw new InvalidArgumentException('Max year cannot be less than min year');
        }

        if ($data['order'] === 'desc') {
            $data['options'] = range($data['max'], $data['min']);
        } else {
            $data['options'] = range($data['min'], $data['max']);
        }
        $data['options'] = array_combine($data['options'], $data['options']);

        unset($data['order'], $data['min'], $data['max']);

        return $this->_select->render($data, $context);
    }
}
