<?php
namespace Cake\Mailer;

use ArrayAccess;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventListenerInterface;

class Mailer implements ArrayAccess, EventListenerInterface
{
    use ModelAwareTrait;

    /**
     * Email instance.
     *
     * @var \Cake\Mailer\Email
     */
    protected $_email;

    /**
     * Serialized email instance's initial state configuration defined by mailer.
     *
     * @var string
     */
    protected $_emailInitialState;

    /**
     * Constructor.
     *
     * @param \Cake\Mailer\Email|null $email Email instance.
     */
    public function __construct(Email $email = null)
    {
        if ($email === null) {
            $email = new Email();
        }

        $this->_email = $email->profile($this);
        $this->_emailInitialState = $email->jsonSerialize();
    }

    /**
     * Dispatches mailer actions.
     *
     * @param string $action Name of the action to trigger (i.e. 'welcome' will trigger '_welcome').
     * @param array $args Arguments passed to triggered action.
     * @return mixed When using `send` prefix, result of `\Cake\Mailer\Email::send()`. Otherwise, $this.
     */
    public function __call($method, $args)
    {
        $action = str_replace('send', '', $method);
        $send = $action !== $method;

        $actionMethod = Inflector::camelize($action);
        if (!method_exists($this, $actionMethod)) {
            throw new \Exception('Missing mailer action: ' . $actionMethod);
        }

        $this += [
            'template' => Inflector::underscore($action),
        ];

        call_user_func_array([$this, $actionMethod], $args);

        return $send ? $this->send() : $this;
    }

    /**
     * Implemented events.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }

    /**
     * Resets email instance to initial state used by mailer.
     *
     * @return $this object.
     */
    public function reset()
    {
        $this->_email->profile(json_decode($this->_emailInitialState));
        return $this;
    }

    /**
     * Sets headers.
     *
     * @param array $headers Headers to set.
     * @return  $this object.
     */
    public function setHeaders(array $headers)
    {
        $this->_email->setHeaders($headers);
        return $this;
    }

    /**
     * Adds headers.
     *
     * @param array $headers Headers to set.
     * @return  $this object.
     */
    public function addHeaders(array $headers)
    {
        $this->_email->addHeaders($headers);
        return $this;
    }

    /**
     * Sets attachments.
     *
     * @param string|array $attachments String with the filename or array with filenames
     * @return $this object.
     * @throws \InvalidArgumentException
     */
    public function attachments($attachments)
    {
        $this->_email->attachments($attachments);
        return $this;
    }

    /**
     * Sets email view vars.
     *
     * @param string|array $key Variable name or hash of view variables.
     * @param mixed $value View variable value.
     * @return $this object.
     */
    public function set($key, $value = null)
    {
        $this->_email->viewVars(is_string($key) ? [$key => $value] : $key);
        return $this;
    }

    /**
     * Sends email.
     *
     * @param array $headers Headers to set.
     * @return array
     * @throws \BadMethodCallException
     */
    public function send($headers = [])
    {
        $this->setHeaders($headers);

        $result = $this->_email
            ->profile($this)
            ->send();

        $this->reset();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return in_array($offset, get_object_vars($this)) ||
            method_exists($this->_email, $offset);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }
}
