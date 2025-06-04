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
     */
    public function setSocket(Socket $socket): void
    {
        $this->_socket = $socket;
    }

    /**
     * Disabled the socket change
     */
    protected function _generateSocket(): void
    {
    }

    /**
     * Returns only serializable properties
     *
     * @return array<string>
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), ['_socket']);
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

        return call_user_func_array($this->$method(...), $args);
    }

    /**
     * Returns the authentication type detected and used to connect to the SMTP server.
     * If no authentication was detected, null is returned.
     *
     * @return string|null
     */
    public function getAuthType(): ?string
    {
        return $this->authType;
    }

    public function setAuthType(string $type): void
    {
        $this->authType = $type;
    }
}
