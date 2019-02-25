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
    public function formatAddress($address)
    {
        return parent::_formatAddress($address);
    }

    /**
     * Get the boundary attribute
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->_boundary;
    }

    /**
     * Encode to protected method
     *
     * @return string
     */
    public function encode($text)
    {
        return $this->_encode($text);
    }

    /**
     * Decode to protected method
     *
     * @return string
     */
    public function decode($text)
    {
        return $this->_decode($text);
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
