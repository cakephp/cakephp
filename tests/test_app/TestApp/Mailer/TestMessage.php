<?php
declare(strict_types=1);

namespace TestApp\Mailer;

use Cake\Mailer\Message;

/**
 * Help to test Message
 */
class TestMessage extends Message
{
    /**
     * Wrap to protected method
     *
     * @return array
     */
    public function fmtAddress(array $address): array
    {
        return parent::formatAddress($address);
    }

    /**
     * Get the boundary attribute
     */
    public function getBoundary(): ?string
    {
        return $this->boundary;
    }

    /**
     * Encode to protected method
     */
    public function encode(string $text): string
    {
        return parent::encodeForHeader($text);
    }

    /**
     * Decode to protected method
     */
    public function decode(string $text): string
    {
        return parent::decodeForHeader($text);
    }

    /**
     * Wrap to protected method
     *
     * @return array
     */
    public function doWrap(string $text, int $length = Message::LINE_LENGTH_MUST): array
    {
        return $this->wrap($text, $length);
    }
}
