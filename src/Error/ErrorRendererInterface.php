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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

/**
 * Interface for PHP error rendering implementations
 *
 * The core provided implementations of this interface are used
 * by Debugger and ErrorTrap to render PHP errors.
 */
interface ErrorRendererInterface
{
    /**
     * Render output for the provided error.
     *
     * @param \Cake\Error\PhpError $error The error to be rendered.
     * @param bool $debug Whether the application is in debug mode.
     * @return string The output to be echoed.
     */
    public function render(PhpError $error, bool $debug): string;

    /**
     * Write output to the renderer's output stream
     *
     * @param string $out The content to output.
     * @return void
     */
    public function write(string $out): void;
}
