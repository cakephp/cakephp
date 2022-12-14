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
 */
interface ExceptionRendererInterface
{
    /**
     * Renders the response for the exception.
     *
     * @return \Psr\Http\Message\ResponseInterface|string The response to be sent.
     */
    public function render(): ResponseInterface|string;

    /**
     * Write the output to the output stream.
     *
     * This method is only called when exceptions are handled by a global default exception handler.
     *
     * @param \Psr\Http\Message\ResponseInterface|string $output Response instance or string for output.
     * @return void
     */
    public function write(ResponseInterface|string $output): void;
}
