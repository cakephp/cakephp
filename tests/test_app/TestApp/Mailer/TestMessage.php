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
    public function fmtAddress($address)
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
    public function encode($text)
    {
        return parent::encodeForHeader($text);
    }

    /**
     * Decode to protected method
     *
     * @return string
     */
    public function decode($text)
    {
        return parent::decodeForHeader($text);
    }

    /**
     * Wrap to protected method
     *
     * @param string $text
     * @param int $length
     * @return array
     */
    public function doWrap($text, $length = Message::LINE_LENGTH_MUST)
    {
        return $this->wrap($text, $length);
    }
}
