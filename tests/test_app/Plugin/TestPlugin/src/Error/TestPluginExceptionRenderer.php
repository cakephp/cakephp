<?php
/**
 * Exception Renderer
 *
 * Provides Exception rendering features. Which allow exceptions to be rendered
 * as HTML pages.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestPlugin\Error;

use Cake\Error\ExceptionRenderer;

/**
 * TestPluginExceptionRenderer
 */
class TestPluginExceptionRenderer extends ExceptionRenderer
{

    /**
     * Renders the response for the exception.
     *
     * @return string
     */
    public function render()
    {
        return 'Rendered by test plugin';
    }
}
