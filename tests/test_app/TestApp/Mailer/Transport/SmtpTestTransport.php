<?php
declare(strict_types=1);

namespace TestApp\Mailer\Transport;

use Cake\Mailer\Transport\SmtpTransport;
use Cake\Network\Socket;

/**
 * Help to test SmtpTransport
 */
class SmtpTestTransport extends SmtpTransport
{
    /**
     * Helper to change the socket
     *
     * @param Socket $socket
     * @return void
     */
    public function setSocket(Socket $socket)
    {
        $this->_socket = $socket;
    }

    /**
     * Disabled the socket change
     *
     * @return void
     */
    protected function _generateSocket(): void
    {
    }

    /**
     * Magic function to call protected methods
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $method = '_' . $method;

        return call_user_func_array([$this, $method], $args);
    }
}
