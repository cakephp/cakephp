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

use Cake\View\Form\ContextInterface;

/**
 * Interface for input widgets.
 */
interface WidgetInterface
{

    /**
     * Converts the $data into one or many HTML elements.
     *
     * @param array $data The data to render.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string Generated HTML for the widget element.
     */
    public function render(array $data, ContextInterface $context);

    /**
     * Returns a list of fields that need to be secured for
     * this widget. Fields are in the form of Model[field][suffix]
     *
     * @param array $data The data to render.
     * @return array Array of fields to secure.
     */
    public function secureFields(array $data);
}
