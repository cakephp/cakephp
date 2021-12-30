<?php
declare(strict_types=1);

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
     * @return string The output to be echoed.
     */
    public function render(PhpError $error): string;
}
