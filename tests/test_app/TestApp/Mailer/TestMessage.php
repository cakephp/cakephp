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
    public function fmtAddress(array $address)
    {
        return parent::formatAddress($address);
    }

    /**
     * Get the boundary attribute
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Encode to protected method
     *
     * @return string
     */
    public function encode(string $text)
    {
        return parent::encodeForHeader($text);
    }

    /**
     * Decode to protected method
     *
     * @return string
     */
    public function decode(string $text)
    {
        return parent::decodeForHeader($text);
    }

    /**
     * Wrap to protected method
     *
     * @return array
     */
    public function doWrap(string $text, int $length = Message::LINE_LENGTH_MUST)
    {
        return $this->wrap($text, $length);
    }
}
