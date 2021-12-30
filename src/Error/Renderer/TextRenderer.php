<?php
declare(strict_types=1);

namespace Cake\Error\Renderer;

use Cake\Error\ErrorRendererInterface;
use Cake\Error\PhpError;

/**
 * Plain text error rendering with a stack trace.
 *
 * Useful in CLI and log file contexts.
 */
class TextRenderer implements ErrorRendererInterface
{
    /**
     * @inheritDoc
     */
    public function render(PhpError $error): string
    {
        return sprintf(
            "%s: %s :: %s on line %s of %s\nTrace:\n%s",
            $error->getLabel(),
            $error->getCode(),
            $error->getMessage(),
            $error->getLine(),
            $error->getFile(),
            $error->getTraceAsString(),
        );
    }
}
