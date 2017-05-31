<?php
namespace Cake\Network\Email;

use ArrayAccess;

class Envelope implements ArrayAccess
{
    protected $_email;

    public function __construct(Email $email = null)
    {
        if ($email === null) {
            $email = new Email();
        }

        $this->_email = $email;
    }

    public function __call($method, $args)
    {
        $this[$method] = $args;
        return $this;
    }

    public function __invoke(Email $email = null)
    {
        return $this->send($email);
    }

    public function send($config = [])
    {
        return $this->_email
            ->profile($this)
            ->send();
    }

    public function offsetExists($offset)
    {
        return in_array($offset, get_object_vars($this)) ||
            method_exists($this->_email, $offset);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw \Exception();
        }

        if (isset($this->{$offset})) {
            return $this->{$offset};
        }

        return call_user_func([$this->_email, $offset]);
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }
}
