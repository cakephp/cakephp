<?php
declare(strict_types=1);

namespace TestApp\Log\Formatter;

use Cake\Log\Formatter\AbstractFormatter;

class ValidFormatter extends AbstractFormatter
{
    /**
     * @inheritDoc
     */
    public function format($level, string $message, array $context = []): string
    {
        return $message;
    }
}
