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
 * Button input class
 *
 * This input class can be used to render button elements.
 * If you need to make basic submit inputs with type=submit,
 * use the Basic input widget.
 */
class ButtonWidget extends BasicWidget
{

    /**
     * Render a button.
     *
     * This method accepts a number of keys:
     *
     * - `text` The text of the button. Unlike all other form controls, buttons
     *   do not escape their contents by default.
     * - `escape` Set to true to enable escaping on all attributes.
     * - `type` The button type defaults to 'submit'.
     *
     * Any other keys provided in $data will be converted into HTML attributes.
     *
     * @param array $data The data to build a button with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context)
    {
        $data += [
            'text' => '',
            'type' => 'submit',
            'escape' => false,
            'templateVars' => []
        ];

        return $this->_templates->format('button', [
            'text' => $data['escape'] ? h($data['text']) : $data['text'],
            'templateVars' => $data['templateVars'],
            'attrs' => $this->_templates->formatAttributes($data, ['text']),
        ]);
    }
}
