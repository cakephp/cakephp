<?php
declare(strict_types=1);
namespace TestApp\Mailer\Email;

use Cake\Mailer\Email;

/**
 * Help to test Email
 */
class TestEmail extends Email
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
}
