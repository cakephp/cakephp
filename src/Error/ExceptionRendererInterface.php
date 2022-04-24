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
 * @since         3.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ExceptionRendererInterface
 *
 * @method \Psr\Http\Message\ResponseInterface|string render() Render the exception to a string or Http Response.
 * @method void write(\Psr\Http\Message\ResponseInterface|string $output) Write the output to the output stream.
 *  This method is only called when exceptions are handled by a global default exception handler.
 */
interface ExceptionRendererInterface
{
    /**
     * Renders the response for the exception.
     *
     * @return \Psr\Http\Message\ResponseInterface The response to be sent.
     */
    public function render(): ResponseInterface;
}
